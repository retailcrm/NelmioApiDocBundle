<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Annotation;

use Symfony\Component\Routing\Route;

/**
 * @Annotation
 */
class ApiDoc
{
    public const DEFAULT_VIEW = 'default';

    /**
     * Requirements are mandatory parameters in a route.
     *
     * @var array
     */
    private $requirements = [];

    /**
     * Which views is this route used. Defaults to "Default"
     *
     * @var array
     */
    private $views = [];

    /**
     * Filters are optional parameters in the query string.
     *
     * @var array
     */
    private $filters = [];

    /**
     * Parameters are data a client can send.
     *
     * @var array
     */
    private $parameters = [];
    /**
     * Headers that client can send.
     *
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $input;

    /**
     * @var string
     */
    private $inputs;

    /**
     * @var string
     */
    private $output;

    /**
     * @var string
     */
    private $link;

    /**
     * Most of the time, a single line of text describing the action.
     *
     * @var string
     */
    private $description;

    /**
     * Section to group actions together.
     *
     * @var string
     */
    private $section;

    /**
     * Extended documentation.
     *
     * @var string
     */
    private $documentation;

    /**
     * @var bool
     */
    private $resource = false;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var array
     */
    private $response = [];

    /**
     * @var Route
     */
    private $route;

    /**
     * @var bool
     */
    private $https = false;

    /**
     * @var bool
     */
    private $authentication = false;

    /**
     * @var array
     */
    private $authenticationRoles = [];

    /**
     * @var int
     */
    private $cache;

    /**
     * @var bool
     */
    private $deprecated = false;

    /**
     * @var array
     */
    private $statusCodes = [];

    /**
     * @var string|null
     */
    private $resourceDescription;

    /**
     * @var array
     */
    private $responseMap = [];

    /**
     * @var array
     */
    private $parsedResponseMap = [];

    /**
     * @var array
     */
    private $tags = [];

    public function __construct(array $data)
    {
        $this->resource = !empty($data['resource']) ? $data['resource'] : false;

        if (isset($data['description'])) {
            $this->description = $data['description'];
        }

        if (isset($data['input'])) {
            $this->input = $data['input'];
        }

        if (isset($data['inputs'])) {
            $this->inputs = $data['inputs'];
        }

        if (isset($data['filters'])) {
            foreach ($data['filters'] as $filter) {
                if (!isset($filter['name'])) {
                    throw new \InvalidArgumentException('A "filter" element has to contain a "name" attribute');
                }

                $name = $filter['name'];
                unset($filter['name']);

                $this->addFilter($name, $filter);
            }
        }

        if (isset($data['requirements'])) {
            foreach ($data['requirements'] as $requirement) {
                if (!isset($requirement['name'])) {
                    throw new \InvalidArgumentException('A "requirement" element has to contain a "name" attribute');
                }

                $name = $requirement['name'];
                unset($requirement['name']);

                $this->addRequirement($name, $requirement);
            }
        }

        if (isset($data['views'])) {
            if (!is_array($data['views'])) {
                $data['views'] = [$data['views']];
            }

            foreach ($data['views'] as $view) {
                $this->addView($view);
            }
        }

        if (isset($data['parameters'])) {
            foreach ($data['parameters'] as $parameter) {
                if (!isset($parameter['name'])) {
                    throw new \InvalidArgumentException('A "parameter" element has to contain a "name" attribute');
                }

                if (!isset($parameter['dataType'])) {
                    throw new \InvalidArgumentException(sprintf(
                        '"%s" parameter element has to contain a "dataType" attribute',
                        $parameter['name']
                    ));
                }

                $name = $parameter['name'];
                unset($parameter['name']);

                $this->addParameter($name, $parameter);
            }
        }

        if (isset($data['headers'])) {
            foreach ($data['headers'] as $header) {
                if (!isset($header['name'])) {
                    throw new \InvalidArgumentException('A "header" element has to contain a "name" attribute');
                }

                $name = $header['name'];
                unset($header['name']);

                $this->addHeader($name, $header);
            }
        }

        if (isset($data['output'])) {
            $this->output = $data['output'];
        }

        if (isset($data['statusCodes'])) {
            foreach ($data['statusCodes'] as $statusCode => $description) {
                $this->addStatusCode($statusCode, $description);
            }
        }

        if (isset($data['authentication'])) {
            $this->setAuthentication((bool) $data['authentication']);
        }

        if (isset($data['authenticationRoles'])) {
            foreach ($data['authenticationRoles'] as $key => $role) {
                $this->authenticationRoles[] = $role;
            }
        }

        if (isset($data['cache'])) {
            $this->setCache($data['cache']);
        }

        if (isset($data['section'])) {
            $this->section = $data['section'];
        }

        if (isset($data['deprecated'])) {
            $this->deprecated = $data['deprecated'];
        }

        if (isset($data['tags'])) {
            if (is_array($data['tags'])) {
                foreach ($data['tags'] as $tag => $colorCode) {
                    if (is_numeric($tag)) {
                        $this->addTag($colorCode);
                    } else {
                        $this->addTag($tag, $colorCode);
                    }
                }
            } else {
                $this->tags[] = $data['tags'];
            }
        }

        if (isset($data['https'])) {
            $this->https = $data['https'];
        }

        if (isset($data['resourceDescription'])) {
            $this->resourceDescription = $data['resourceDescription'];
        }

        if (isset($data['responseMap'])) {
            $this->responseMap = $data['responseMap'];
            if (isset($this->responseMap[200])) {
                $this->output = $this->responseMap[200];
            }
        }
    }

    /**
     * @param string $name
     */
    public function addFilter($name, array $filter): void
    {
        $this->filters[$name] = $filter;
    }

    /**
     * @param string $statusCode
     */
    public function addStatusCode($statusCode, $description): void
    {
        $this->statusCodes[$statusCode] = !is_array($description) ? [$description] : $description;
    }

    /**
     * @param string $tag
     * @param string $colorCode
     */
    public function addTag($tag, $colorCode = '#d9534f'): void
    {
        $this->tags[$tag] = $colorCode;
    }

    /**
     * @param string $name
     */
    public function addRequirement($name, array $requirement): void
    {
        $this->requirements[$name] = $requirement;
    }

    public function setRequirements(array $requirements): void
    {
        $this->requirements = array_merge($this->requirements, $requirements);
    }

    /**
     * @return string|null
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @return array|null
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * @return string|null
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @param string $link
     */
    public function setLink($link): void
    {
        $this->link = $link;
    }

    /**
     * @param string $section
     */
    public function setSection($section): void
    {
        $this->section = $section;
    }

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @return array
     */
    public function addView($view)
    {
        $this->views[] = $view;
    }

    /**
     * @return array
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * @param string $documentation
     */
    public function setDocumentation($documentation): void
    {
        $this->documentation = $documentation;
    }

    /**
     * @return string
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }

    /**
     * @return bool
     */
    public function isResource()
    {
        return (bool) $this->resource;
    }

    public function getResource()
    {
        return $this->resource && is_string($this->resource) ? $this->resource : false;
    }

    /**
     * @param string $name
     */
    public function addParameter($name, array $parameter): void
    {
        $this->parameters[$name] = $parameter;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function addHeader($name, array $header): void
    {
        $this->headers[$name] = $header;
    }

    /**
     * Sets the response data as processed by the parsers - same format as parameters
     */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    public function setRoute(Route $route): void
    {
        $this->route = $route;

        if (method_exists($route, 'getHost')) {
            $this->host = $route->getHost() ?: null;

            // replace route placeholders
            foreach ($route->getDefaults() as $key => $value) {
                if (null !== $this->host && is_string($value)) {
                    $this->host = str_replace('{' . $key . '}', $value, $this->host);
                }
            }
        } else {
            $this->host = null;
        }

        $this->uri = $route->getPath();
        $this->method = $route->getMethods() ? implode('|', $route->getMethods()) : 'ANY';
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host): void
    {
        $this->host = $host;
    }

    /**
     * @return bool
     */
    public function getHttps()
    {
        return $this->https;
    }

    /**
     * @param bool $https
     */
    public function setHttps($https): void
    {
        $this->https = $https;
    }

    /**
     * @return bool
     */
    public function getAuthentication()
    {
        return $this->authentication;
    }

    /**
     * @param bool $authentication
     */
    public function setAuthentication($authentication): void
    {
        $this->authentication = $authentication;
    }

    /**
     * @return array
     */
    public function getAuthenticationRoles()
    {
        return $this->authenticationRoles;
    }

    /**
     * @param array $authenticationRoles
     */
    public function setAuthenticationRoles($authenticationRoles): void
    {
        $this->authenticationRoles = $authenticationRoles;
    }

    /**
     * @return int
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param int $cache
     */
    public function setCache($cache): void
    {
        $this->cache = (int) $cache;
    }

    /**
     * @return bool
     */
    public function getDeprecated()
    {
        return $this->deprecated;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return array
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param bool $deprecated
     *
     * @return $this
     */
    public function setDeprecated($deprecated)
    {
        $this->deprecated = (bool) $deprecated;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [
            'method' => $this->method,
            'uri' => $this->uri,
        ];

        if ($host = $this->host) {
            $data['host'] = $host;
        }

        if ($description = $this->description) {
            $data['description'] = $description;
        }

        if ($link = $this->link) {
            $data['link'] = $link;
        }

        if ($documentation = $this->documentation) {
            $data['documentation'] = $documentation;
        }

        if ($filters = $this->filters) {
            $data['filters'] = $filters;
        }

        if ($parameters = $this->parameters) {
            $data['parameters'] = $parameters;
        }

        if ($headers = $this->headers) {
            $data['headers'] = $headers;
        }

        if ($requirements = $this->requirements) {
            $data['requirements'] = $requirements;
        }

        if ($views = $this->views) {
            $data['views'] = $views;
        }

        if ($response = $this->response) {
            $data['response'] = $response;
        }

        if ($parsedResponseMap = $this->parsedResponseMap) {
            $data['parsedResponseMap'] = $parsedResponseMap;
        }

        if ($statusCodes = $this->statusCodes) {
            $data['statusCodes'] = $statusCodes;
        }

        if ($section = $this->section) {
            $data['section'] = $section;
        }

        if ($cache = $this->cache) {
            $data['cache'] = $cache;
        }

        if ($tags = $this->tags) {
            $data['tags'] = $tags;
        }

        if ($resourceDescription = $this->resourceDescription) {
            $data['resourceDescription'] = $resourceDescription;
        }

        $data['https'] = $this->https;
        $data['authentication'] = $this->authentication;
        $data['authenticationRoles'] = $this->authenticationRoles;
        $data['deprecated'] = $this->deprecated;

        return $data;
    }

    /**
     * @return string|null
     */
    public function getResourceDescription()
    {
        return $this->resourceDescription;
    }

    /**
     * @return array
     */
    public function getResponseMap()
    {
        if (!isset($this->responseMap[200]) && null !== $this->output) {
            $this->responseMap[200] = $this->output;
        }

        return $this->responseMap;
    }

    /**
     * @return array
     */
    public function getParsedResponseMap()
    {
        return $this->parsedResponseMap;
    }

    /**
     * @param int $statusCode
     */
    public function setResponseForStatusCode($model, $type, $statusCode = 200): void
    {
        $this->parsedResponseMap[$statusCode] = ['type' => $type, 'model' => $model];
        if (200 == $statusCode && $this->response !== $model) {
            $this->response = $model;
        }
    }
}

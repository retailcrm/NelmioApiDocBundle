<?php

namespace Nelmio\ApiDocBundle\Attribute;

use Symfony\Component\Routing\Route;

#[\Attribute(\Attribute::TARGET_METHOD)]
class ApiDoc
{
    public const DEFAULT_VIEW = 'default';

    /**
     * Requirements are mandatory parameters in a route.
     *
     * @var array<string, array<string, string>>
     */
    private array $requirements = [];

    /**
     * Which views is this route used. Defaults to "Default"
     *
     * @var string[]
     */
    private array $views = [];

    /**
     * Filters are optional parameters in the query string.
     *
     * @var array<string, array<string, string>>
     */
    private array $filters = [];

    /**
     * Parameters are data a client can send.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $parameters = [];
    /**
     * Headers that client can send.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $headers = [];

    private ?string $link = null;

    /**
     * Extended documentation.
     */
    private ?string $documentation = null;

    private Route $route;
    private ?string $host = null;
    private string $method;
    private string $uri;

    private array $response = [];

    /**
     * @var array<int|string, string[]>
     */
    private array $statusCodes = [];

    /**
     * @var array<int, array<mixed>>
     */
    private array $responseMap = [];

    private array $parsedResponseMap = [];

    /**
     * @var array<string|int, string>
     */
    private array $tags = [];

    private ?string $scope = null;

    /**
     * @param string[]|string|null $description
     */
    public function __construct(
        private string|bool $resource = false,
        private array|string|null $description = null,
        private string|array|null $input = null,
        private ?array $inputs = null,
        private string|array|null $output = null,
        private ?string $section = null,
        private bool $deprecated = false,
        private ?string $resourceDescription = null,
        ?array $filters = null,
        ?array $requirements = null,
        array|string|null $views = null,
        ?array $parameters = null,
        ?array $headers = null,
        ?array $statusCodes = null,
        array|string|int|null $tags = null,
        ?array $responseMap = null,
    ) {
        if (null !== $filters) {
            foreach ($filters as $filter) {
                if (!isset($filter['name'])) {
                    throw new \InvalidArgumentException('A "filter" element has to contain a "name" attribute');
                }

                $name = $filter['name'];
                unset($filter['name']);

                $this->addFilter($name, $filter);
            }
        }

        if (null !== $requirements) {
            foreach ($requirements as $requirement) {
                if (!isset($requirement['name'])) {
                    throw new \InvalidArgumentException('A "requirement" element has to contain a "name" attribute');
                }

                $name = $requirement['name'];
                unset($requirement['name']);

                $this->addRequirement($name, $requirement);
            }
        }

        if (null !== $views) {
            if (!is_array($views)) {
                $views = [$views];
            }

            foreach ($views as $view) {
                $this->addView($view);
            }
        }

        if (null !== $parameters) {
            foreach ($parameters as $parameter) {
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

        if (null !== $headers) {
            foreach ($headers as $header) {
                if (!isset($header['name'])) {
                    throw new \InvalidArgumentException('A "header" element has to contain a "name" attribute');
                }

                $name = $header['name'];
                unset($header['name']);

                $this->addHeader($name, $header);
            }
        }

        if (null !== $statusCodes) {
            foreach ($statusCodes as $statusCode => $statusDescription) {
                $this->addStatusCode($statusCode, $statusDescription);
            }
        }

        if (null !== $tags) {
            if (is_array($tags)) {
                foreach ($tags as $tag => $colorCode) {
                    if (is_numeric($tag)) {
                        $this->addTag($colorCode);
                    } else {
                        $this->addTag($tag, $colorCode);
                    }
                }
            } else {
                $this->tags[] = $tags;
            }
        }

        if (null !== $responseMap) {
            $this->responseMap = $responseMap;
            if (isset($this->responseMap[200])) {
                $this->output = $this->responseMap[200];
            }
        }
    }

    public function addFilter(string $name, array $filter): void
    {
        $this->filters[$name] = $filter;
    }

    public function addStatusCode(int|string $statusCode, string|array $description): void
    {
        $this->statusCodes[$statusCode] = !is_array($description) ? [$description] : $description;
    }

    public function addTag(int|string $tag, string $colorCode = '#d9534f'): void
    {
        $this->tags[$tag] = $colorCode;
    }

    public function addRequirement(string $name, array $requirement): void
    {
        $this->requirements[$name] = $requirement;
    }

    public function setRequirements(array $requirements): void
    {
        $this->requirements = array_merge($this->requirements, $requirements);
    }

    public function getInput(): string|array|null
    {
        return $this->input;
    }

    public function getInputs(): ?array
    {
        return $this->inputs;
    }

    public function getOutput(): array|string|null
    {
        return $this->output;
    }

    /**
     * @return string[]|string|null
     */
    public function getDescription(): array|string|null
    {
        return $this->description;
    }

    /**
     * @param string[]|string|null $description
     */
    public function setDescription(array|string|null $description): void
    {
        $this->description = $description;
    }

    public function setLink(?string $link): void
    {
        $this->link = $link;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function addView(string $view): void
    {
        $this->views[] = $view;
    }

    /**
     * @return string[]
     */
    public function getViews(): array
    {
        return $this->views;
    }

    public function setDocumentation(?string $documentation): void
    {
        $this->documentation = $documentation;
    }

    public function getDocumentation(): ?string
    {
        return $this->documentation;
    }

    public function isResource(): bool
    {
        return (bool) $this->resource;
    }

    public function getResource(): string|bool
    {
        return $this->resource && is_string($this->resource) ? $this->resource : false;
    }

    public function addParameter(string $name, array $parameter): void
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

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getDeprecated(): bool
    {
        return $this->deprecated;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getRequirements(): array
    {
        return $this->requirements;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setDeprecated(bool $deprecated): void
    {
        $this->deprecated = $deprecated;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [
            'method' => $this->method ?? null,
            'uri' => $this->uri ?? null,
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

        if ($tags = $this->tags) {
            $data['tags'] = $tags;
        }

        if ($resourceDescription = $this->resourceDescription) {
            $data['resourceDescription'] = $resourceDescription;
        }

        $data['deprecated'] = $this->deprecated;
        $data['scope'] = $this->scope;

        return $data;
    }

    public function getResourceDescription(): ?string
    {
        return $this->resourceDescription;
    }

    public function getResponseMap(): array
    {
        if (!isset($this->responseMap[200]) && null !== $this->output) {
            $this->responseMap[200] = $this->output;
        }

        return $this->responseMap;
    }

    public function getParsedResponseMap(): array
    {
        return $this->parsedResponseMap;
    }

    public function setResponseForStatusCode(array $model, array $type, int $statusCode = 200): void
    {
        $this->parsedResponseMap[$statusCode] = ['type' => $type, 'model' => $model];
        if (200 === $statusCode && $this->response !== $model) {
            $this->response = $model;
        }
    }
}

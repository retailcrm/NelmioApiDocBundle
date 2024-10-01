<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Formatter;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Swagger\ModelRegistry;
use Symfony\Component\HttpFoundation\Response;

/**
 * Produces Swagger-compliant resource lists and API declarations as defined here:
 * https://github.com/wordnik/swagger-spec/blob/master/versions/1.2.md
 *
 * This formatter produces an array. Therefore output still needs to be `json_encode`d before passing on as HTTP response.
 *
 * @author Bezalel Hermoso <bezalelhermoso@gmail.com>
 */
class SwaggerFormatter implements FormatterInterface
{
    protected $basePath;

    protected $apiVersion;

    protected $swaggerVersion;

    protected $info = [];

    protected $typeMap = [
        DataTypes::INTEGER => 'integer',
        DataTypes::FLOAT => 'number',
        DataTypes::STRING => 'string',
        DataTypes::BOOLEAN => 'boolean',
        DataTypes::FILE => 'string',
        DataTypes::DATE => 'string',
        DataTypes::DATETIME => 'string',
    ];

    protected $formatMap = [
        DataTypes::INTEGER => 'int32',
        DataTypes::FLOAT => 'float',
        DataTypes::FILE => 'byte',
        DataTypes::DATE => 'date',
        DataTypes::DATETIME => 'date-time',
    ];

    /**
     * @var ModelRegistry
     */
    protected $modelRegistry;

    public function __construct($namingStategy)
    {
        $this->modelRegistry = new ModelRegistry($namingStategy);
    }

    /**
     * @var array
     */
    protected $authConfig;

    public function setAuthenticationConfig(array $config): void
    {
        $this->authConfig = $config;
    }

    /**
     * Format a collection of documentation data.
     *
     * If resource is provided, an API declaration for that resource is produced. Otherwise, a resource listing is returned.
     *
     * @param array|ApiDoc[] $collection
     * @param string|null    $resource
     *
     * @return string|array
     */
    public function format(array $collection, $resource = null)
    {
        if (null === $resource) {
            return $this->produceResourceListing($collection);
        } else {
            return $this->produceApiDeclaration($collection, $resource);
        }
    }

    /**
     * Formats the collection into Swagger-compliant output.
     *
     * @return array
     */
    public function produceResourceListing(array $collection)
    {
        $resourceList = [
            'swaggerVersion' => (string) $this->swaggerVersion,
            'apis' => [],
            'apiVersion' => (string) $this->apiVersion,
            'info' => $this->getInfo(),
            'authorizations' => $this->getAuthorizations(),
        ];

        $apis = &$resourceList['apis'];

        foreach ($collection as $item) {
            /** @var $apiDoc ApiDoc */
            $apiDoc = $item['annotation'];
            $resource = $item['resource'];

            if (!$apiDoc->isResource()) {
                continue;
            }

            $subPath = $this->stripBasePath($resource);
            $normalizedName = $this->normalizeResourcePath($subPath);

            $apis[] = [
                'path' => '/' . $normalizedName,
                'description' => $apiDoc->getResourceDescription(),
            ];
        }

        return $resourceList;
    }

    protected function getAuthorizations()
    {
        $auth = [];

        if (null === $this->authConfig) {
            return $auth;
        }

        $config = $this->authConfig;

        if ('http' === $config['delivery']) {
            return $auth;
        }

        $auth['apiKey'] = [
            'type' => 'apiKey',
            'passAs' => $config['delivery'],
            'keyname' => $config['name'],
        ];

        return $auth;
    }

    /**
     * @return array
     */
    protected function getInfo()
    {
        return $this->info;
    }

    /**
     * Format documentation data for one route.
     *
     * @param ApiDoc $annotation
     *                           return string|array
     *
     * @throws \BadMethodCallException
     */
    public function formatOne(ApiDoc $annotation): void
    {
        throw new \BadMethodCallException(sprintf('%s does not support formatting a single ApiDoc only.', __CLASS__));
    }

    /**
     * Formats collection to produce a Swagger-compliant API declaration for the given resource.
     *
     * @param string $resource
     *
     * @return array
     */
    protected function produceApiDeclaration(array $collection, $resource)
    {
        $apiDeclaration = [
            'swaggerVersion' => (string) $this->swaggerVersion,
            'apiVersion' => (string) $this->apiVersion,
            'basePath' => $this->basePath,
            'resourcePath' => $resource,
            'apis' => [],
            'models' => [],
            'produces' => [],
            'consumes' => [],
            'authorizations' => $this->getAuthorizations(),
        ];

        $main = null;

        $apiBag = [];

        foreach ($collection as $item) {
            /** @var $apiDoc ApiDoc */
            $apiDoc = $item['annotation'];
            $itemResource = $this->stripBasePath($item['resource']);
            $input = $apiDoc->getInput();

            if (!is_array($input)) {
                $input = [
                    'class' => $input,
                    'paramType' => 'form',
                ];
            } elseif (empty($input['paramType'])) {
                $input['paramType'] = 'form';
            }

            $route = $apiDoc->getRoute();

            $itemResource = $this->normalizeResourcePath($itemResource);

            if ('/' . $itemResource !== $resource) {
                continue;
            }

            $compiled = $route->compile();

            $path = $this->stripBasePath($route->getPath());

            if (!isset($apiBag[$path])) {
                $apiBag[$path] = [];
            }

            $parameters = [];
            $responseMessages = [];

            foreach ($compiled->getPathVariables() as $paramValue) {
                $parameter = [
                    'paramType' => 'path',
                    'name' => $paramValue,
                    'type' => 'string',
                    'required' => true,
                ];

                if ('_format' === $paramValue && false != ($req = $route->getRequirement('_format'))) {
                    $parameter['enum'] = explode('|', $req);
                }

                $parameters[] = $parameter;
            }

            $data = $apiDoc->toArray();

            if (isset($data['filters'])) {
                $parameters = array_merge($parameters, $this->deriveQueryParameters($data['filters']));
            }

            if (isset($data['parameters'])) {
                $parameters = array_merge($parameters, $this->deriveParameters($data['parameters'], $input['paramType']));
            }

            $responseMap = $apiDoc->getParsedResponseMap();

            $statusMessages = $data['statusCodes'] ?? [];

            foreach ($responseMap as $statusCode => $prop) {
                if (isset($statusMessages[$statusCode])) {
                    $message = is_array($statusMessages[$statusCode]) ? implode('; ', $statusMessages[$statusCode]) : $statusCode[$statusCode];
                } else {
                    $message = sprintf('See standard HTTP status code reason for %s', $statusCode);
                }

                $className = !empty($prop['type']['form_errors']) ? $prop['type']['class'] . '.ErrorResponse' : $prop['type']['class'];

                if (isset($prop['type']['collection']) && true === $prop['type']['collection']) {
                    /*
                     * Without alias:       Fully\Qualified\Class\Name[]
                     * With alias:          Fully\Qualified\Class\Name[alias]
                     */
                    $alias = $prop['type']['collectionName'];

                    $newName = sprintf('%s[%s]', $className, $alias);
                    $collId =
                        $this->registerModel(
                            $newName,
                            [
                                $alias => [
                                    'dataType' => null,
                                    'subType' => $className,
                                    'actualType' => DataTypes::COLLECTION,
                                    'required' => true,
                                    'readonly' => true,
                                    'description' => null,
                                    'default' => null,
                                    'children' => $prop['model'][$alias]['children'],
                                ],
                            ],
                            ''
                        );
                    $responseModel = [
                        'code' => $statusCode,
                        'message' => $message,
                        'responseModel' => $collId,
                    ];
                } else {
                    $responseModel = [
                        'code' => $statusCode,
                        'message' => $message,
                        'responseModel' => $this->registerModel($className, $prop['model'], ''),
                    ];
                }
                $responseMessages[$statusCode] = $responseModel;
            }

            $unmappedMessages = array_diff(array_keys($statusMessages), array_keys($responseMessages));

            foreach ($unmappedMessages as $code) {
                $responseMessages[$code] = [
                    'code' => $code,
                    'message' => is_array($statusMessages[$code]) ? implode('; ', $statusMessages[$code]) : $statusMessages[$code],
                ];
            }

            $type = $responseMessages[200]['responseModel'] ?? null;

            foreach ($apiDoc->getRoute()->getMethods() as $method) {
                $operation = [
                    'method' => $method,
                    'summary' => $apiDoc->getDescription(),
                    'nickname' => $this->generateNickname($method, $itemResource),
                    'parameters' => $parameters,
                    'responseMessages' => array_values($responseMessages),
                ];

                if (null !== $type) {
                    $operation['type'] = $type;
                }

                $apiBag[$path][] = $operation;
            }
        }

        $apiDeclaration['resourcePath'] = $resource;

        foreach ($apiBag as $path => $operations) {
            $apiDeclaration['apis'][] = [
                'path' => $path,
                'operations' => $operations,
            ];
        }

        $apiDeclaration['models'] = $this->modelRegistry->getModels();
        $this->modelRegistry->clear();

        return $apiDeclaration;
    }

    /**
     * Slugify a URL path. Trims out path parameters wrapped in curly brackets.
     *
     * @return string
     */
    protected function normalizeResourcePath($path)
    {
        $path = preg_replace('/({.*?})/', '', $path);
        $path = trim(preg_replace('/[^0-9a-zA-Z]/', '-', $path), '-');
        $path = preg_replace('/-+/', '-', $path);

        return $path;
    }

    public function setBasePath($path): void
    {
        $this->basePath = $path;
    }

    /**
     * Formats query parameters to Swagger-compliant form.
     *
     * @return array
     */
    protected function deriveQueryParameters(array $input)
    {
        $parameters = [];

        foreach ($input as $name => $prop) {
            if (!isset($prop['dataType'])) {
                $prop['dataType'] = 'string';
            }
            $parameters[] = [
                'paramType' => 'query',
                'name' => $name,
                'type' => $this->typeMap[$prop['dataType']] ?? 'string',
                'description' => $prop['description'] ?? null,
            ];
        }

        return $parameters;
    }

    /**
     * Builds a Swagger-compliant parameter list from the provided parameter array. Models are built when necessary.
     *
     * @param string $paramType
     *
     * @return array
     */
    protected function deriveParameters(array $input, $paramType = 'form')
    {
        $parameters = [];

        foreach ($input as $name => $prop) {
            $type = null;
            $format = null;
            $ref = null;
            $enum = null;
            $items = null;

            if (!isset($prop['actualType'])) {
                $prop['actualType'] = 'string';
            }

            if (isset($this->typeMap[$prop['actualType']])) {
                $type = $this->typeMap[$prop['actualType']];
            } else {
                switch ($prop['actualType']) {
                    case DataTypes::ENUM:
                        $type = 'string';
                        if (isset($prop['format'])) {
                            $enum = explode('|', rtrim(ltrim($prop['format'], '['), ']'));
                        }
                        break;

                    case DataTypes::MODEL:
                        $ref =
                            $this->registerModel(
                                $prop['subType'],
                                $prop['children'] ?? null,
                                $prop['description'] ?: $prop['dataType']
                            );
                        break;

                    case DataTypes::COLLECTION:
                        $type = 'array';
                        if (null === $prop['subType']) {
                            $items = ['type' => 'string'];
                        } elseif (isset($this->typeMap[$prop['subType']])) {
                            $items = ['type' => $this->typeMap[$prop['subType']]];
                        } else {
                            $ref =
                                $this->registerModel(
                                    $prop['subType'],
                                    $prop['children'] ?? null,
                                    $prop['description'] ?: $prop['dataType']
                                );
                            $items = [
                                '$ref' => $ref,
                            ];
                        }
                        break;
                }
            }

            if (isset($this->formatMap[$prop['actualType']])) {
                $format = $this->formatMap[$prop['actualType']];
            }

            if (null === $type && null === $ref) {
                /* `type` or `$ref` is required. Continue to next of none of these was determined. */
                continue;
            }

            $parameter = [
                'paramType' => $paramType,
                'name' => $name,
            ];

            if (null !== $type) {
                $parameter['type'] = $type;
            }

            if (null !== $ref) {
                $parameter['$ref'] = $ref;
                $parameter['type'] = $ref;
            }

            if (null !== $format) {
                $parameter['format'] = $format;
            }

            if (is_array($enum) && count($enum) > 0) {
                $parameter['enum'] = $enum;
            }

            if (isset($prop['default'])) {
                $parameter['defaultValue'] = $prop['default'];
            }

            if (isset($items)) {
                $parameter['items'] = $items;
            }

            if (isset($prop['description'])) {
                $parameter['description'] = $prop['description'];
            }

            $parameters[] = $parameter;
        }

        return $parameters;
    }

    /**
     * Registers a model into the model array. Returns a unique identifier for the model to be used in `$ref` properties.
     *
     * @param string $description
     *
     * @internal param $models
     */
    public function registerModel($className, ?array $parameters = null, $description = '')
    {
        return $this->modelRegistry->register($className, $parameters, $description);
    }

    public function setSwaggerVersion($swaggerVersion): void
    {
        $this->swaggerVersion = $swaggerVersion;
    }

    public function setApiVersion($apiVersion): void
    {
        $this->apiVersion = $apiVersion;
    }

    public function setInfo($info): void
    {
        $this->info = $info;
    }

    /**
     * Strips the base path from a URL path.
     */
    protected function stripBasePath($basePath)
    {
        if ('/' === $this->basePath) {
            return $basePath;
        }

        $path = sprintf('#^%s#', preg_quote($this->basePath));
        $subPath = preg_replace($path, '', $basePath);

        return $subPath;
    }

    /**
     * Generate nicknames based on support HTTP methods and the resource name.
     *
     * @return string
     */
    protected function generateNickname($method, $resource)
    {
        $resource = preg_replace('#/^#', '', $resource);
        $resource = $this->normalizeResourcePath($resource);

        return sprintf('%s_%s', strtolower($method ?: ''), $resource);
    }
}

<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Swagger;

use Nelmio\ApiDocBundle\DataTypes;

/**
 * Class ModelRegistry
 *
 * @author Bez Hermoso <bez@activelamp.com>
 */
class ModelRegistry
{
    /**
     * @var array
     */
    protected $namingStrategies = [
        'dot_notation' => 'nameDotNotation',
        'last_segment_only' => 'nameLastSegmentOnly',
    ];

    /**
     * @var array
     */
    protected $models = [];

    protected $classes = [];

    /**
     * @var callable
     */
    protected $namingStategy;

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

    public function __construct($namingStrategy)
    {
        if (!isset($this->namingStrategies[$namingStrategy])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid naming strategy. Choose from: %s',
                json_encode(array_keys($this->namingStrategies))
            ));
        }

        $this->namingStategy = [$this, $this->namingStrategies[$namingStrategy]];
    }

    public function register($className, ?array $parameters = null, $description = '')
    {
        if (!isset($this->classes[$className])) {
            $this->classes[$className] = [];
        }

        $id = call_user_func_array($this->namingStategy, [$className]);

        if (isset($this->models[$id])) {
            return $id;
        }

        $this->classes[$className][] = $id;

        $model = [
            'id' => $id,
            'description' => $description,
        ];

        if (is_array($parameters)) {
            $required = [];
            $properties = [];

            foreach ($parameters as $name => $prop) {
                $subParam = [];

                if (DataTypes::MODEL === $prop['actualType']) {
                    $subParam['$ref'] = $this->register(
                        $prop['subType'],
                        $prop['children'] ?? null,
                        $prop['description'] ?: $prop['dataType']
                    );
                } else {
                    $type = null;
                    $format = null;
                    $items = null;
                    $enum = null;
                    $ref = null;

                    if (isset($this->typeMap[$prop['actualType']])) {
                        $type = $this->typeMap[$prop['actualType']];
                    } else {
                        switch ($prop['actualType']) {
                            case DataTypes::ENUM:
                                $type = 'string';
                                if (isset($prop['format'])) {
                                    $enum = array_keys(json_decode($prop['format'], true));
                                }
                                break;

                            case DataTypes::COLLECTION:
                                $type = 'array';

                                if (null === $prop['subType']) {
                                    $items = [
                                        'type' => 'string',
                                    ];
                                } elseif (isset($this->typeMap[$prop['subType']])) {
                                    $items = [
                                        'type' => $this->typeMap[$prop['subType']],
                                    ];
                                } elseif (!isset($this->typeMap[$prop['subType']])) {
                                    $items = [
                                        '$ref' => $this->register(
                                            $prop['subType'],
                                            $prop['children'] ?? null,
                                            $prop['description'] ?: $prop['dataType']
                                        ),
                                    ];
                                }
                                /* @TODO: Handle recursion if subtype is a model. */
                                break;

                            case DataTypes::MODEL:
                                $ref = $this->register(
                                    $prop['subType'],
                                    $prop['children'] ?? null,
                                    $prop['description'] ?: $prop['dataType']
                                );

                                $type = $ref;
                                /* @TODO: Handle recursion. */
                                break;
                        }
                    }

                    if (isset($this->formatMap[$prop['actualType']])) {
                        $format = $this->formatMap[$prop['actualType']];
                    }

                    $subParam = [
                        'type' => $type,
                        'description' => false === empty($prop['description']) ? (string) $prop['description'] : $prop['dataType'],
                    ];

                    if (null !== $format) {
                        $subParam['format'] = $format;
                    }

                    if (null !== $enum) {
                        $subParam['enum'] = $enum;
                    }

                    if (null !== $ref) {
                        $subParam['$ref'] = $ref;
                    }

                    if (null !== $items) {
                        $subParam['items'] = $items;
                    }

                    if ($prop['required']) {
                        $required[] = $name;
                    }
                }

                $properties[$name] = $subParam;
            }

            $model['properties'] = $properties;
            $model['required'] = $required;
            $this->models[$id] = $model;
        }

        return $id;
    }

    public function nameDotNotation($className)
    {
        /*
         * Converts \Fully\Qualified\Class\Name to Fully.Qualified.Class.Name
         * "[...]" in aliased and non-aliased collections preserved.
         */
        $id = preg_replace('#(\\\|[^A-Za-z0-9\[\]])#', '.', $className);
        // Replace duplicate dots.
        $id = preg_replace('/\.+/', '.', $id);
        // Replace trailing dots.
        $id = preg_replace('/^\./', '', $id);

        return $id;
    }

    public function nameLastSegmentOnly($className)
    {
        /*
         * Converts \Fully\Qualified\ClassName to ClassName
         */
        $segments = explode('\\', $className);
        $id = end($segments);

        return $id;
    }

    public function getModels()
    {
        return $this->models;
    }

    public function clear(): void
    {
        $this->models = [];
        $this->classes = [];
    }
}

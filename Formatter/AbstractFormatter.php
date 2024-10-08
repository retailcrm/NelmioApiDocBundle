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

use Nelmio\ApiDocBundle\Attribute\ApiDoc;
use Nelmio\ApiDocBundle\DataTypes;

abstract class AbstractFormatter implements FormatterInterface
{
    protected $version;

    public function setVersion($version): void
    {
        $this->version = $version;
    }

    public function formatOne(ApiDoc $annotation)
    {
        return $this->renderOne(
            $this->processAnnotation($annotation->toArray())
        );
    }

    public function format(array $collection)
    {
        return $this->render(
            $this->processCollection($collection)
        );
    }

    /**
     * Format a single array of data
     *
     * @return string|array
     */
    abstract protected function renderOne(array $data);

    /**
     * Format a set of resource sections.
     *
     * @return string|array
     */
    abstract protected function render(array $collection);

    /**
     * Check that the versions range includes current version
     *
     * @param string $fromVersion (default: null)
     * @param string $toVersion   (default: null)
     *
     * @return bool
     */
    protected function rangeIncludesVersion($fromVersion = null, $toVersion = null)
    {
        if (!$fromVersion && !$toVersion) {
            return true;
        }
        if ($fromVersion && version_compare($fromVersion, $this->version, '>')) {
            return false;
        }
        if ($toVersion && version_compare($toVersion, $this->version, '<')) {
            return false;
        }

        return true;
    }

    /**
     * Compresses nested parameters into a flat by changing the parameter
     * names to strings which contain the nested property names, for example:
     * `user[group][name]`
     *
     * @param string $parentName
     * @param bool   $ignoreNestedReadOnly
     *
     * @return array
     */
    protected function compressNestedParameters(array $data, $parentName = null, $ignoreNestedReadOnly = false)
    {
        $newParams = [];
        foreach ($data as $name => $info) {
            if ($this->version && !$this->rangeIncludesVersion(
                $info['sinceVersion'] ?? null,
                $info['untilVersion'] ?? null
            )) {
                continue;
            }

            $newName = $this->getNewName($name, $info, $parentName);

            $newParams[$newName] = [
                'dataType' => $info['dataType'],
                'readonly' => array_key_exists('readonly', $info) ? $info['readonly'] : null,
                'required' => $info['required'],
                'default' => array_key_exists('default', $info) ? $info['default'] : null,
                'description' => array_key_exists('description', $info) ? $info['description'] : null,
                'format' => array_key_exists('format', $info) ? $info['format'] : null,
                'sinceVersion' => array_key_exists('sinceVersion', $info) ? $info['sinceVersion'] : null,
                'untilVersion' => array_key_exists('untilVersion', $info) ? $info['untilVersion'] : null,
                'actualType' => array_key_exists('actualType', $info) ? $info['actualType'] : null,
                'subType' => array_key_exists('subType', $info) ? $info['subType'] : null,
                'parentClass' => array_key_exists('parentClass', $info) ? $info['parentClass'] : null,
                'field' => array_key_exists('field', $info) ? $info['field'] : null,
            ];

            if (isset($info['children']) && (!$info['readonly'] || !$ignoreNestedReadOnly)) {
                foreach ($this->compressNestedParameters($info['children'], $newName, $ignoreNestedReadOnly) as $nestedItemName => $nestedItemData) {
                    $newParams[$nestedItemName] = $nestedItemData;
                }
            }
        }

        return $newParams;
    }

    /**
     * Returns a new property name, taking into account whether or not the property
     * is an array of some other data type.
     *
     * @param string $name
     * @param array  $data
     * @param string $parentName
     *
     * @return string
     */
    protected function getNewName($name, $data, $parentName = null)
    {
        $array = '';
        $newName = ($parentName) ? sprintf('%s[%s]', $parentName, $name) : $name;

        if (isset($data['actualType']) && DataTypes::COLLECTION == $data['actualType']
            && isset($data['subType']) && null !== $data['subType']
        ) {
            $array = '[]';
        }

        return sprintf('%s%s', $newName, $array);
    }

    /**
     * @param array $annotation
     *
     * @return array
     */
    protected function processAnnotation($annotation)
    {
        if (isset($annotation['parameters'])) {
            $annotation['parameters'] = $this->compressNestedParameters($annotation['parameters'], null, true);
        }

        if (isset($annotation['response'])) {
            $annotation['response'] = $this->compressNestedParameters($annotation['response']);
        }

        if (isset($annotation['parsedResponseMap'])) {
            foreach ($annotation['parsedResponseMap'] as $statusCode => &$data) {
                $data['model'] = $this->compressNestedParameters($data['model']);
            }
        }

        $annotation['id'] = strtolower($annotation['method'] ?? '') . '-' . str_replace('/', '-', $annotation['uri'] ?? '');

        return $annotation;
    }

    /**
     * @param  array[ApiDoc] $collection
     *
     * @return array
     */
    protected function processCollection(array $collection)
    {
        $array = [];
        foreach ($collection as $coll) {
            $array[$coll['annotation']->getSection()][$coll['resource']][] = $coll['annotation']->toArray();
        }

        $processedCollection = [];
        foreach ($array as $section => $resources) {
            foreach ($resources as $path => $annotations) {
                foreach ($annotations as $annotation) {
                    if ($section) {
                        $processedCollection[$section][$path][] = $this->processAnnotation($annotation);
                    } else {
                        $processedCollection['_others'][$path][] = $this->processAnnotation($annotation);
                    }
                }
            }
        }

        ksort($processedCollection);

        return $processedCollection;
    }
}

<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Parser;

use Nelmio\ApiDocBundle\DataTypes;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\MetadataFactoryInterface as LegacyMetadataFactoryInterface;

/**
 * Uses the Symfony Validation component to extract information about API objects.
 */
class ValidationParser implements ParserInterface, PostParserInterface
{
    /**
     * @var LegacyMetadataFactoryInterface
     */
    protected $factory;

    protected $typeMap = [
        'integer' => DataTypes::INTEGER,
        'int' => DataTypes::INTEGER,
        'scalar' => DataTypes::STRING,
        'numeric' => DataTypes::INTEGER,
        'boolean' => DataTypes::BOOLEAN,
        'string' => DataTypes::STRING,
        'float' => DataTypes::FLOAT,
        'double' => DataTypes::FLOAT,
        'long' => DataTypes::INTEGER,
        'object' => DataTypes::MODEL,
        'array' => DataTypes::COLLECTION,
        'DateTime' => DataTypes::DATETIME,
    ];

    /**
     * Requires a validation MetadataFactory.
     *
     * @param MetadataFactoryInterface|LegacyMetadataFactoryInterface $factory
     */
    public function __construct($factory)
    {
        if (!($factory instanceof MetadataFactoryInterface) && !($factory instanceof LegacyMetadataFactoryInterface)) {
            throw new \InvalidArgumentException('Argument 1 of %s constructor must be either an instance of Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface or Symfony\Component\Validator\MetadataFactoryInterface.');
        }
        $this->factory = $factory;
    }

    public function supports(array $input)
    {
        $className = $input['class'];

        return $this->factory->hasMetadataFor($className);
    }

    public function parse(array $input)
    {
        $className = $input['class'];

        $groups = [];
        if (isset($input['groups']) && $input['groups']) {
            $groups = $input['groups'];
        }

        $parsed = $this->doParse($className, [], $groups);

        if (!isset($input['name']) || empty($input['name'])) {
            return $parsed;
        }

        if ($className && class_exists($className)) {
            $parts = explode('\\', $className);
            $dataType = sprintf('object (%s)', end($parts));
        } else {
            $dataType = sprintf('object (%s)', $className);
        }

        return [
            $input['name'] => [
                'dataType' => $dataType,
                'actualType' => DataTypes::MODEL,
                'class' => $className,
                'subType' => $dataType,
                'required' => null,
                'readonly' => null,
                'children' => $parsed,
                'default' => null,
            ],
        ];
    }

    /**
     * Recursively parse constraints.
     *
     * @return array
     */
    protected function doParse($className, array $visited, array $groups = [])
    {
        $params = [];
        $classdata = $this->factory->getMetadataFor($className);
        $properties = $classdata->getConstrainedProperties();

        $refl = $classdata->getReflectionClass();
        $defaults = $refl->getDefaultProperties();

        foreach ($properties as $property) {
            $vparams = [];

            $vparams['default'] = $defaults[$property] ?? null;

            $pds = $classdata->getPropertyMetadata($property);
            foreach ($pds as $propdata) {
                $constraints = $propdata->getConstraints();

                foreach ($constraints as $constraint) {
                    $vparams = $this->parseConstraint($constraint, $vparams, $className, $visited, $groups);
                }
            }

            if (isset($vparams['format'])) {
                $vparams['format'] = implode(', ', array_unique($vparams['format']));
            }

            foreach (['dataType', 'readonly', 'required', 'subType'] as $reqprop) {
                if (!isset($vparams[$reqprop])) {
                    $vparams[$reqprop] = null;
                }
            }

            // check for nested classes with All constraint
            if (isset($vparams['class']) && !in_array($vparams['class'], $visited) && null !== $this->factory->getMetadataFor($vparams['class'])) {
                $visited[] = $vparams['class'];
                $vparams['children'] = $this->doParse($vparams['class'], $visited, $groups);
            }

            $vparams['actualType'] = $vparams['actualType'] ?? DataTypes::STRING;

            $params[$property] = $vparams;
        }

        return $params;
    }

    public function postParse(array $input, array $parameters)
    {
        $groups = [];
        if (isset($input['groups']) && $input['groups']) {
            $groups = $input['groups'];
        }

        foreach ($parameters as $param => $data) {
            if (isset($data['class']) && isset($data['children'])) {
                $input = ['class' => $data['class'], 'groups' => $groups];
                $parameters[$param]['children'] = array_merge(
                    $parameters[$param]['children'], $this->postParse($input, $parameters[$param]['children'])
                );
                $parameters[$param]['children'] = array_merge(
                    $parameters[$param]['children'], $this->parse($input, $parameters[$param]['children'])
                );
            }
        }

        return $parameters;
    }

    /**
     * Create a valid documentation parameter based on an individual validation Constraint.
     * Currently supports:
     *  - NotBlank/NotNull
     *  - Type
     *  - Email
     *  - Url
     *  - Ip
     *  - Length (min and max)
     *  - Choice (single and multiple, min and max)
     *  - Regex (match and non-match)
     *
     * @param Constraint $constraint The constraint metadata object.
     * @param array      $vparams    The existing validation parameters.
     * @param array      $groups     Validation groups.
     *
     * @return mixed The parsed list of validation parameters.
     */
    protected function parseConstraint(
        Constraint $constraint,
        $vparams,
        $className,
        &$visited = [],
        array $groups = [],
    ) {
        $class = substr($constraint::class, strlen('Symfony\\Component\\Validator\\Constraints\\'));

        $vparams['actualType'] = DataTypes::STRING;
        $vparams['subType'] = null;
        $vparams['groups'] = $constraint->groups;

        if ($groups) {
            $containGroup = false;
            foreach ($groups as $group) {
                if (in_array($group, $vparams['groups'])) {
                    $containGroup = true;
                }
            }
            if (!$containGroup) {
                return $vparams;
            }
        }

        switch ($class) {
            case 'NotBlank':
                $vparams['format'][] = '{not blank}';
                // no break
            case 'NotNull':
                $vparams['required'] = true;
                break;
            case 'Type':
                if (isset($this->typeMap[$constraint->type])) {
                    $vparams['actualType'] = $this->typeMap[$constraint->type];
                }
                $vparams['dataType'] = $constraint->type;
                break;
            case 'Email':
                $vparams['format'][] = '{email address}';
                break;
            case 'Url':
                $vparams['format'][] = '{url}';
                break;
            case 'Ip':
                $vparams['format'][] = '{ip address}';
                break;
            case 'Date':
                $vparams['format'][] = '{Date YYYY-MM-DD}';
                $vparams['actualType'] = DataTypes::DATE;
                break;
            case 'DateTime':
                $vparams['format'][] = '{DateTime YYYY-MM-DD HH:MM:SS}';
                $vparams['actualType'] = DataTypes::DATETIME;
                break;
            case 'Time':
                $vparams['format'][] = '{Time HH:MM:SS}';
                $vparams['actualType'] = DataTypes::TIME;
                break;
            case 'Range':
                $messages = [];
                if (isset($constraint->min)) {
                    $messages[] = ">={$constraint->min}";
                }
                if (isset($constraint->max)) {
                    $messages[] = "<={$constraint->max}";
                }
                $vparams['format'][] = '{range: {' . implode(', ', $messages) . '}}';
                break;
            case 'Length':
                $messages = [];
                if (isset($constraint->min)) {
                    $messages[] = "min: {$constraint->min}";
                }
                if (isset($constraint->max)) {
                    $messages[] = "max: {$constraint->max}";
                }
                $vparams['format'][] = '{length: {' . implode(', ', $messages) . '}}';
                break;
            case 'Choice':
                $choices = $this->getChoices($constraint, $className);
                sort($choices);
                $format = '[' . implode('|', $choices) . ']';
                if ($constraint->multiple) {
                    $vparams['actualType'] = DataTypes::COLLECTION;
                    $vparams['subType'] = DataTypes::ENUM;
                    $messages = [];
                    if (isset($constraint->min)) {
                        $messages[] = "min: {$constraint->min} ";
                    }
                    if (isset($constraint->max)) {
                        $messages[] = "max: {$constraint->max} ";
                    }
                    $vparams['format'][] = '{' . implode('', $messages) . 'choice of ' . $format . '}';
                } else {
                    $vparams['actualType'] = DataTypes::ENUM;
                    $vparams['format'][] = $format;
                }
                break;
            case 'Regex':
                if ($constraint->match) {
                    $vparams['format'][] = '{match: ' . $constraint->pattern . '}';
                } else {
                    $vparams['format'][] = '{not match: ' . $constraint->pattern . '}';
                }
                break;
            case 'All':
                foreach ($constraint->constraints as $childConstraint) {
                    if ($childConstraint instanceof Type) {
                        $nestedType = $childConstraint->type;
                        $exp = explode('\\', $nestedType);
                        if (!$nestedType || !class_exists($nestedType)) {
                            $nestedType = substr($className, 0, strrpos($className, '\\') + 1) . $nestedType;

                            if (!class_exists($nestedType)) {
                                continue;
                            }
                        }

                        $vparams['dataType'] = sprintf('array of objects (%s)', end($exp));
                        $vparams['actualType'] = DataTypes::COLLECTION;
                        $vparams['subType'] = $nestedType;
                        $vparams['class'] = $nestedType;

                        if (!in_array($nestedType, $visited)) {
                            $visited[] = $nestedType;
                            $vparams['children'] = $this->doParse($nestedType, $visited);
                        }
                    }
                }
                break;
        }

        return $vparams;
    }

    /**
     * Return Choice constraint choices.
     *
     * @return array
     *
     * @throws ConstraintDefinitionException
     */
    protected function getChoices(Constraint $constraint, $className)
    {
        if ($constraint->callback) {
            if (is_callable([$className, $constraint->callback])) {
                $choices = call_user_func([$className, $constraint->callback]);
            } elseif (is_callable($constraint->callback)) {
                $choices = call_user_func($constraint->callback);
            } else {
                throw new ConstraintDefinitionException('The Choice constraint expects a valid callback');
            }
        } else {
            $choices = $constraint->choices;
        }

        return $choices;
    }
}

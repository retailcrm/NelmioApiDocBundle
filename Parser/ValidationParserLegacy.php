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

use Symfony\Component\Validator\Mapping\ClassMetadataFactoryInterface;

/**
 * Uses the Symfony Validation component to extract information about API objects. This is a backwards-compatible Validation component for Symfony2.1
 */
class ValidationParserLegacy extends ValidationParser
{
    /**
     * @var ClassMetadataFactoryInterface
     */
    protected $factory;

    /**
     * Requires a validation MetadataFactory.
     *
     * @param MetadataFactoryInterface $factory
     */
    public function __construct(ClassMetadataFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function supports(array $input)
    {
        $className = $input['class'];

        return null !== $this->factory->getClassMetadata($className);
    }

    public function parse(array $input)
    {
        $params = [];
        $className = $input['class'];

        $classdata = $this->factory->getClassMetadata($className);

        $properties = $classdata->getConstrainedProperties();

        $refl = $classdata->getReflectionClass();
        $defaults = $refl->getDefaultProperties();

        foreach ($properties as $property) {
            $vparams = [];

            $vparams['default'] = $defaults[$property] ?? null;

            $pds = $classdata->getMemberMetadatas($property);

            foreach ($pds as $propdata) {
                $constraints = $propdata->getConstraints();

                foreach ($constraints as $constraint) {
                    $vparams = $this->parseConstraint($constraint, $vparams, $className);
                }
            }

            if (isset($vparams['format'])) {
                $vparams['format'] = implode(', ', $vparams['format']);
            }

            foreach (['dataType', 'readonly', 'required'] as $reqprop) {
                if (!isset($vparams[$reqprop])) {
                    $vparams[$reqprop] = null;
                }
            }

            $params[$property] = $vparams;
        }

        return $params;
    }
}

<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Fixtures\Form;

use Nelmio\ApiDocBundle\Util\LegacyFormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SimpleType
 *
 * @author Bez Hermoso <bez@activelamp.com>
 */
class SimpleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('a', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\TextType'), [
            'description' => 'Something that describes A.',
        ])
        ->add('b', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\NumberType'))
        ->add('c', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\ChoiceType'),
            ['choices' => ['X' => 'x', 'Y' => 'y', 'Z' => 'z']]
        )
        ->add('d', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\DateTimeType'))
        ->add('e', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\DateType'))
        ->add('g', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\TextareaType'))
        ;
    }

    /**
     * BC SF < 2.8
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'simple';
    }
}

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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('a', null, ['description' => 'A nice description'])
            ->add('b')
            ->add($builder->create('c', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\CheckboxType')))
            ->add('d', LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\TextType'), ['data' => 'DefaultTest'])
        ;
    }

    /**
     * @deprecated Remove it when bumping requirements to Symfony 2.7+
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $this->configureOptions($resolver);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\Test',
        ]);

        return;
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
        return '';
    }
}

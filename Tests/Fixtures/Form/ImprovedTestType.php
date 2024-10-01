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
use Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ImprovedTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choiceType = LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\ChoiceType');
        $datetimeType = LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\DateTimeType');
        $dateType = LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\DateType');

        $builder
            ->add('dt1', $datetimeType, ['widget' => 'single_text', 'description' => 'A nice description'])
            ->add('dt2', $datetimeType, ['date_format' => 'M/d/y', 'html5' => false])
            ->add('dt3', $datetimeType, ['widget' => 'single_text', 'format' => 'M/d/y H:i:s', 'html5' => false])
            ->add('dt4', $datetimeType, ['date_format' => \IntlDateFormatter::MEDIUM])
            ->add('dt5', $datetimeType, ['format' => 'M/d/y H:i:s', 'html5' => false])
            ->add('d1', $dateType, ['format' => \IntlDateFormatter::MEDIUM])
            ->add('d2', $dateType, ['format' => 'd-M-y'])
            ->add('c1', $choiceType, ['choices' => ['Male' => 'm', 'Female' => 'f']])
            ->add('c2', $choiceType, ['choices' => ['Male' => 'm', 'Female' => 'f'], 'multiple' => true])
            ->add('c3', $choiceType, ['choices' => []])
            ->add('c4', $choiceType, ['choices' => ['bar' => 'foo', 'bazgroup' => ['Buzz' => 'baz']]])
            ->add('e1', LegacyFormHelper::isLegacy() ? new EntityType() : __NAMESPACE__ . '\EntityType',
                LegacyFormHelper::isLegacy()
                    ? ['choice_list' => new SimpleChoiceList(['bar' => 'foo', 'bazgroup' => ['Buzz' => 'baz']])]
                    : ['choices' => ['bar' => 'foo', 'bazgroup' => ['Buzz' => 'baz']]]
            )
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
            'data_class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\ImprovedTest',
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

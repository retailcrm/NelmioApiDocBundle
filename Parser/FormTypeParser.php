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
use Nelmio\ApiDocBundle\Util\LegacyFormHelper;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormTypeParser implements ParserInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var \Symfony\Component\Form\FormRegistry
     */
    protected $formRegistry;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var bool
     */
    protected $entityToChoice;

    /**
     * @var array|FormInfoParser[]
     */
    protected $formInfoParsers = [];

    /**
     * @var array
     *
     * @deprecated since 2.12, to be removed in 3.0. Use $extendedMapTypes instead.
     */
    protected $mapTypes = [
        'text' => DataTypes::STRING,
        'date' => DataTypes::DATE,
        'datetime' => DataTypes::DATETIME,
        'checkbox' => DataTypes::BOOLEAN,
        'time' => DataTypes::TIME,
        'number' => DataTypes::FLOAT,
        'integer' => DataTypes::INTEGER,
        'textarea' => DataTypes::STRING,
        'country' => DataTypes::STRING,
        'choice' => DataTypes::ENUM,
        'file' => DataTypes::FILE,
    ];

    /**
     * @var array
     */
    protected $extendedMapTypes = [
        DataTypes::STRING => [
            'text',
            'Symfony\Component\Form\Extension\Core\Type\TextType',
            'textarea',
            'Symfony\Component\Form\Extension\Core\Type\TextareaType',
            'country',
            'Symfony\Component\Form\Extension\Core\Type\CountryType',
        ],
        DataTypes::DATE => [
            'date',
            'Symfony\Component\Form\Extension\Core\Type\DateType',
        ],
        DataTypes::DATETIME => [
            'datetime',
            'Symfony\Component\Form\Extension\Core\Type\DatetimeType',
        ],
        DataTypes::BOOLEAN => [
            'checkbox',
            'Symfony\Component\Form\Extension\Core\Type\CheckboxType',
        ],
        DataTypes::TIME => [
            'time',
            'Symfony\Component\Form\Extension\Core\Type\TimeType',
        ],
        DataTypes::FLOAT => [
            'number',
            'Symfony\Component\Form\Extension\Core\Type\NumberType',
        ],
        DataTypes::INTEGER => [
            'integer',
            'Symfony\Component\Form\Extension\Core\Type\IntegerType',
        ],
        DataTypes::ENUM => [
            'choice',
            'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
        ],
        DataTypes::FILE => [
            'file',
            'Symfony\Component\Form\Extension\Core\Type\FileType',
        ],
    ];

    public function __construct(FormFactoryInterface $formFactory, TranslatorInterface $translator, $entityToChoice)
    {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->entityToChoice = (bool) $entityToChoice;
    }

    public function supports(array $item)
    {
        $className = $item['class'];
        $options = $item['options'];

        try {
            if ($this->createForm($className, null, $options)) {
                return true;
            }
        } catch (FormException $e) {
            return false;
        } catch (MissingOptionsException $e) {
            return false;
        }

        return false;
    }

    public function parse(array $item)
    {
        $type = $item['class'];
        $options = $item['options'];

        try {
            $form = $this->formFactory->create($type, null, $options);
        }
        // TODO: find a better exception to catch
        catch (\Exception $exception) {
            if (!LegacyFormHelper::isLegacy()) {
                @trigger_error('Using FormTypeInterface instance with required arguments without defining them as service is deprecated in symfony 2.8 and removed in 3.0.', E_USER_DEPRECATED);
            }
        }

        if (!isset($form)) {
            if (!LegacyFormHelper::hasBCBreaks() && $this->implementsType($type)) {
                $type = $this->getTypeInstance($type);
                $form = $this->formFactory->create($type, null, $options);
            } else {
                throw new \InvalidArgumentException('Unsupported form type class.');
            }
        }

        $name = array_key_exists('name', $item)
            ? $item['name']
            : (method_exists($form, 'getBlockPrefix') ? $form->getBlockPrefix() : $form->getName());

        if (empty($name)) {
            return $this->parseForm($form);
        }

        $subType = is_object($type) ? $type::class : $type;

        if ($subType && class_exists($subType)) {
            $parts = explode('\\', $subType);
            $dataType = sprintf('object (%s)', end($parts));
        } else {
            $dataType = sprintf('object (%s)', $subType);
        }

        return [
            $name => [
                'required' => true,
                'readonly' => false,
                'description' => '',
                'default' => null,
                'dataType' => $dataType,
                'actualType' => DataTypes::MODEL,
                'subType' => $subType,
                'children' => $this->parseForm($form),
            ],
        ];
    }

    public function addFormInfoParser(FormInfoParser $formInfoParser): void
    {
        $class = $formInfoParser::class;
        if (isset($this->formInfoParsers[$class])) {
            throw new \InvalidArgumentException($class . ' already added');
        }

        $this->formInfoParsers[$class] = $formInfoParser;
    }

    private function parseFormType(FormTypeInterface $type, FormConfigInterface $config): ?array
    {
        foreach ($this->formInfoParsers as $parser) {
            $customInfo = $parser->parseFormType($type, $config);
            if ($customInfo) {
                return $customInfo;
            }
        }

        return null;
    }

    private function getDataType($type)
    {
        foreach ($this->extendedMapTypes as $data => $types) {
            if (in_array($type, $types)) {
                return $data;
            }
        }
    }

    private function parseForm($form)
    {
        $parameters = [];
        $domain = $form->getConfig()->getOption('translation_domain');

        foreach ($form as $name => $child) {
            $config = $child->getConfig();
            $options = $config->getOptions();
            $bestType = '';
            $actualType = null;
            $subType = null;
            $children = null;

            for ($type = $config->getType();
                $type instanceof FormInterface || $type instanceof ResolvedFormTypeInterface;
                $type = $type->getParent()
            ) {
                $customInfo = $this->parseFormType($type->getInnerType(), $config);
                if ($customInfo) {
                    $parameters[$name] = array_merge([
                        'dataType' => 'string',
                        'actualType' => 'string',
                        'default' => $config->getData(),
                        'required' => $config->getRequired(),
                        'description' => $this->getFormDescription($config, $domain),
                        'readonly' => $config->getDisabled(),
                    ], $customInfo);

                    continue 2;
                }

                $typeName = method_exists($type, 'getBlockPrefix') ?
                    $type->getBlockPrefix() : $type->getName();

                $dataType = $this->getDataType($typeName);
                if (null !== $dataType) {
                    $actualType = $bestType = $dataType;
                } elseif ('collection' === $typeName) {
                    // BC sf < 2.8
                    $typeOption = $config->hasOption('entry_type') ? $config->getOption('entry_type') : $config->getOption('type');

                    if (is_object($typeOption)) {
                        $typeOption = method_exists($typeOption, 'getBlockPrefix') ?
                            $typeOption->getBlockPrefix() : $typeOption->getName();
                    }

                    $dataType = $this->getDataType($typeOption);
                    if (null !== $dataType) {
                        $subType = $dataType;
                        $actualType = DataTypes::COLLECTION;
                        $bestType = sprintf('array of %ss', $subType);
                    } else {
                        // Embedded form collection
                        // BC sf < 2.8
                        $embbededType = $config->hasOption('entry_type') ? $config->getOption('entry_type') : $config->getOption('type');
                        $subForm = $this->formFactory->create($embbededType, null, $config->getOption('entry_options', []));
                        $children = $this->parseForm($subForm);
                        $actualType = DataTypes::COLLECTION;
                        $subType = is_object($embbededType) ? $embbededType::class : $embbededType;

                        if ($subType && class_exists($subType)) {
                            $parts = explode('\\', $subType);
                            $bestType = sprintf('array of objects (%s)', end($parts));
                        } else {
                            $bestType = sprintf('array of objects (%s)', $subType);
                        }
                    }
                }
            }

            if ('' === $bestType) {
                if ($type = $config->getType()) {
                    if ($type = $type->getInnerType()) {
                        /**
                         * TODO: Implement a better handling of unsupported types
                         * This is just a temporary workaround for don't breaking docs page in case of unsupported types
                         * like the entity type https://github.com/nelmio/NelmioApiDocBundle/issues/94
                         */
                        $addDefault = false;
                        try {
                            if (isset($subForm)) {
                                unset($subForm);
                            }

                            if (LegacyFormHelper::hasBCBreaks()) {
                                try {
                                    $subForm = $this->formFactory->create($type::class, null, $options);
                                } catch (\Exception $e) {
                                }
                            }
                            if (!isset($subForm)) {
                                $subForm = $this->formFactory->create($type, null, $options);
                            }

                            $subParameters = $this->parseForm($subForm, $name);

                            if (!empty($subParameters)) {
                                $children = $subParameters;
                                $config = $subForm->getConfig();
                                $subType = $type::class;
                                $parts = explode('\\', $subType);
                                $bestType = sprintf('object (%s)', end($parts));

                                $parameters[$name] = [
                                    'dataType' => $bestType,
                                    'actualType' => DataTypes::MODEL,
                                    'default' => null,
                                    'subType' => $subType,
                                    'required' => $config->getRequired(),
                                    'description' => $this->getFormDescription($config, $domain),
                                    'readonly' => $config->getDisabled(),
                                    'children' => $children,
                                ];
                            } else {
                                $addDefault = true;
                            }
                        } catch (\Exception $e) {
                            $addDefault = true;
                        }

                        if ($addDefault) {
                            $parameters[$name] = [
                                'dataType' => 'string',
                                'actualType' => 'string',
                                'default' => $config->getData(),
                                'required' => $config->getRequired(),
                                'description' => $this->getFormDescription($config, $domain),
                                'readonly' => $config->getDisabled(),
                            ];
                        }

                        continue;
                    }
                }
            }

            $parameters[$name] = [
                'dataType' => $bestType,
                'actualType' => $actualType,
                'subType' => $subType,
                'default' => $config->getData(),
                'required' => $config->getRequired(),
                'description' => $this->getFormDescription($config, $domain),
                'readonly' => $config->getDisabled(),
            ];

            if (null !== $children) {
                $parameters[$name]['children'] = $children;
            }

            switch ($bestType) {
                case 'datetime':
                    if (($format = $config->getOption('date_format')) && is_string($format)) {
                        $parameters[$name]['format'] = $format;
                    } elseif ('single_text' == $config->getOption('widget') && $format = $config->getOption('format')) {
                        $parameters[$name]['format'] = $format;
                    }
                    break;

                case 'date':
                    if (($format = $config->getOption('format')) && is_string($format)) {
                        $parameters[$name]['format'] = $format;
                    }
                    break;

                case 'choice':
                    if ($config->getOption('multiple')) {
                        $parameters[$name]['dataType'] = sprintf('array of %ss', $parameters[$name]['dataType']);
                        $parameters[$name]['actualType'] = DataTypes::COLLECTION;
                        $parameters[$name]['subType'] = DataTypes::ENUM;
                    }

                    if (($choices = $config->getOption('choices')) && is_array($choices) && count($choices)) {
                        $choices = $config->getOption('choices_as_values') ?
                            array_values($choices) :
                            array_keys($choices);
                        sort($choices);
                        $parameters[$name]['format'] = '[' . implode('|', $choices) . ']';
                    } elseif ($choiceList = $config->getOption('choice_list')) {
                        $choiceListType = $config->getType();
                        $choiceListName = method_exists($choiceListType, 'getBlockPrefix') ?
                            $choiceListType->getBlockPrefix() : $choiceListType->getName();

                        if ('entity' === $choiceListName && false === $this->entityToChoice) {
                            $choices = [];
                        } else {
                            // TODO: fixme
                            // does not work since: https://github.com/symfony/symfony/commit/03efce1b568379eac21d880e427090e43035f505
                            $choices = [];
                        }

                        if (is_array($choices) && count($choices)) {
                            $parameters[$name]['format'] = json_encode($choices);
                        }
                    }
                    break;
            }
        }

        return $parameters;
    }

    private function implementsType($item)
    {
        if (null === $item || !class_exists($item)) {
            return false;
        }

        $refl = new \ReflectionClass($item);

        return $refl->implementsInterface('Symfony\Component\Form\FormTypeInterface') || $refl->implementsInterface('Symfony\Component\Form\ResolvedFormTypeInterface');
    }

    private function getTypeInstance($type)
    {
        $refl = new \ReflectionClass($type);
        $constructor = $refl->getConstructor();

        // this fallback may lead to runtime exception, but try hard to generate the docs
        if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            return $refl->newInstanceWithoutConstructor();
        }

        return $refl->newInstance();
    }

    private function createForm($type, $data = null, array $options = [])
    {
        try {
            return $this->formFactory->create($type, null, $options);
        } catch (InvalidArgumentException $exception) {
        }

        if (!LegacyFormHelper::hasBCBreaks() && !isset($form) && $this->implementsType($type)) {
            $type = $this->getTypeInstance($type);

            return $this->formFactory->create($type, null, $options);
        }
    }

    private function handleChoiceListValues(ChoiceListInterface $choiceList)
    {
        $choices = [];
        foreach ([$choiceList->getPreferredViews(), $choiceList->getRemainingViews()] as $viewList) {
            $choices = array_merge($choices, $this->handleChoiceViewsHierarchy($viewList));
        }

        return $choices;
    }

    private function handleChoiceViewsHierarchy(array $choiceViews)
    {
        $choices = [];
        foreach ($choiceViews as $item) {
            if ($item instanceof ChoiceView) {
                $choices[$item->value] = $item->label;
            } elseif (is_array($item)) {
                $choices = array_merge($choices, $this->handleChoiceViewsHierarchy($item));
            }
        }

        return $choices;
    }

    private function getFormDescription($config, $domain = null)
    {
        $description = ($config->getOption('description'))
            ?: $config->getOption('label');

        if (null != $description) {
            return $this->translator->trans($description, [], $domain);
        }

        return null;
    }
}

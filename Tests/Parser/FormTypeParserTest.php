<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NelmioApiDocBundle\Tests\Parser;

use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Form\Extension\DescriptionFormTypeExtension;
use Nelmio\ApiDocBundle\Parser\FormTypeParser;
use Nelmio\ApiDocBundle\Tests\Fixtures\Form\DependencyType;
use Nelmio\ApiDocBundle\Util\LegacyFormHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\Translation\Translator;

class FormTypeParserTest extends TestCase
{
    /**
     * @dataProvider dataTestParse
     */
    public function testParse($typeName, $expected): void
    {
        $resolvedTypeFactory = new ResolvedFormTypeFactory();
        $formFactoryBuilder = new FormFactoryBuilder();
        $formFactoryBuilder->setResolvedTypeFactory($resolvedTypeFactory);
        $formFactoryBuilder->addExtension(new CoreExtension());
        $formFactoryBuilder->addTypeExtension(new DescriptionFormTypeExtension());
        $formFactoryBuilder->addType(new DependencyType(['foo']));
        $formFactory = $formFactoryBuilder->getFormFactory();
        $formTypeParser = new FormTypeParser($formFactory, new Translator('en'), $entityToChoice = true);

        set_error_handler(['Nelmio\ApiDocBundle\Tests\WebTestCase', 'handleDeprecation']);
        @trigger_error('test', E_USER_DEPRECATED);

        $output = $formTypeParser->parse($typeName);
        restore_error_handler();

        $this->assertEquals($expected, $output);
    }

    /**
     * Checks that we can still use FormType with required arguments without defining them as services.
     *
     * @dataProvider dataTestParse
     */
    public function testLegacyParse($typeName, $expected): void
    {
        if (LegacyFormHelper::hasBCBreaks()) {
            $this->markTestSkipped('Not supported on symfony 3.0.');
        }

        $resolvedTypeFactory = new ResolvedFormTypeFactory();
        $formFactoryBuilder = new FormFactoryBuilder();
        $formFactoryBuilder->setResolvedTypeFactory($resolvedTypeFactory);
        $formFactoryBuilder->addExtension(new CoreExtension());
        $formFactoryBuilder->addTypeExtension(new DescriptionFormTypeExtension());
        $formFactory = $formFactoryBuilder->getFormFactory();
        $formTypeParser = new FormTypeParser($formFactory, new Translator('en'), $entityToChoice = true);

        set_error_handler(['Nelmio\ApiDocBundle\Tests\WebTestCase', 'handleDeprecation']);
        @trigger_error('test', E_USER_DEPRECATED);

        $output = $formTypeParser->parse($typeName);
        restore_error_handler();

        $this->assertEquals($expected, $output);
    }

    /**
     * @dataProvider dataTestParseWithoutEntity
     */
    public function testParseWithoutEntity($typeName, $expected): void
    {
        $resolvedTypeFactory = new ResolvedFormTypeFactory();
        $formFactoryBuilder = new FormFactoryBuilder();
        $formFactoryBuilder->setResolvedTypeFactory($resolvedTypeFactory);
        $formFactoryBuilder->addExtension(new CoreExtension());
        $formFactoryBuilder->addTypeExtension(new DescriptionFormTypeExtension());
        $formFactoryBuilder->addType(new DependencyType(['bar']));
        $formFactory = $formFactoryBuilder->getFormFactory();
        $formTypeParser = new FormTypeParser($formFactory, new Translator('en'), $entityToChoice = false);

        set_error_handler(['Nelmio\ApiDocBundle\Tests\WebTestCase', 'handleDeprecation']);
        @trigger_error('test', E_USER_DEPRECATED);

        $output = $formTypeParser->parse($typeName);
        restore_error_handler();

        $this->assertEquals($expected, $output);
    }

    public function dataTestParse()
    {
        return $this->expectedData(true);
    }

    public function dataTestParseWithoutEntity()
    {
        return $this->expectedData(false);
    }

    protected function expectedData($entityToChoice)
    {
        $entityData = array_merge(
            [
                'dataType' => 'choice',
                'actualType' => DataTypes::ENUM,
                'subType' => null,
                'default' => null,
                'required' => true,
                'description' => null,
                'readonly' => false,
            ],
            LegacyFormHelper::isLegacy() ? [] : ['format' => '[bar|bazgroup]']
        );

        return [
            [
                ['class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\TestType', 'options' => []],
                [
                    'a' => [
                        'dataType' => 'string',
                        'actualType' => DataTypes::STRING,
                        'subType' => null,
                        'required' => true,
                        'description' => 'A nice description',
                        'readonly' => false,
                        'default' => null,
                    ],
                    'b' => [
                        'dataType' => 'string',
                        'actualType' => DataTypes::STRING,
                        'subType' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'default' => null,
                    ],
                    'c' => [
                        'dataType' => 'boolean',
                        'actualType' => DataTypes::BOOLEAN,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                    ],
                    'd' => [
                        'dataType' => 'string',
                        'actualType' => DataTypes::STRING,
                        'subType' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'default' => 'DefaultTest',
                    ],
                ],
            ],
            [
                ['class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\CollectionType', 'options' => []],
                [
                    'collection_type' => [
                        'dataType' => 'object (CollectionType)',
                        'actualType' => DataTypes::MODEL,
                        'subType' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\CollectionType',
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'children' => [
                            'a' => [
                                'dataType' => 'array of strings',
                                'actualType' => DataTypes::COLLECTION,
                                'subType' => DataTypes::STRING,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                            'b' => [
                                'dataType' => 'array of objects (TestType)',
                                'actualType' => DataTypes::COLLECTION,
                                'subType' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\TestType',
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                                'children' => [
                                    'a' => [
                                        'dataType' => 'string',
                                        'actualType' => DataTypes::STRING,
                                        'default' => null,
                                        'subType' => null,
                                        'required' => true,
                                        'description' => 'A nice description',
                                        'readonly' => false,
                                    ],
                                    'b' => [
                                        'dataType' => 'string',
                                        'actualType' => DataTypes::STRING,
                                        'default' => null,
                                        'subType' => null,
                                        'required' => true,
                                        'description' => '',
                                        'readonly' => false,
                                    ],
                                    'c' => [
                                        'dataType' => 'boolean',
                                        'actualType' => DataTypes::BOOLEAN,
                                        'subType' => null,
                                        'default' => null,
                                        'required' => true,
                                        'description' => '',
                                        'readonly' => false,
                                    ],
                                    'd' => [
                                        'dataType' => 'string',
                                        'actualType' => DataTypes::STRING,
                                        'subType' => null,
                                        'required' => true,
                                        'description' => '',
                                        'readonly' => false,
                                        'default' => 'DefaultTest',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\CollectionType',
                    'name' => '',
                    'options' => [],
                ],
                [
                    'a' => [
                        'dataType' => 'array of strings',
                        'actualType' => DataTypes::COLLECTION,
                        'subType' => DataTypes::STRING,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                    ],
                    'b' => [
                        'dataType' => 'array of objects (TestType)',
                        'actualType' => DataTypes::COLLECTION,
                        'subType' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\TestType',
                        'required' => true,
                        'description' => '',
                        'default' => null,
                        'readonly' => false,
                        'children' => [
                            'a' => [
                                'dataType' => 'string',
                                'actualType' => DataTypes::STRING,
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => 'A nice description',
                                'readonly' => false,
                            ],
                            'b' => [
                                'dataType' => 'string',
                                'actualType' => DataTypes::STRING,
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                            'c' => [
                                'dataType' => 'boolean',
                                'actualType' => DataTypes::BOOLEAN,
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                            'd' => [
                                'dataType' => 'string',
                                'actualType' => DataTypes::STRING,
                                'subType' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                                'default' => 'DefaultTest',
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\CollectionType',
                    'name' => null,
                    'options' => [],
                ],
                [
                    'a' => [
                        'dataType' => 'array of strings',
                        'actualType' => DataTypes::COLLECTION,
                        'subType' => DataTypes::STRING,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                    ],
                    'b' => [
                        'dataType' => 'array of objects (TestType)',
                        'actualType' => DataTypes::COLLECTION,
                        'subType' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\TestType',
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'children' => [
                            'a' => [
                                'dataType' => 'string',
                                'actualType' => DataTypes::STRING,
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => 'A nice description',
                                'readonly' => false,
                            ],
                            'b' => [
                                'dataType' => 'string',
                                'actualType' => DataTypes::STRING,
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                            'c' => [
                                'dataType' => 'boolean',
                                'actualType' => DataTypes::BOOLEAN,
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                            'd' => [
                                'dataType' => 'string',
                                'actualType' => DataTypes::STRING,
                                'subType' => null,
                                'default' => 'DefaultTest',
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                ['class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\ImprovedTestType', 'options' => []],
                [
                    'dt1' => [
                        'dataType' => 'datetime',
                        'actualType' => DataTypes::DATETIME,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => 'A nice description',
                        'readonly' => false,
                        'format' => DateTimeType::HTML5_FORMAT,
                    ],
                    'dt2' => [
                        'dataType' => 'datetime',
                        'actualType' => DataTypes::DATETIME,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'format' => 'M/d/y',
                    ],
                    'dt3' => [
                        'dataType' => 'datetime',
                        'actualType' => DataTypes::DATETIME,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'format' => 'M/d/y H:i:s',
                    ],
                    'dt4' => [
                        'dataType' => 'datetime',
                        'actualType' => DataTypes::DATETIME,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                    ],
                    'dt5' => [
                        'dataType' => 'datetime',
                        'actualType' => DataTypes::DATETIME,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                    ],
                    'd1' => [
                        'dataType' => 'date',
                        'actualType' => DataTypes::DATE,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                    ],
                    'd2' => [
                        'dataType' => 'date',
                        'actualType' => DataTypes::DATE,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'format' => 'd-M-y',
                    ],
                    'c1' => [
                        'dataType' => 'choice',
                        'actualType' => DataTypes::ENUM,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'format' => '[Female|Male]',
                    ],
                    'c2' => [
                        'dataType' => 'array of choices',
                        'actualType' => DataTypes::COLLECTION,
                        'subType' => DataTypes::ENUM,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'format' => '[Female|Male]',
                    ],
                    'c3' => [
                        'dataType' => 'choice',
                        'actualType' => DataTypes::ENUM,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                    ],
                    'c4' => [
                        'dataType' => 'choice',
                        'actualType' => DataTypes::ENUM,
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => null,
                        'readonly' => false,
                        'format' => '[bar|bazgroup]',
                    ],
                    'e1' => $entityData,
                ],
            ],
            [
                ['class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\CompoundType', 'options' => []],
                [
                    'sub_form' => [
                        'dataType' => 'object (SimpleType)',
                        'actualType' => 'model',
                        'subType' => 'Nelmio\\ApiDocBundle\\Tests\\Fixtures\\Form\\SimpleType',
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'children' => [
                            'a' => [
                                'dataType' => 'string',
                                'actualType' => 'string',
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => 'Something that describes A.',
                                'readonly' => false,
                            ],
                            'b' => [
                                'dataType' => 'float',
                                'actualType' => 'float',
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                            'c' => [
                                'dataType' => 'choice',
                                'actualType' => 'choice',
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                                'format' => '[X|Y|Z]',
                            ],
                            'd' => [
                                'dataType' => 'datetime',
                                'actualType' => 'datetime',
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                            'e' => [
                                'dataType' => 'date',
                                'actualType' => 'date',
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                            'g' => [
                                'dataType' => 'string',
                                'actualType' => 'string',
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => '',
                                'readonly' => false,
                            ],
                        ],
                    ],
                    'a' => [
                        'dataType' => 'float',
                        'actualType' => 'float',
                        'subType' => null,
                        'default' => null,
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                    ],
                ],
            ],
            [
                ['class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\RequireConstructionType', 'options' => []],
                [
                    'require_construction_type' => [
                        'dataType' => 'object (RequireConstructionType)',
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'default' => null,
                        'actualType' => 'model',
                        'subType' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\RequireConstructionType',
                        'children' => [
                            'a' => [
                                'dataType' => 'string',
                                'actualType' => 'string',
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => 'A nice description',
                                'readonly' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                ['class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\DependencyType', 'options' => []],
                [
                    'dependency_type' => [
                        'dataType' => 'object (DependencyType)',
                        'required' => true,
                        'description' => '',
                        'readonly' => false,
                        'default' => null,
                        'actualType' => 'model',
                        'subType' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Form\DependencyType',
                        'children' => [
                            'a' => [
                                'dataType' => 'string',
                                'actualType' => 'string',
                                'subType' => null,
                                'default' => null,
                                'required' => true,
                                'description' => 'A nice description',
                                'readonly' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

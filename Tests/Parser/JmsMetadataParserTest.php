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

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Parser\JmsMetadataParser;
use PHPUnit\Framework\TestCase;

class JmsMetadataParserTest extends TestCase
{
    /**
     * @dataProvider dataTestParserWithNestedType
     */
    public function testParserWithNestedType($type): void
    {
        $metadataFactory = $this->createMock('Metadata\MetadataFactoryInterface');
        $docCommentExtractor = $this->getMockBuilder('Nelmio\ApiDocBundle\Util\DocCommentExtractor')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $propertyMetadataFoo = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'foo');
        $propertyMetadataFoo->type = [
            'name' => 'DateTime',
        ];

        $propertyMetadataBar = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'bar');
        $propertyMetadataBar->type = [
            'name' => 'string',
        ];

        $propertyMetadataBaz = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'baz');
        $propertyMetadataBaz->type = [
            'name' => $type,
            'params' => [
                [
                    'name' => 'integer',
                    'params' => [],
                ],
            ],
        ];

        $metadata = new ClassMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested');
        $metadata->addPropertyMetadata($propertyMetadataFoo);
        $metadata->addPropertyMetadata($propertyMetadataBar);
        $metadata->addPropertyMetadata($propertyMetadataBaz);

        $propertyNamingStrategy = $this->createMock('JMS\Serializer\Naming\PropertyNamingStrategyInterface');

        $propertyNamingStrategy
            ->expects($this->exactly(3))
            ->method('translateName')
            ->willReturnOnConsecutiveCalls(
                $this->returnValue('foo'),
                $this->returnValue('bar'),
                $this->returnValue('baz')
            )
        ;

        $input = 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested';

        $metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with($input)
            ->willReturn($metadata)
        ;

        $jmsMetadataParser = new JmsMetadataParser($metadataFactory, $propertyNamingStrategy, $docCommentExtractor);

        $output = $jmsMetadataParser->parse(
            [
                'class' => $input,
                'groups' => [],
            ]
        );

        $this->assertEquals(
            [
                'foo' => [
                    'dataType' => 'DateTime',
                    'actualType' => DataTypes::DATETIME,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'bar' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => 'baz',
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'baz' => [
                    'dataType' => 'array of integers',
                    'actualType' => DataTypes::COLLECTION,
                    'subType' => DataTypes::INTEGER,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
            ],
            $output
        );
    }

    public function testParserWithGroups(): void
    {
        $metadataFactory = $this->createMock('Metadata\MetadataFactoryInterface');
        $docCommentExtractor = $this->getMockBuilder('Nelmio\ApiDocBundle\Util\DocCommentExtractor')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $propertyMetadataFoo = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'foo');
        $propertyMetadataFoo->type = ['name' => 'string'];

        $propertyMetadataBar = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'bar');
        $propertyMetadataBar->type = ['name' => 'string'];
        $propertyMetadataBar->groups = ['Default', 'Special'];

        $propertyMetadataBaz = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'baz');
        $propertyMetadataBaz->type = ['name' => 'string'];
        $propertyMetadataBaz->groups = ['Special'];

        $input = 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested';

        $metadata = new ClassMetadata($input);
        $metadata->addPropertyMetadata($propertyMetadataFoo);
        $metadata->addPropertyMetadata($propertyMetadataBar);
        $metadata->addPropertyMetadata($propertyMetadataBaz);

        $metadataFactory->expects($this->any())
            ->method('getMetadataForClass')
            ->with($input)
            ->willReturn($metadata)
        ;

        $propertyNamingStrategy = new CamelCaseNamingStrategy();

        $jmsMetadataParser = new JmsMetadataParser($metadataFactory, $propertyNamingStrategy, $docCommentExtractor);

        // No group specified.
        $output = $jmsMetadataParser->parse(
            [
                'class' => $input,
                'groups' => [],
            ]
        );

        $this->assertEquals(
            [
                'foo' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'bar' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => 'baz',
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'baz' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
            ],
            $output
        );

        // Default group.
        $output = $jmsMetadataParser->parse(
            [
                'class' => $input,
                'groups' => ['Default'],
            ]
        );

        $this->assertEquals(
            [
                'foo' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'bar' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => 'baz',
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
            ],
            $output
        );

        // Special group.
        $output = $jmsMetadataParser->parse(
            [
                'class' => $input,
                'groups' => ['Special'],
            ]
        );

        $this->assertEquals(
            [
                'bar' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => 'baz',
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'baz' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
            ],
            $output
        );

        // Default + Special groups.
        $output = $jmsMetadataParser->parse(
            [
                'class' => $input,
                'groups' => ['Default', 'Special'],
            ]
        );

        $this->assertEquals(
            [
                'foo' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'bar' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => 'baz',
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'baz' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
            ],
            $output
        );
    }

    public function testNestedGroups(): void
    {
        $metadataFactory = $this->createMock('Metadata\MetadataFactoryInterface');
        $docCommentExtractor = $this->getMockBuilder('Nelmio\ApiDocBundle\Util\DocCommentExtractor')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $input = 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested';
        $nestedInput = 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest';

        $nestedPropertyMetadataHidden = new PropertyMetadata($nestedInput, 'hidden');
        $nestedPropertyMetadataHidden->type = ['name' => 'string'];
        $nestedPropertyMetadataHidden->groups = ['hidden'];

        $nestedPropertyMetadataFoo = new PropertyMetadata($nestedInput, 'foo');
        $nestedPropertyMetadataFoo->type = ['name' => 'string'];

        $nestedMetadata = new ClassMetadata($nestedInput);
        $nestedMetadata->addPropertyMetadata($nestedPropertyMetadataHidden);
        $nestedMetadata->addPropertyMetadata($nestedPropertyMetadataFoo);

        $propertyMetadataFoo = new PropertyMetadata($input, 'foo');
        $propertyMetadataFoo->type = ['name' => 'string'];

        $propertyMetadataBar = new PropertyMetadata($input, 'bar');
        $propertyMetadataBar->type = ['name' => 'string'];
        $propertyMetadataBar->groups = ['Default'];

        $propertyMetadataParent = new PropertyMetadata($input, 'parent');
        $propertyMetadataParent->type = ['name' => $nestedInput];
        $propertyMetadataParent->groups = ['hidden'];

        $metadata = new ClassMetadata($input);
        $metadata->addPropertyMetadata($propertyMetadataFoo);
        $metadata->addPropertyMetadata($propertyMetadataBar);
        $metadata->addPropertyMetadata($propertyMetadataParent);

        $metadataFactory->expects($this->any())
            ->method('getMetadataForClass')
            ->willReturnMap([
                [$input, $metadata],
                [$nestedInput, $nestedMetadata],
            ])
        ;

        $propertyNamingStrategy = new CamelCaseNamingStrategy();
        $jmsMetadataParser = new JmsMetadataParser($metadataFactory, $propertyNamingStrategy, $docCommentExtractor);

        // No group specified.
        $output = $jmsMetadataParser->parse(
            [
                'class' => $input,
                'groups' => ['hidden'],
            ]
        );

        $this->assertEquals(
            [
                'parent' => [
                    'dataType' => 'object (JmsTest)',
                    'actualType' => DataTypes::MODEL,
                    'subType' => $nestedInput,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                    'class' => $nestedInput,
                    'children' => [
                        'hidden' => [
                            'dataType' => 'string',
                            'actualType' => 'string',
                            'subType' => null,
                            'required' => false,
                            'default' => null,
                            'description' => null,
                            'readonly' => false,
                            'sinceVersion' => null,
                            'untilVersion' => null,
                        ],
                    ],
                ],
            ],
            $output
        );
    }

    public function testParserWithVersion(): void
    {
        $metadataFactory = $this->createMock('Metadata\MetadataFactoryInterface');
        $docCommentExtractor = $this->getMockBuilder('Nelmio\ApiDocBundle\Util\DocCommentExtractor')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $propertyMetadataFoo = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'foo');
        $propertyMetadataFoo->type = ['name' => 'string'];

        $propertyMetadataBar = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'bar');
        $propertyMetadataBar->type = ['name' => 'string'];
        $propertyMetadataBar->sinceVersion = '2.0';

        $propertyMetadataBaz = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested', 'baz');
        $propertyMetadataBaz->type = ['name' => 'string'];
        $propertyMetadataBaz->untilVersion = '3.0';

        $input = 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsNested';

        $metadata = new ClassMetadata($input);
        $metadata->addPropertyMetadata($propertyMetadataFoo);
        $metadata->addPropertyMetadata($propertyMetadataBar);
        $metadata->addPropertyMetadata($propertyMetadataBaz);

        $metadataFactory->expects($this->any())
            ->method('getMetadataForClass')
            ->with($input)
            ->willReturn($metadata)
        ;

        $propertyNamingStrategy = new CamelCaseNamingStrategy();

        $jmsMetadataParser = new JmsMetadataParser($metadataFactory, $propertyNamingStrategy, $docCommentExtractor);

        // No group specified.
        $output = $jmsMetadataParser->parse(
            [
                'class' => $input,
                'groups' => [],
            ]
        );

        $this->assertEquals(
            [
                'foo' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'bar' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => 'baz',
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => '2.0',
                    'untilVersion' => null,
                ],
                'baz' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => '3.0',
                ],
            ],
            $output
        );
    }

    public function testParserWithInline(): void
    {
        $metadataFactory = $this->createMock('Metadata\MetadataFactoryInterface');
        $docCommentExtractor = $this->getMockBuilder('Nelmio\ApiDocBundle\Util\DocCommentExtractor')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $propertyMetadataFoo = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsInline', 'foo');
        $propertyMetadataFoo->type = ['name' => 'string'];

        $propertyMetadataInline = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsInline', 'inline');
        $propertyMetadataInline->type = ['name' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest'];
        $propertyMetadataInline->inline = true;

        $input = 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsInline';

        $metadata = new ClassMetadata($input);
        $metadata->addPropertyMetadata($propertyMetadataFoo);
        $metadata->addPropertyMetadata($propertyMetadataInline);

        $propertyMetadataBar = new PropertyMetadata('Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest', 'bar');
        $propertyMetadataBar->type = ['name' => 'string'];

        $subInput = 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest';

        $subMetadata = new ClassMetadata($subInput);
        $subMetadata->addPropertyMetadata($propertyMetadataBar);

        $metadataFactory
            ->expects($this->exactly(3))
            ->method('getMetadataForClass')
            ->withConsecutive(
                [$input],
                [$subInput],
                [$subInput]
            )
            ->willReturnOnConsecutiveCalls(
                $metadata,
                $subMetadata,
                $subMetadata
            )
        ;
        $propertyNamingStrategy = new CamelCaseNamingStrategy();

        $jmsMetadataParser = new JmsMetadataParser($metadataFactory, $propertyNamingStrategy, $docCommentExtractor);

        $output = $jmsMetadataParser->parse(
            [
                'class' => $input,
                'groups' => [],
            ]
        );

        $this->assertEquals(
            [
                'foo' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
                'bar' => [
                    'dataType' => 'string',
                    'actualType' => DataTypes::STRING,
                    'subType' => null,
                    'default' => null,
                    'required' => false,
                    'description' => null,
                    'readonly' => false,
                    'sinceVersion' => null,
                    'untilVersion' => null,
                ],
            ],
            $output
        );
    }

    public function dataTestParserWithNestedType()
    {
        return [
            ['array'],
            ['ArrayCollection'],
        ];
    }
}

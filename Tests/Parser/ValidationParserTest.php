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
use Nelmio\ApiDocBundle\Parser\ValidationParser;
use Nelmio\ApiDocBundle\Parser\ValidationParserLegacy;
use Nelmio\ApiDocBundle\Tests\WebTestCase;
use Symfony\Component\HttpKernel\Kernel;

class ValidationParserTest extends WebTestCase
{
    protected $handler;
    private ValidationParser $parser;

    protected function setUp(): void
    {
        $container = $this->getContainer();

        if ($container->has('validator.mapping.class_metadata_factory')) {
            $factory = $container->get('validator.mapping.class_metadata_factory');
        } else {
            $factory = $container->get('validator');
        }

        if (version_compare(Kernel::VERSION, '2.2.0', '<')) {
            $this->parser = new ValidationParserLegacy($factory);
        } else {
            $this->parser = new ValidationParser($factory);
        }
    }

    /**
     * @dataProvider dataTestParser
     */
    public function testParser($property, $expected): void
    {
        $result = $this->parser->parse(['class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\ValidatorTest']);
        foreach ($expected as $name => $value) {
            $this->assertArrayHasKey($property, $result);
            $this->assertArrayHasKey($name, $result[$property]);
            $this->assertEquals($result[$property][$name], $expected[$name]);
        }
    }

    public function dataTestParser()
    {
        return [
            [
                'property' => 'length10',
                'expected' => [
                    'format' => '{length: {min: 10}}',
                    'default' => 'validate this',
                ],
            ],
            [
                'property' => 'length1to10',
                'expected' => [
                    'format' => '{length: {min: 1, max: 10}}',
                    'default' => null,
                ],
            ],
            [
                'property' => 'notblank',
                'expected' => [
                    'required' => true,
                    'default' => null,
                ],
            ],
            [
                'property' => 'notnull',
                'expected' => [
                    'required' => true,
                    'default' => null,
                ],
            ],
            [
                'property' => 'type',
                'expected' => [
                    'dataType' => 'DateTime',
                    'actualType' => DataTypes::DATETIME,
                    'default' => null,
                ],
            ],
            [
                'property' => 'date',
                'expected' => [
                    'format' => '{Date YYYY-MM-DD}',
                    'actualType' => DataTypes::DATE,
                    'default' => null,
                ],
            ],
            [
                'property' => 'dateTime',
                'expected' => [
                    'format' => '{DateTime YYYY-MM-DD HH:MM:SS}',
                    'actualType' => DataTypes::DATETIME,
                    'default' => null,
                ],
            ],
            [
                'property' => 'time',
                'expected' => [
                    'format' => '{Time HH:MM:SS}',
                    'actualType' => DataTypes::TIME,
                    'default' => null,
                ],
            ],
            [
                'property' => 'email',
                'expected' => [
                    'format' => '{email address}',
                    'default' => null,
                ],
            ],
            [
                'property' => 'url',
                'expected' => [
                    'format' => '{url}',
                    'default' => 'https://github.com',
                ],
            ],
            [
                'property' => 'ip',
                'expected' => [
                    'format' => '{ip address}',
                    'default' => null,
                ],
            ],
            [
                'property' => 'singlechoice',
                'expected' => [
                    'format' => '[a|b]',
                    'actualType' => DataTypes::ENUM,
                    'default' => null,
                ],
            ],
            [
                'property' => 'multiplechoice',
                'expected' => [
                    'format' => '{choice of [x|y|z]}',
                    'actualType' => DataTypes::COLLECTION,
                    'subType' => DataTypes::ENUM,
                    'default' => null,
                ],
            ],
            [
                'property' => 'multiplerangechoice',
                'expected' => [
                    'format' => '{min: 2 max: 3 choice of [bar|baz|foo|qux]}',
                    'actualType' => DataTypes::COLLECTION,
                    'subType' => DataTypes::ENUM,
                    'default' => null,
                ],
            ],
            [
                'property' => 'regexmatch',
                'expected' => [
                    'format' => '{match: /^\d{1,4}\w{1,4}$/}',
                    'default' => null,
                ],
            ],
            [
                'property' => 'regexnomatch',
                'expected' => [
                    'format' => '{not match: /\d/}',
                    'default' => null,
                ],
            ],
            [
                'property' => 'multipleassertions',
                'expected' => [
                    'required' => true,
                    'dataType' => 'string',
                    'format' => '{email address}',
                    'default' => null,
                ],
            ],
            [
                'property' => 'multipleformats',
                'expected' => [
                    'format' => '{url}, {length: {min: 10}}',
                    'default' => null,
                ],
            ],
        ];
    }
}

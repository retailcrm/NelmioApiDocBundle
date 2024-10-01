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

use Nelmio\ApiDocBundle\Parser\JsonSerializableParser;
use PHPUnit\Framework\TestCase;

class JsonSerializableParserTest extends TestCase
{
    /**
     * @var JsonSerializableParser
     */
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new JsonSerializableParser();
    }

    /**
     * @dataProvider dataTestParser
     */
    public function testParser($property, $expected): void
    {
        $result = $this->parser->parse(['class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JsonSerializableTest']);
        foreach ($expected as $name => $value) {
            $this->assertArrayHasKey($property, $result);
            $this->assertArrayHasKey($name, $result[$property]);
            $this->assertEquals($result[$property][$name], $expected[$name]);
        }
    }

    /**
     * @dataProvider dataTestSupports
     */
    public function testSupports($class, $expected): void
    {
        $this->assertEquals($this->parser->supports(['class' => $class]), $expected);
    }

    public function dataTestParser()
    {
        return [
            [
                'property' => 'id',
                'expected' => [
                    'dataType' => 'integer',
                    'default' => 123,
                ],
            ],
            [
                'property' => 'name',
                'expected' => [
                    'dataType' => 'string',
                    'default' => 'My name',
                ],
            ],
            [
                'property' => 'child',
                'expected' => [
                    'dataType' => 'object',
                    'children' => [
                        'value' => [
                            'dataType' => 'array',
                            'actualType' => 'array',
                            'subType' => null,
                            'required' => null,
                            'description' => null,
                            'readonly' => null,
                            'default' => null,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function dataTestSupports()
    {
        return [
            [
                'class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JsonSerializableTest',
                'expected' => true,
            ],
            [
                'class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JsonSerializableRequiredConstructorTest',
                'expected' => false,
            ],
            [
                'class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\JsonSerializableOptionalConstructorTest',
                'expected' => true,
            ],
            [
                'class' => 'Nelmio\ApiDocBundle\Tests\Fixtures\Model\Popo',
                'expected' => false,
            ],
        ];
    }
}

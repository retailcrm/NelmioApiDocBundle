<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Extractor;

use PHPUnit\Framework\TestCase;

class CollectionDirectiveTest extends TestCase
{
    /**
     * @var TestExtractor
     */
    private $testExtractor;

    protected function setUp(): void
    {
        $this->testExtractor = new TestExtractor();
    }

    private function normalize($input)
    {
        return $this->testExtractor->getNormalization($input);
    }

    /**
     * @dataProvider dataNormalizationTests
     */
    public function testNormalizations($input, callable $callable): void
    {
        call_user_func($callable, $this->normalize($input), $this);
    }

    public function dataNormalizationTests()
    {
        return [
            'test_simple_notation' => [
                'array<User>',
                function ($actual, TestCase $case): void {
                    $case->assertArrayHasKey('collection', $actual);
                    $case->assertArrayHasKey('collectionName', $actual);
                    $case->assertArrayHasKey('class', $actual);

                    $case->assertTrue($actual['collection']);
                    $case->assertEquals('', $actual['collectionName']);
                    $case->assertEquals('User', $actual['class']);
                },
            ],
            'test_simple_notation_with_namespaces' => [
                'array<Vendor0_2\\_Namespace1\\Namespace_2\\User>',
                function ($actual, TestCase $case): void {
                    $case->assertArrayHasKey('collection', $actual);
                    $case->assertArrayHasKey('collectionName', $actual);
                    $case->assertArrayHasKey('class', $actual);

                    $case->assertTrue($actual['collection']);
                    $case->assertEquals('', $actual['collectionName']);
                    $case->assertEquals('Vendor0_2\\_Namespace1\\Namespace_2\\User', $actual['class']);
                },
            ],
            'test_simple_named_collections' => [
                'array<Group> as groups',
                function ($actual, TestCase $case): void {
                    $case->assertArrayHasKey('collection', $actual);
                    $case->assertArrayHasKey('collectionName', $actual);
                    $case->assertArrayHasKey('class', $actual);

                    $case->assertTrue($actual['collection']);
                    $case->assertEquals('groups', $actual['collectionName']);
                    $case->assertEquals('Group', $actual['class']);
                },
            ],
            'test_namespaced_named_collections' => [
                'array<_Vendor\\Namespace0\\Namespace_2F3\\Group> as groups',
                function ($actual, TestCase $case): void {
                    $case->assertArrayHasKey('collection', $actual);
                    $case->assertArrayHasKey('collectionName', $actual);
                    $case->assertArrayHasKey('class', $actual);

                    $case->assertTrue($actual['collection']);
                    $case->assertEquals('groups', $actual['collectionName']);
                    $case->assertEquals('_Vendor\\Namespace0\\Namespace_2F3\\Group', $actual['class']);
                },
            ],
        ];
    }

    /**
     * @dataProvider dataInvalidDirectives
     */
    public function testInvalidDirectives($input): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->normalize($input);
    }

    public function dataInvalidDirectives()
    {
        return [
            ['array<>'],
            ['array<Vendor\\>'],
            ['array<2Vendor\\>'],
            ['array<Vendor\\2Class>'],
            ['array<User> as'],
            ['array<User> as '],
        ];
    }
}

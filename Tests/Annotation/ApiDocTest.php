<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Annotation;

use Nelmio\ApiDocBundle\Attribute\ApiDoc;
use Nelmio\ApiDocBundle\Tests\TestCase;
use Symfony\Component\Routing\Route;

class ApiDocTest extends TestCase
{
    public function testConstructWithoutData(): void
    {
        $annot = new ApiDoc();
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertFalse(isset($array['filters']));
        $this->assertFalse($annot->isResource());
        $this->assertEmpty($annot->getViews());
        $this->assertFalse($annot->getDeprecated());
        $this->assertFalse(isset($array['description']));
        $this->assertFalse(isset($array['requirements']));
        $this->assertFalse(isset($array['parameters']));
        $this->assertNull($annot->getInput());
        $this->assertFalse(isset($array['headers']));
    }

    public function testConstructWithInvalidData(): void
    {
        $annot = new ApiDoc();
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertFalse(isset($array['filters']));
        $this->assertFalse($annot->isResource());
        $this->assertFalse($annot->getDeprecated());
        $this->assertFalse(isset($array['description']));
        $this->assertFalse(isset($array['requirements']));
        $this->assertFalse(isset($array['parameters']));
        $this->assertNull($annot->getInput());
    }

    public function testConstruct(): void
    {
        $data = [
            'description' => 'Heya',
        ];

        $annot = new ApiDoc(description: $data['description']);
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertFalse(isset($array['filters']));
        $this->assertFalse($annot->isResource());
        $this->assertFalse($annot->getDeprecated());
        $this->assertEquals($data['description'], $array['description']);
        $this->assertFalse(isset($array['requirements']));
        $this->assertFalse(isset($array['parameters']));
        $this->assertNull($annot->getInput());
    }

    public function testConstructDefinesAFormType(): void
    {
        $data = [
            'description' => 'Heya',
            'input' => 'My\Form\Type',
        ];

        $annot = new ApiDoc(
            description: $data['description'],
            input: $data['input']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertFalse(isset($array['filters']));
        $this->assertFalse($annot->isResource());
        $this->assertFalse($annot->getDeprecated());
        $this->assertEquals($data['description'], $array['description']);
        $this->assertFalse(isset($array['requirements']));
        $this->assertFalse(isset($array['parameters']));
        $this->assertEquals($data['input'], $annot->getInput());
    }

    public function testConstructMethodIsResource(): void
    {
        $data = [
            'resource' => true,
            'description' => 'Heya',
            'deprecated' => true,
            'input' => 'My\Form\Type',
        ];

        $annot = new ApiDoc(
            resource: $data['resource'],
            description: $data['description'],
            deprecated: $data['deprecated'],
            input: $data['input']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertFalse(isset($array['filters']));
        $this->assertTrue($annot->isResource());
        $this->assertTrue($annot->getDeprecated());
        $this->assertEquals($data['description'], $array['description']);
        $this->assertFalse(isset($array['requirements']));
        $this->assertFalse(isset($array['parameters']));
        $this->assertEquals($data['input'], $annot->getInput());
    }

    public function testConstructMethodResourceIsFalse(): void
    {
        $data = [
            'resource' => false,
            'description' => 'Heya',
            'deprecated' => false,
            'input' => 'My\Form\Type',
        ];

        $annot = new ApiDoc(
            resource: $data['resource'],
            description: $data['description'],
            deprecated: $data['deprecated'],
            input: $data['input']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertFalse(isset($array['filters']));
        $this->assertFalse($annot->isResource());
        $this->assertEquals($data['description'], $array['description']);
        $this->assertFalse(isset($array['requirements']));
        $this->assertFalse(isset($array['parameters']));
        $this->assertEquals($data['deprecated'], $array['deprecated']);
        $this->assertEquals($data['input'], $annot->getInput());
    }

    public function testConstructMethodHasFilters(): void
    {
        $data = [
            'resource' => true,
            'deprecated' => false,
            'description' => 'Heya',
            'filters' => [
                ['name' => 'a-filter'],
            ],
        ];

        $annot = new ApiDoc(
            resource: $data['resource'],
            description: $data['description'],
            deprecated: $data['deprecated'],
            filters: $data['filters']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertTrue(is_array($array['filters']));
        $this->assertCount(1, $array['filters']);
        $this->assertEquals(['a-filter' => []], $array['filters']);
        $this->assertTrue($annot->isResource());
        $this->assertEquals($data['description'], $array['description']);
        $this->assertFalse(isset($array['requirements']));
        $this->assertFalse(isset($array['parameters']));
        $this->assertEquals($data['deprecated'], $array['deprecated']);
        $this->assertNull($annot->getInput());
    }

    public function testConstructMethodHasFiltersWithoutName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = [
            'description' => 'Heya',
            'filters' => [
                ['parameter' => 'foo'],
            ],
        ];

        $annot = new ApiDoc(
            description: $data['description'],
            filters: $data['filters']
        );
    }

    public function testConstructWithStatusCodes(): void
    {
        $data = [
            'description' => 'Heya',
            'statusCodes' => [
                200 => 'Returned when successful',
                403 => 'Returned when the user is not authorized',
                404 => [
                    'Returned when the user is not found',
                    'Returned when when something else is not found',
                ],
            ],
        ];

        $annot = new ApiDoc(
            description: $data['description'],
            statusCodes: $data['statusCodes']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertTrue(is_array($array['statusCodes']));
        foreach ($data['statusCodes'] as $code => $message) {
            $this->assertEquals($array['statusCodes'][$code], !is_array($message) ? [$message] : $message);
        }
    }

    public function testConstructWithRequirements(): void
    {
        $data = [
            'requirements' => [
                [
                    'name' => 'fooId',
                    'requirement' => '\d+',
                    'dataType' => 'integer',
                    'description' => 'This requirement might be used withing action method directly from Request object',
                ],
            ],
        ];

        $annot = new ApiDoc(
            requirements: $data['requirements']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertTrue(isset($array['requirements']['fooId']));
        $this->assertTrue(isset($array['requirements']['fooId']['dataType']));
    }

    public function testConstructWithParameters(): void
    {
        $data = [
            'parameters' => [
                [
                    'name' => 'fooId',
                    'dataType' => 'integer',
                    'description' => 'Some description',
                ],
            ],
        ];

        $annot = new ApiDoc(
            parameters: $data['parameters']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertTrue(isset($array['parameters']['fooId']));
        $this->assertTrue(isset($array['parameters']['fooId']['dataType']));
    }

    public function testConstructWithHeaders(): void
    {
        $data = [
            'headers' => [
                [
                    'name' => 'headerName',
                    'description' => 'Some description',
                ],
            ],
        ];

        $annot = new ApiDoc(
            headers: $data['headers']
        );
        $array = $annot->toArray();

        $this->assertArrayHasKey('headerName', $array['headers']);
        $this->assertNotEmpty($array['headers']['headerName']);

        $keys = array_keys($array['headers']);
        $this->assertEquals($data['headers'][0]['name'], $keys[0]);
        $this->assertEquals($data['headers'][0]['description'], $array['headers']['headerName']['description']);
    }

    public function testConstructWithOneTag(): void
    {
        $data = [
            'tags' => 'beta',
        ];

        $annot = new ApiDoc(
            tags: $data['tags']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertTrue(is_array($array['tags']), 'Single tag should be put in array');
        $this->assertEquals(['beta'], $array['tags']);
    }

    public function testConstructWithOneTagAndColorCode(): void
    {
        $data = [
            'tags' => [
                'beta' => '#ff0000',
            ],
        ];

        $annot = new ApiDoc(
            tags: $data['tags']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertTrue(is_array($array['tags']), 'Single tag should be put in array');
        $this->assertEquals(['beta' => '#ff0000'], $array['tags']);
    }

    public function testConstructWithMultipleTags(): void
    {
        $data = [
            'tags' => [
                'experimental' => '#0000ff',
                'beta' => '#0000ff',
            ],
        ];

        $annot = new ApiDoc(
            tags: $data['tags']
        );
        $array = $annot->toArray();

        $this->assertTrue(is_array($array));
        $this->assertTrue(is_array($array['tags']), 'Tags should be in array');
        $this->assertEquals($data['tags'], $array['tags']);
    }

    public function testAlignmentOfOutputAndResponseModels(): void
    {
        $data = [
            'output' => 'FooBar',
            'responseMap' => [
                400 => 'Foo\\ValidationErrorCollection',
            ],
        ];

        $apiDoc = new ApiDoc(
            output: $data['output'],
            responseMap: $data['responseMap']
        );

        $map = $apiDoc->getResponseMap();

        $this->assertCount(2, $map);
        $this->assertArrayHasKey(200, $map);
        $this->assertArrayHasKey(400, $map);
        $this->assertEquals($data['output'], $map[200]);
    }

    public function testAlignmentOfOutputAndResponseModels2(): void
    {
        $data = [
            'responseMap' => [
                200 => 'FooBar',
                400 => 'Foo\\ValidationErrorCollection',
            ],
        ];

        $apiDoc = new ApiDoc(
            responseMap: $data['responseMap']
        );
        $map = $apiDoc->getResponseMap();

        $this->assertCount(2, $map);
        $this->assertArrayHasKey(200, $map);
        $this->assertArrayHasKey(400, $map);
        $this->assertEquals($apiDoc->getOutput(), $map[200]);
    }

    public function testSetRoute(): void
    {
        $route = new Route(
            '/path/{foo}',
            [
                'foo' => 'bar',
                'nested' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ],
            [],
            [],
            '{foo}.awesome_host.com'
        );

        $apiDoc = new ApiDoc();
        $apiDoc->setRoute($route);

        $this->assertSame($route, $apiDoc->getRoute());
        $this->assertEquals('bar.awesome_host.com', $apiDoc->getHost());
        $this->assertEquals('ANY', $apiDoc->getMethod());
    }
}

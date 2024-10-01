<?php

namespace Nelmio\ApiDocBundle\Tests\Formatter;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Formatter\SwaggerFormatter;
use Nelmio\ApiDocBundle\Tests\WebTestCase;

/**
 * Class SwaggerFormatterTest
 *
 * @author  Bez Hermoso <bez@activelamp.com>
 */
class SwaggerFormatterTest extends WebTestCase
{
    /**
     * @var ApiDocExtractor
     */
    protected $extractor;

    /**
     * @var SwaggerFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainer();
        $this->extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $this->formatter = $container->get('nelmio_api_doc.formatter.swagger_formatter');
    }

    public function testResourceListing(): void
    {
        set_error_handler([$this, 'handleDeprecation']);
        $data = $this->extractor->all();
        restore_error_handler();

        /** @var $formatter SwaggerFormatter */
        $actual = $this->formatter->format($data, null);

        $expected = [
            'swaggerVersion' => '1.2',
            'apiVersion' => '3.14',
            'info' => [
                'title' => 'Nelmio Swagger',
                'description' => 'Testing Swagger integration.',
                'TermsOfServiceUrl' => 'https://github.com',
                'contact' => 'user@domain.tld',
                'license' => 'MIT',
                'licenseUrl' => 'http://opensource.org/licenses/MIT',
            ],
            'authorizations' => [
                'apiKey' => [
                    'type' => 'apiKey',
                    'passAs' => 'header',
                    'keyname' => 'access_token',
                ],
            ],
            'apis' => [
                [
                    'path' => '/other-resources',
                    'description' => 'Operations on another resource.',
                ],
                [
                    'path' => '/resources',
                    'description' => 'Operations on resource.',
                ],
                [
                    'path' => '/tests',
                    'description' => null,
                ],
                [
                    'path' => '/tests',
                    'description' => null,
                ],
                [
                    'path' => '/tests2',
                    'description' => null,
                ],
                [
                    'path' => '/TestResource',
                    'description' => null,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider dataTestApiDeclaration
     */
    public function testApiDeclaration($resource, $expected): void
    {
        set_error_handler([$this, 'handleDeprecation']);
        $data = $this->extractor->all();
        restore_error_handler();

        $actual = $this->formatter->format($data, $resource);

        $this->assertEquals($expected, $actual);
    }

    public function dataTestApiDeclaration()
    {
        return [
            [
                '/resources',
                [
                    'swaggerVersion' => '1.2',
                    'apiVersion' => '3.14',
                    'basePath' => '/api',
                    'resourcePath' => '/resources',
                    'apis' => [
                        [
                            'path' => '/resources.{_format}',
                            'operations' => [
                                [
                                    'method' => 'GET',
                                    'summary' => 'List resources.',
                                    'nickname' => 'get_resources',
                                    'parameters' => [
                                        [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                            'enum' => [
                                                'json',
                                                'xml',
                                                'html',
                                            ],
                                        ],
                                    ],
                                    'responseMessages' => [
                                        [
                                            'code' => 200,
                                            'message' => 'Returned on success.',
                                            'responseModel' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.Test[tests]',
                                        ],
                                        [
                                            'code' => 404,
                                            'message' => 'Returned if resource cannot be found.',
                                        ],
                                    ],
                                    'type' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.Test[tests]',
                                ],
                                [
                                    'method' => 'POST',
                                    'summary' => 'Create a new resource.',
                                    'nickname' => 'post_resources',
                                    'parameters' => [
                                        0 => [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                            'enum' => [
                                                0 => 'json',
                                                1 => 'xml',
                                                2 => 'html',
                                            ],
                                        ],
                                        1 => [
                                            'paramType' => 'form',
                                            'name' => 'a',
                                            'type' => 'string',
                                            'description' => 'Something that describes A.',
                                        ],
                                        2 => [
                                            'paramType' => 'form',
                                            'name' => 'b',
                                            'type' => 'number',
                                            'format' => 'float',
                                        ],
                                        3 => [
                                            'paramType' => 'form',
                                            'name' => 'c',
                                            'type' => 'string',
                                            'enum' => [
                                                0 => 'X',
                                                1 => 'Y',
                                                2 => 'Z',
                                            ],
                                        ],
                                        4 => [
                                            'paramType' => 'form',
                                            'name' => 'd',
                                            'type' => 'string',
                                            'format' => 'date-time',
                                        ],
                                        5 => [
                                            'paramType' => 'form',
                                            'name' => 'e',
                                            'type' => 'string',
                                            'format' => 'date',
                                        ],
                                        6 => [
                                            'paramType' => 'form',
                                            'name' => 'g',
                                            'type' => 'string',
                                        ],
                                    ],
                                    'responseMessages' => [
                                        0 => [
                                            'code' => 200,
                                            'message' => 'See standard HTTP status code reason for 200',
                                            'responseModel' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                                        ],
                                        1 => [
                                            'code' => 400,
                                            'message' => 'See standard HTTP status code reason for 400',
                                            'responseModel' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.ErrorResponse',
                                        ],
                                    ],
                                    'type' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                                ],
                            ],
                        ],
                        [
                            'path' => '/resources/{id}.{_format}',
                            'operations' => [
                                [
                                    'method' => 'DELETE',
                                    'summary' => 'Delete a resource by ID.',
                                    'nickname' => 'delete_resources',
                                    'parameters' => [
                                        [
                                            'paramType' => 'path',
                                            'name' => 'id',
                                            'type' => 'string',
                                            'required' => true,
                                        ],
                                        [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                            'enum' => [
                                                'json',
                                                'xml',
                                                'html',
                                            ],
                                        ],
                                    ],
                                    'responseMessages' => [],
                                ],
                                [
                                    'method' => 'GET',
                                    'summary' => 'Retrieve a resource by ID.',
                                    'nickname' => 'get_resources',
                                    'parameters' => [
                                        [
                                            'paramType' => 'path',
                                            'name' => 'id',
                                            'type' => 'string',
                                            'required' => true,
                                        ],
                                        [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                            'enum' => [
                                                'json',
                                                'xml',
                                                'html',
                                            ],
                                        ],
                                    ],
                                    'responseMessages' => [],
                                ],
                            ],
                        ],
                    ],
                    'models' => [
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Model.Test' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.Test',
                            'description' => null,
                            'properties' => [
                                'a' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'b' => [
                                    'type' => 'string',
                                    'description' => 'DateTime',
                                    'format' => 'date-time',
                                ],
                            ],
                            'required' => [
                                'a',
                            ],
                        ],
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Model.Test[tests]' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.Test[tests]',
                            'description' => '',
                            'properties' => [
                                'tests' => [
                                    'type' => 'array',
                                    'description' => null,
                                    'items' => [
                                        '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.Test',
                                    ],
                                ],
                            ],
                            'required' => [
                                'tests',
                            ],
                        ],
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest',
                            'description' => 'object (JmsTest)',
                            'properties' => [
                                'foo' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'bar' => [
                                    'type' => 'string',
                                    'description' => 'DateTime',
                                    'format' => 'date-time',
                                ],
                                'number' => [
                                    'type' => 'number',
                                    'description' => 'double',
                                    'format' => 'float',
                                ],
                                'arr' => [
                                    'type' => 'array',
                                    'description' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'nested' => [
                                    '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                                ],
                                'nested_array' => [
                                    'type' => 'array',
                                    'description' => 'array of objects (JmsNested)',
                                    'items' => [
                                        '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                                    ],
                                ],
                            ],
                            'required' => [],
                        ],
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                            'description' => '',
                            'properties' => [
                                'foo' => [
                                    'type' => 'string',
                                    'description' => 'DateTime',
                                    'format' => 'date-time',
                                ],
                                'bar' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'baz' => [
                                    'type' => 'array',
                                    'description' => 'Epic description.

With multiple lines.',
                                    'items' => [
                                        'type' => 'integer',
                                    ],
                                ],
                                'circular' => [
                                    '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                                ],
                                'parent' => [
                                    '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest',
                                ],
                                'since' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'until' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'since_and_until' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                            ],
                            'required' => [],
                        ],
                        'FieldErrors' => [
                            'id' => 'FieldErrors',
                            'description' => 'Errors on the parameter',
                            'properties' => [
                                'errors' => [
                                    'type' => 'array',
                                    'description' => 'array of errors',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                            'required' => [],
                        ],
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.FormErrors' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.FormErrors',
                            'description' => 'Errors',
                            'properties' => [
                                'simple' => [
                                    '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.FieldErrors[simple]',
                                ],
                            ],
                            'required' => [],
                        ],
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.ErrorResponse' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.ErrorResponse',
                            'description' => '',
                            'properties' => [
                                'status_code' => [
                                    'type' => 'integer',
                                    'description' => 'The status code',
                                    'format' => 'int32',
                                ],
                                'message' => [
                                    'type' => 'string',
                                    'description' => 'The error message',
                                ],
                                'errors' => [
                                    '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.FormErrors',
                                ],
                            ],
                            'required' => [],
                        ],
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.FieldErrors[simple]' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Form.SimpleType.FieldErrors[simple]',
                            'description' => 'Errors on the parameter',
                            'properties' => [
                                'a' => [
                                    '$ref' => 'FieldErrors',
                                ],
                                'b' => [
                                    '$ref' => 'FieldErrors',
                                ],
                                'c' => [
                                    '$ref' => 'FieldErrors',
                                ],
                                'd' => [
                                    '$ref' => 'FieldErrors',
                                ],
                                'e' => [
                                    '$ref' => 'FieldErrors',
                                ],
                                'g' => [
                                    '$ref' => 'FieldErrors',
                                ],
                            ],
                            'required' => [],
                        ],
                    ],
                    'produces' => [],
                    'consumes' => [],
                    'authorizations' => [
                        'apiKey' => [
                            'type' => 'apiKey',
                            'passAs' => 'header',
                            'keyname' => 'access_token',
                        ],
                    ],
                ],
            ],
            [
                '/other-resources',
                [
                    'swaggerVersion' => '1.2',
                    'apiVersion' => '3.14',
                    'basePath' => '/api',
                    'resourcePath' => '/other-resources',
                    'apis' => [
                        [
                            'path' => '/other-resources.{_format}',
                            'operations' => [
                                [
                                    'method' => 'GET',
                                    'summary' => 'List another resource.',
                                    'nickname' => 'get_other-resources',
                                    'parameters' => [
                                        [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                            'enum' => [
                                                'json',
                                                'xml',
                                                'html',
                                            ],
                                        ],
                                    ],
                                    'responseMessages' => [
                                        [
                                            'code' => 200,
                                            'message' => 'See standard HTTP status code reason for 200',
                                            'responseModel' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest[]',
                                        ],
                                    ],
                                    'type' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest[]',
                                ],
                            ],
                        ],
                        [
                            'path' => '/other-resources/{id}.{_format}',
                            'operations' => [
                                [
                                    'method' => 'PUT',
                                    'summary' => 'Update a resource bu ID.',
                                    'nickname' => 'put_other-resources',
                                    'parameters' => [
                                        [
                                            'paramType' => 'path',
                                            'name' => 'id',
                                            'type' => 'string',
                                            'required' => true,
                                        ],
                                        [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                            'enum' => [
                                                'json',
                                                'xml',
                                                'html',
                                            ],
                                        ],
                                    ],
                                    'responseMessages' => [],
                                ],
                                [
                                    'method' => 'PATCH',
                                    'summary' => 'Update a resource bu ID.',
                                    'nickname' => 'patch_other-resources',
                                    'parameters' => [
                                        [
                                            'paramType' => 'path',
                                            'name' => 'id',
                                            'type' => 'string',
                                            'required' => true,
                                        ],
                                        [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                            'enum' => [
                                                'json',
                                                'xml',
                                                'html',
                                            ],
                                        ],
                                    ],
                                    'responseMessages' => [],
                                ],
                            ],
                        ],
                    ],
                    'models' => [
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest',
                            'description' => null,
                            'properties' => [
                                'foo' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'bar' => [
                                    'type' => 'string',
                                    'description' => 'DateTime',
                                    'format' => 'date-time',
                                ],
                                'number' => [
                                    'type' => 'number',
                                    'description' => 'double',
                                    'format' => 'float',
                                ],
                                'arr' => [
                                    'type' => 'array',
                                    'description' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'nested' => [
                                    '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                                ],
                                'nested_array' => [
                                    'type' => 'array',
                                    'description' => 'array of objects (JmsNested)',
                                    'items' => [
                                        '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                                    ],
                                ],
                            ],
                            'required' => [],
                        ],
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                            'description' => 'object (JmsNested)',
                            'properties' => [
                                'foo' => [
                                    'type' => 'string',
                                    'description' => 'DateTime',
                                    'format' => 'date-time',
                                ],
                                'bar' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'baz' => [
                                    'type' => 'array',
                                    'description' => 'Epic description.

With multiple lines.',
                                    'items' => [
                                        'type' => 'integer',
                                    ],
                                ],
                                'circular' => [
                                    '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsNested',
                                ],
                                'parent' => [
                                    '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest',
                                ],
                                'since' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'until' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                                'since_and_until' => [
                                    'type' => 'string',
                                    'description' => 'string',
                                ],
                            ],
                            'required' => [],
                        ],
                        'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest[]' => [
                            'id' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest[]',
                            'description' => '',
                            'properties' => [
                                '' => [
                                    'type' => 'array',
                                    'description' => null,
                                    'items' => [
                                        '$ref' => 'Nelmio.ApiDocBundle.Tests.Fixtures.Model.JmsTest',
                                    ],
                                ],
                            ],
                            'required' => [
                                '',
                            ],
                        ],
                    ],
                    'produces' => [],
                    'consumes' => [],
                    'authorizations' => [
                        'apiKey' => [
                            'type' => 'apiKey',
                            'passAs' => 'header',
                            'keyname' => 'access_token',
                        ],
                    ],
                ],
            ],
            [
                '/tests',
                [
                    'swaggerVersion' => '1.2',
                    'apiVersion' => '3.14',
                    'basePath' => '/api',
                    'resourcePath' => '/tests',
                    'apis' => [
                        [
                            'path' => '/tests.{_format}',
                            'operations' => [
                                [
                                    'method' => 'GET',
                                    'summary' => 'index action',
                                    'nickname' => 'get_tests',
                                    'parameters' => [
                                        [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                        ],
                                        [
                                            'paramType' => 'query',
                                            'name' => 'a',
                                            'type' => 'integer',
                                            'description' => null,
                                        ],
                                        [
                                            'paramType' => 'query',
                                            'name' => 'b',
                                            'type' => 'string',
                                            'description' => null,
                                        ],
                                    ],
                                    'responseMessages' => [],
                                ],
                                [
                                    'method' => 'POST',
                                    'summary' => 'create test',
                                    'nickname' => 'post_tests',
                                    'parameters' => [
                                        [
                                            'paramType' => 'path',
                                            'name' => '_format',
                                            'type' => 'string',
                                            'required' => true,
                                        ],
                                        [
                                            'paramType' => 'form',
                                            'name' => 'a',
                                            'type' => 'string',
                                            'description' => 'A nice description',
                                        ],
                                        [
                                            'paramType' => 'form',
                                            'name' => 'b',
                                            'type' => 'string',
                                        ],
                                        [
                                            'paramType' => 'form',
                                            'name' => 'c',
                                            'type' => 'boolean',
                                            'defaultValue' => false,
                                        ],
                                        [
                                            'paramType' => 'form',
                                            'name' => 'd',
                                            'type' => 'string',
                                            'defaultValue' => 'DefaultTest',
                                        ],
                                    ],
                                    'responseMessages' => [],
                                ],
                            ],
                        ],
                    ],
                    'models' => [],
                    'produces' => [],
                    'consumes' => [],
                    'authorizations' => [
                        'apiKey' => [
                            'type' => 'apiKey',
                            'passAs' => 'header',
                            'keyname' => 'access_token',
                        ],
                    ],
                ],
            ],
        ];
    }
}

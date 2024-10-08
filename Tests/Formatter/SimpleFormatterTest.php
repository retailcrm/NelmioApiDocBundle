<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Formatter;

use Nelmio\ApiDocBundle\Tests\WebTestCase;

class SimpleFormatterTest extends WebTestCase
{
    public function testFormat(): void
    {
        $container = $this->getContainer();

        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        set_error_handler([$this, 'handleDeprecation']);
        $data = $extractor->all();
        restore_error_handler();
        $result = $container->get('nelmio_api_doc.formatter.simple_formatter')->format($data);

        $suffix = '_1';
        $expected = require __DIR__ . '/testFormat-result' . $suffix . '.php';

        $this->assertEquals($expected, $result, 'file ' . __DIR__ . '/testFormat-result' . $suffix . '.php');
    }

    public function testFormatOne(): void
    {
        $container = $this->getContainer();

        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::indexAction', 'test_route_1');
        $result = $container->get('nelmio_api_doc.formatter.simple_formatter')->formatOne($annotation);

        $expected = [
            'method' => 'GET',
            'uri' => '/tests.{_format}',
            'filters' => [
                'a' => [
                    'dataType' => 'integer',
                ],
                'b' => [
                    'dataType' => 'string',
                    'arbitrary' => [
                        'arg1',
                        'arg2',
                    ],
                ],
            ],
            'description' => 'index action',
            'requirements' => [
                '_format' => ['dataType' => '', 'description' => '', 'requirement' => ''],
            ],
            'deprecated' => false,
            'scope' => null,
        ];

        $this->assertEquals($expected, $result);
    }
}

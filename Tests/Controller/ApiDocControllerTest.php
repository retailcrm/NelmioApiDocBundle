<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NelmioApiDocBundle\Tests\Controller;

use Nelmio\ApiDocBundle\Tests\WebTestCase;

/**
 * Class ApiDocControllerTest
 *
 * @author Bez Hermoso <bez@activelamp.com>
 */
class ApiDocControllerTest extends WebTestCase
{
    public function testSwaggerDocResourceListRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api-docs');

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/html; charset=UTF-8', $response->headers->get('Content-type'));
    }

    public function dataTestApiDeclarations()
    {
        return [
            ['resources'],
            ['tests'],
            ['tests2'],
            ['TestResource'],
        ];
    }

    /**
     * @dataProvider dataTestApiDeclarations
     */
    public function testApiDeclarationRoutes($resource): void
    {
        $client = static::createClient();
        $client->request('GET', '/api-docs/' . $resource);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-type'));
    }

    public function testNonExistingApiDeclaration(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api-docs/santa');

        $response = $client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
    }
}

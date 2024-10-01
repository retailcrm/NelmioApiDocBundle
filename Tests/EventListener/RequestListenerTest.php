<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\EventListener;

use Nelmio\ApiDocBundle\Tests\WebTestCase;

class RequestListenerTest extends WebTestCase
{
    public function testDocQueryArg(): void
    {
        $client = $this->createClient();

        $client->request('GET', '/tests?_doc=1');
        $content = $client->getResponse()->getContent();
        $this->assertTrue(!str_starts_with($content, '<h1>API documentation</h1>'), 'Event listener should capture ?_doc=1 requests');
        $this->assertTrue(!str_starts_with($content, '/tests.{_format}'), 'Event listener should capture ?_doc=1 requests');

        $client->request('GET', '/tests');
        $this->assertEquals('tests', $client->getResponse()->getContent(), 'Event listener should let normal requests through');
    }
}

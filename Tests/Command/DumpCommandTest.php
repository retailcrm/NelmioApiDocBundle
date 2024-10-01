<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NelmioApiDocBundle\Tests\Command;

use Nelmio\ApiDocBundle\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DumpCommandTest extends WebTestCase
{
    /**
     * @dataProvider viewProvider
     *
     * @param string $view                 Command view option value
     * @param array  $expectedMethodsCount Expected resource methods count
     * @param array  $expectedMethodValues Expected resource method values
     */
    public function testDumpWithViewOption($view, array $expectedMethodsCount, array $expectedMethodValues): void
    {
        $this->getContainer();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(false);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);

        $input = [
            'command' => 'api:doc:dump',
            '--view' => $view,
            '--format' => 'json',
        ];
        $tester->run($input);

        $display = $tester->getDisplay();

        $this->assertJson($display);

        $json = json_decode($display);

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($expectedMethodsCount as $propertyPath => $expectedCount) {
            $this->assertCount($expectedCount, $accessor->getValue($json, $propertyPath));
        }

        foreach ($expectedMethodValues as $propertyPath => $expectedValue) {
            $this->assertEquals($expectedValue, $accessor->getValue($json, $propertyPath));
        }
    }

    /**
     * @return array
     */
    public static function viewProvider()
    {
        return [
            'test' => [
                'test',
                [
                    '/api/resources' => 1,
                ],
                [
                    '/api/resources[0].method' => 'GET',
                    '/api/resources[0].uri' => '/api/resources.{_format}',
                ],
            ],
            'premium' => [
                'premium',
                [
                    '/api/resources' => 2,
                ],
                [
                    '/api/resources[0].method' => 'GET',
                    '/api/resources[0].uri' => '/api/resources.{_format}',
                    '/api/resources[1].method' => 'POST',
                    '/api/resources[1].uri' => '/api/resources.{_format}',
                ],
            ],
            'default' => [
                'default',
                [
                    '/api/resources' => 4,
                ],
                [
                    '/api/resources[0].method' => 'GET',
                    '/api/resources[0].uri' => '/api/resources.{_format}',
                    '/api/resources[1].method' => 'POST',
                    '/api/resources[1].uri' => '/api/resources.{_format}',
                    '/api/resources[2].method' => 'DELETE',
                    '/api/resources[2].uri' => '/api/resources/{id}.{_format}',
                    '/api/resources[3].method' => 'GET',
                    '/api/resources[3].uri' => '/api/resources/{id}.{_format}',
                ],
            ],
        ];
    }
}

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

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Tests\WebTestCase;

class ApiDocExtractorTest extends WebTestCase
{
    private static $ROUTES_QUANTITY_DEFAULT = 26; // Routes in the default view
    private static $ROUTES_QUANTITY_PREMIUM = 5;  // Routes in the premium view
    private static $ROUTES_QUANTITY_TEST = 2;  // Routes in the test view

    public function testAll(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        set_error_handler([$this, 'handleDeprecation']);
        $data = $extractor->all();
        restore_error_handler();

        $this->assertTrue(is_array($data));
        $this->assertCount(self::$ROUTES_QUANTITY_DEFAULT, $data);

        $cacheFile = $container->getParameter('kernel.cache_dir') . '/api-doc.cache.' . ApiDoc::DEFAULT_VIEW;
        $this->assertFileExists($cacheFile);
        $this->assertStringEqualsFile($cacheFile, serialize($data));

        foreach ($data as $key => $d) {
            $this->assertTrue(is_array($d));
            $this->assertArrayHasKey('annotation', $d);
            $this->assertArrayHasKey('resource', $d);

            $this->assertInstanceOf('Nelmio\ApiDocBundle\Annotation\ApiDoc', $d['annotation']);
            $this->assertInstanceOf('Symfony\Component\Routing\Route', $d['annotation']->getRoute());
            $this->assertNotNull($d['resource']);
        }
    }

    public function testRouteVersionChecking(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $data = $extractor->allForVersion('1.5');
        $this->assertTrue(is_array($data));
        $this->assertCount(self::$ROUTES_QUANTITY_DEFAULT, $data);
        $data = $extractor->allForVersion('1.4');
        $this->assertTrue(is_array($data));
        $this->assertCount(self::$ROUTES_QUANTITY_DEFAULT - 1, $data);
    }

    public function testGet(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::indexAction', 'test_route_1');

        $this->assertInstanceOf('Nelmio\ApiDocBundle\Annotation\ApiDoc', $annotation);

        $this->assertTrue($annotation->isResource());
        $this->assertEquals('index action', $annotation->getDescription());

        $array = $annotation->toArray();
        $this->assertTrue(is_array($array['filters']));
        $this->assertNull($annotation->getInput());

        $annotation2 = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::indexAction', 'test_service_route_1');
        $annotation2->getRoute()
            ->setDefault('_controller', $annotation->getRoute()->getDefault('_controller'))
            ->compile() // compile as we changed a default value
        ;
        $this->assertEquals($annotation, $annotation2);
    }

    public function testGetWithBadController(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $data = $extractor->get('Undefined\Controller::indexAction', 'test_route_1');

        $this->assertNull($data);

        $data = $extractor->get('undefined_service:index', 'test_service_route_1');

        $this->assertNull($data);
    }

    public function testGetWithBadRoute(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $data = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::indexAction', 'invalid_route');

        $this->assertNull($data);

        $data = $extractor->get('nelmio.test.controller:indexAction', 'invalid_route');

        $this->assertNull($data);
    }

    public function testGetWithInvalidPath(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $data = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController', 'test_route_1');

        $this->assertNull($data);

        $data = $extractor->get('nelmio.test.controller', 'test_service_route_1');

        $this->assertNull($data);
    }

    public function testGetWithMethodWithoutApiDocAnnotation(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $data = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::anotherAction', 'test_route_3');

        $this->assertNull($data);

        $data = $extractor->get('nelmio.test.controller:anotherAction', 'test_service_route_1');

        $this->assertNull($data);
    }

    public function testGetWithDocComment(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::myCommentedAction', 'test_route_5');

        $this->assertNotNull($annotation);
        $this->assertEquals(
            'This method is useful to test if the getDocComment works.',
            $annotation->getDescription()
        );

        $data = $annotation->toArray();
        $this->assertEquals(
            4,
            count($data['requirements'])
        );
        $this->assertEquals(
            'The param type',
            $data['requirements']['paramType']['description']
        );
        $this->assertEquals(
            'The param id',
            $data['requirements']['param']['description']
        );
    }

    public function testGetWithDeprecated(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::DeprecatedAction', 'test_route_14');

        $this->assertNotNull($annotation);
        $this->assertTrue(
            $annotation->getDeprecated()
        );
    }

    public function testOutputWithSelectedParsers(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::zReturnSelectedParsersOutputAction', 'test_route_19');

        $this->assertNotNull($annotation);
        $output = $annotation->getOutput();

        $parsers = $output['parsers'];
        $this->assertEquals(
            'Nelmio\\ApiDocBundle\\Parser\\JmsMetadataParser',
            $parsers[0]
        );
        $this->assertEquals(
            'Nelmio\\ApiDocBundle\\Parser\\ValidationParser',
            $parsers[1]
        );
        $this->assertCount(2, $parsers);
    }

    public function testInputWithSelectedParsers(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::zReturnSelectedParsersInputAction', 'test_route_20');

        $this->assertNotNull($annotation);
        $input = $annotation->getInput();
        $parsers = $input['parsers'];
        $this->assertEquals(
            'Nelmio\\ApiDocBundle\\Parser\\FormTypeParser',
            $parsers[0]
        );
        $this->assertCount(1, $parsers);
    }

    public function testPostRequestDoesRequireParametersWhenMarkedAsSuch(): void
    {
        $container = $this->getContainer();
        /** @var ApiDocExtractor $extractor */
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        /** @var ApiDoc $annotation */
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::requiredParametersAction', 'test_required_parameters');

        $parameters = $annotation->getParameters();
        $this->assertTrue($parameters['required_field']['required']);
    }

    public function testPatchRequestDoesNeverRequireParameters(): void
    {
        $container = $this->getContainer();
        /** @var ApiDocExtractor $extractor */
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        /** @var ApiDoc $annotation */
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::requiredParametersAction', 'test_patch_disables_required_parameters');

        $parameters = $annotation->getParameters();
        $this->assertFalse($parameters['required_field']['required']);
    }

    public static function dataProviderForViews(): array
    {
        $offset = 0;

        return [
            ['default', self::$ROUTES_QUANTITY_DEFAULT + $offset],
            ['premium', self::$ROUTES_QUANTITY_PREMIUM + $offset],
            ['test', self::$ROUTES_QUANTITY_TEST + $offset],
            ['foobar', $offset],
            ['', $offset],
            [null, $offset],
        ];
    }

    public function testViewNamedTest(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        set_error_handler([$this, 'handleDeprecation']);
        $data = $extractor->all('test');
        restore_error_handler();

        $this->assertTrue(is_array($data));
        $this->assertCount(self::$ROUTES_QUANTITY_TEST, $data);

        $a1 = $data[0]['annotation'];
        $this->assertCount(3, $a1->getViews());
        $this->assertEquals('List resources.', $a1->getDescription());

        $a2 = $data[1]['annotation'];
        $this->assertCount(2, $a2->getViews());
        $this->assertEquals('create another test', $a2->getDescription());
    }

    public function testViewNamedPremium(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        set_error_handler([$this, 'handleDeprecation']);
        $data = $extractor->all('premium');
        restore_error_handler();

        $this->assertTrue(is_array($data));
        $this->assertCount(self::$ROUTES_QUANTITY_PREMIUM, $data);

        $a1 = $data[0]['annotation'];
        $this->assertCount(2, $a1->getViews());
        $this->assertEquals('List another resource.', $a1->getDescription());

        $a2 = $data[1]['annotation'];
        $this->assertCount(3, $a2->getViews());
        $this->assertEquals('List resources.', $a2->getDescription());
    }

    /**
     * @dataProvider dataProviderForViews
     */
    public function testForViews($view, $count): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        set_error_handler([$this, 'handleDeprecation']);
        $data = $extractor->all($view);
        restore_error_handler();

        $this->assertTrue(is_array($data));
        $this->assertCount($count, $data);
    }

    public function testOverrideJmsAnnotationWithApiDocParameters(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get(
            'Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::overrideJmsAnnotationWithApiDocParametersAction',
            'test_route_27'
        );

        $this->assertInstanceOf('Nelmio\ApiDocBundle\Annotation\ApiDoc', $annotation);

        $array = $annotation->toArray();
        $this->assertTrue(is_array($array['parameters']));

        $this->assertEquals('string', $array['parameters']['foo']['dataType']);
        $this->assertEquals('DateTime', $array['parameters']['bar']['dataType']);

        $this->assertEquals('integer', $array['parameters']['number']['dataType']);
        $this->assertEquals('string', $array['parameters']['number']['actualType']);
        $this->assertNull($array['parameters']['number']['subType']);
        $this->assertTrue($array['parameters']['number']['required']);
        $this->assertEquals('This is the new description', $array['parameters']['number']['description']);
        $this->assertFalse($array['parameters']['number']['readonly']);
        $this->assertEquals('v3.0', $array['parameters']['number']['sinceVersion']);
        $this->assertEquals('v4.0', $array['parameters']['number']['untilVersion']);

        $this->assertEquals('object (ArrayCollection)', $array['parameters']['arr']['dataType']);

        $this->assertEquals('object (JmsNested)', $array['parameters']['nested']['dataType']);
        $this->assertEquals('integer', $array['parameters']['nested']['children']['bar']['dataType']);
        $this->assertEquals('d+', $array['parameters']['nested']['children']['bar']['format']);
    }

    public function testJmsAnnotation(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get(
            'Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::defaultJmsAnnotations',
            'test_route_27'
        );

        $this->assertInstanceOf('Nelmio\ApiDocBundle\Annotation\ApiDoc', $annotation);

        $array = $annotation->toArray();
        $this->assertTrue(is_array($array['parameters']));

        $this->assertEquals('string', $array['parameters']['foo']['dataType']);
        $this->assertEquals('DateTime', $array['parameters']['bar']['dataType']);

        $this->assertEquals('double', $array['parameters']['number']['dataType']);
        $this->assertEquals('float', $array['parameters']['number']['actualType']);
        $this->assertNull($array['parameters']['number']['subType']);
        $this->assertFalse($array['parameters']['number']['required']);
        $this->assertEquals('', $array['parameters']['number']['description']);
        $this->assertFalse($array['parameters']['number']['readonly']);
        $this->assertNull($array['parameters']['number']['sinceVersion']);
        $this->assertNull($array['parameters']['number']['untilVersion']);

        $this->assertEquals('array', $array['parameters']['arr']['dataType']);

        $this->assertEquals('object (JmsNested)', $array['parameters']['nested']['dataType']);
        $this->assertEquals('string', $array['parameters']['nested']['children']['bar']['dataType']);
    }

    public function testMergeParametersDefaultKeyNotExistingInFirstArray(): void
    {
        $container = $this->getContainer();
        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');

        $mergeMethod = new \ReflectionMethod('Nelmio\ApiDocBundle\Extractor\ApiDocExtractor', 'mergeParameters');
        $mergeMethod->setAccessible(true);

        $p1 = [
            'myPropName' => [
                'dataType' => 'string',
                'actualType' => 'string',
                'subType' => null,
                'required' => null,
                'description' => null,
                'readonly' => null,
            ],
        ];

        $p2 = [
            'myPropName' => [
                'dataType' => 'string',
                'actualType' => 'string',
                'subType' => null,
                'required' => null,
                'description' => null,
                'readonly' => null,
                'default' => '',
            ],
        ];

        $mergedResult = $mergeMethod->invokeArgs($extractor, [$p1, $p2]);
        $this->assertEquals($p2, $mergedResult);
    }
}

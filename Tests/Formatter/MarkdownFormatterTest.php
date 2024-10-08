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
use Nelmio\ApiDocBundle\Util\LegacyFormHelper;

class MarkdownFormatterTest extends WebTestCase
{
    public function testFormat(): void
    {
        $container = $this->getContainer();

        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        set_error_handler([$this, 'handleDeprecation']);
        $data = $extractor->all();
        restore_error_handler();
        $result = $container->get('nelmio_api_doc.formatter.markdown_formatter')->format($data);

        $suffix = '_1';
        $expected = file_get_contents(__DIR__ . '/testFormat-result' . $suffix . '.markdown');
        if (LegacyFormHelper::isLegacy()) {
            $expected = str_replace('DependencyType', 'dependency_type', $expected);
        }

        $this->assertEquals($expected, $result . "\n", 'file ' . __DIR__ . '/testFormat-result' . $suffix . '.markdown');
    }

    public function testFormatOne(): void
    {
        $container = $this->getContainer();

        $extractor = $container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $annotation = $extractor->get('Nelmio\ApiDocBundle\Tests\Fixtures\Controller\TestController::indexAction', 'test_route_1');
        $result = $container->get('nelmio_api_doc.formatter.markdown_formatter')->formatOne($annotation);

        $expected = <<<MARKDOWN
### `GET` /tests.{_format} ###

_index action_

#### Requirements ####

**_format**


#### Filters ####

a:

  * DataType: integer

b:

  * DataType: string
  * Arbitrary: ["arg1","arg2"]


MARKDOWN;

        $this->assertEquals($expected, $result);
    }
}

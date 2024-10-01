<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Controller;

use Nelmio\ApiDocBundle\Attribute\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Formatter\HtmlFormatter;
use Nelmio\ApiDocBundle\Formatter\RequestAwareSwaggerFormatter;
use Nelmio\ApiDocBundle\Formatter\SwaggerFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiDocController extends AbstractController
{
    public function __construct(
        private readonly ApiDocExtractor $extractor,
        private readonly HtmlFormatter $htmlFormatter,
        private readonly SwaggerFormatter $swaggerFormatter,
    ) {
    }

    public function index(Request $request, $view = ApiDoc::DEFAULT_VIEW)
    {
        $apiVersion = $request->query->get('_version', null);

        if ($apiVersion) {
            $this->htmlFormatter->setVersion($apiVersion);
            $extractedDoc = $this->extractor->allForVersion($apiVersion, $view);
        } else {
            $extractedDoc = $this->extractor->all($view);
        }
        $htmlContent = $this->htmlFormatter->format($extractedDoc);

        return new Response($htmlContent, 200, ['Content-Type' => 'text/html']);
    }

    public function swagger(Request $request, $resource = null)
    {
        $docs = $this->extractor->all();
        $formatter = new RequestAwareSwaggerFormatter($request, $this->swaggerFormatter);

        $spec = $formatter->format($docs, $resource ? '/' . $resource : null);

        if (null !== $resource && 0 === count($spec['apis'])) {
            throw $this->createNotFoundException(sprintf('Cannot find resource "%s"', $resource));
        }

        return new JsonResponse($spec);
    }
}

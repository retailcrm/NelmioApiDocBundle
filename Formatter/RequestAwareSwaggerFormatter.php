<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Formatter;

use Nelmio\ApiDocBundle\Attribute\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extends SwaggerFormatter which takes into account the request's base URL when generating the documents for direct swagger-ui consumption.
 *
 * @author Bezalel Hermoso <bezalelhermoso@gmail.com>
 */
class RequestAwareSwaggerFormatter implements FormatterInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var SwaggerFormatter
     */
    protected $formatter;

    public function __construct(Request $request, SwaggerFormatter $formatter)
    {
        $this->request = $request;
        $this->formatter = $formatter;
    }

    /**
     * Format a collection of documentation data.
     *
     * @param null $resource
     *
     * @internal param $array [ApiDoc] $collection
     *
     * @return string|array
     */
    public function format(array $collection, $resource = null)
    {
        $result = $this->formatter->format($collection, $resource);

        if (null !== $resource) {
            $result['basePath'] = $this->request->getBaseUrl() . $result['basePath'];
        }

        return $result;
    }

    /**
     * Format documentation data for one route.
     *
     * @param ApiDoc $annotation
     *                           return string|array
     */
    public function formatOne(ApiDoc $annotation)
    {
        return $this->formatter->formatOne($annotation);
    }
}

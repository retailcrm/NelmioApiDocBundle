<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Render\Html;

use InvalidArgumentException;
use Nelmio\ApiDocBundle\Render\OpenApiRenderer;
use Nelmio\ApiDocBundle\Render\RenderOpenApi;
use OpenApi\Annotations\OpenApi;
use Twig\Environment;

/**
 * @internal
 */
class HtmlOpenApiRenderer implements OpenApiRenderer
{
    /** @var Environment|\Twig_Environment */
    private $twig;

    /** @var GetNelmioAsset */
    private $getNelmioAsset;

    public function __construct($twig)
    {
        if (!$twig instanceof \Twig_Environment && !$twig instanceof Environment) {
            throw new InvalidArgumentException(sprintf('Providing an instance of "%s" as twig is not supported.', get_class($twig)));
        }
        $this->twig = $twig;
    }

    public function getFormat(): string
    {
        return RenderOpenApi::HTML;
    }

    public function render(OpenApi $spec, array $options = []): string
    {
        $options += [
            'assets_mode' => AssetsMode::CDN,
            'swagger_ui_config' => [],
        ];

        $spec = json_decode($spec->toJson(), true);

        foreach ($spec['paths'] as $path => $methods) {
            foreach ($methods as $method => $data) {
                if (array_key_exists('tags', $data)) {
                    $spec['resources'][$data['tags'][0]][$path] = $methods;
                    $spec['resources'][$data['tags'][0]][$path][$method]['id'] = strtolower($method).'-'.str_replace('/', '-', $path);
                }
            }
        }

        return $this->twig->render(
            '@NelmioApiDoc/SwaggerUi/index.html.twig',
            [
                'swagger_data' => ['spec' => $spec],
                'assets_mode' => $options['assets_mode'],
                'swagger_ui_config' => $options['swagger_ui_config'],
            ]
        );
    }
}

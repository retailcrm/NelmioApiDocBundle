<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Extractor;

use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Util\DocCommentExtractor;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class CachingApiDocExtractor
 *
 * @author Bez Hermoso <bez@activelamp.com>
 */
class CachingApiDocExtractor extends ApiDocExtractor
{
    /**
     * @param HandlerInterface[]             $handlers
     * @param AnnotationsProviderInterface[] $annotationsProviders
     * @param string[]                       $excludeSections
     * @param bool|false                     $debug
     */
    public function __construct(
        RouterInterface $router,
        Reader $reader,
        DocCommentExtractor $commentExtractor,
        array $handlers,
        array $annotationsProviders,
        array $excludeSections,
        private string $cacheFile,
        private bool $debug = false,
    ) {
        parent::__construct($router, $reader, $commentExtractor, $handlers, $annotationsProviders, $excludeSections);
    }

    /**
     * @param string $view View name
     *
     * @return array|mixed
     */
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        $cache = $this->getViewCache($view);

        if (!$cache->isFresh()) {
            $resources = [];
            foreach ($this->getRoutes() as $route) {
                if (null !== ($method = $this->getReflectionMethod($route->getDefault('_controller')))
                  && null !== ($annotation = $this->reader->getMethodAnnotation($method, self::ANNOTATION_CLASS))) {
                    $file = $method->getDeclaringClass()->getFileName();
                    $resources[] = new FileResource($file);
                }
            }

            $resources = array_merge($resources, $this->router->getRouteCollection()->getResources());

            $data = parent::all($view);

            $cache->write(serialize($data), $resources);

            return $data;
        }

        // For BC
        if (method_exists($cache, 'getPath')) {
            $cachePath = $cache->getPath();
        } else {
            $cachePath = (string) $cache;
        }

        return unserialize(file_get_contents($cachePath));
    }

    /**
     * @param string $view
     *
     * @return ConfigCache
     */
    private function getViewCache($view)
    {
        return new ConfigCache($this->cacheFile . '.' . $view, $this->debug);
    }
}

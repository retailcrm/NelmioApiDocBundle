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
use Doctrine\Common\Util\ClassUtils;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\DataTypes;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use Nelmio\ApiDocBundle\Parser\PostParserInterface;
use Nelmio\ApiDocBundle\Util\DocCommentExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class ApiDocExtractor
{
    const ANNOTATION_CLASS = 'Nelmio\\ApiDocBundle\\Annotation\\ApiDoc';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var DocCommentExtractor
     */
    private $commentExtractor;

    /**
     * @var ParserInterface[]
     */
    protected $parsers = array();

    /**
     * @var HandlerInterface[]
     */
    protected $handlers;

    /**
     * @var AnnotationsProviderInterface[]
     */
    protected $annotationsProviders;

    public function __construct(ContainerInterface $container, RouterInterface $router, Reader $reader, DocCommentExtractor $commentExtractor, array $handlers, array $annotationsProviders)
    {
        $this->container            = $container;
        $this->router               = $router;
        $this->reader               = $reader;
        $this->commentExtractor     = $commentExtractor;
        $this->handlers             = $handlers;
        $this->annotationsProviders = $annotationsProviders;
    }

    /**
     * Return a list of route to inspect for ApiDoc annotation
     * You can extend this method if you don't want all the routes
     * to be included.
     *
     * @return Route[] An array of routes
     */
    public function getRoutes()
    {
        return $this->router->getRouteCollection()->all();
    }

    /**
     * Extracts annotations from all known routes
     *
     * @return array
     */
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        return $this->extractAnnotations($this->getRoutes(), $view);
    }

    /**
     * Extracts annotations from routes for specific version
     *
     * @param string $apiVersion API version
     * @param string $view
     *
     * @return array
     */
    public function allForVersion($apiVersion, $view = ApiDoc::DEFAULT_VIEW)
    {
        $data = $this->all($view);
        foreach ($data as $k => $a) {
            // ignore other api version's routes
            if (
                $a['annotation']->getRoute()->getDefault('_version') &&
                !version_compare($apiVersion ?? '', $a['annotation']->getRoute()->getDefault('_version'), '=')
            ) {
                unset($data[$k]);
            }
        }
        return $data;
    }

    /**
     * Returns an array of data where each data is an array with the following keys:
     *  - annotation
     *  - resource
     *
     * @param array $routes array of Route-objects for which the annotations should be extracted
     *
     * @return array
     */
    public function extractAnnotations(array $routes, $view = ApiDoc::DEFAULT_VIEW)
    {
        $array     = array();
        $resources = array();
        $excludeSections = $this->container->getParameter('nelmio_api_doc.exclude_sections');

        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new \InvalidArgumentException(sprintf('All elements of $routes must be instances of Route. "%s" given', gettype($route)));
            }

            if ($method = $this->getReflectionMethod($route->getDefault('_controller'))) {
                $annotation = $this->reader->getMethodAnnotation($method, static::ANNOTATION_CLASS);
                if (
                    $annotation && !in_array($annotation->getSection(), $excludeSections) &&
                    (in_array($view, $annotation->getViews()) || (0 === count($annotation->getViews()) && $view === ApiDoc::DEFAULT_VIEW))
                ) {
                    if ($annotation->isResource()) {
                        if ($resource = $annotation->getResource()) {
                            $resources[] = $resource;
                        } else {
                            // remove format from routes used for resource grouping
                            $resources[] = str_replace('.{_format}', '', $route->getPath() ?: '');
                        }
                    }

                    $array[] = array('annotation' => $this->extractData($annotation, $route, $method));
                }
            }
        }

        foreach ($this->annotationsProviders as $annotationProvider) {
            foreach ($annotationProvider->getAnnotations() as $annotation) {
                $route = $annotation->getRoute();
                $array[] = array('annotation' => $this->extractData($annotation, $route, $this->getReflectionMethod($route->getDefault('_controller'))));
            }
        }

        rsort($resources);
        foreach ($array as $index => $element) {
            $hasResource = false;
            $path        = $element['annotation']->getRoute()->getPath() ?: '';

            foreach ($resources as $resource) {
                if (0 === strpos($path, $resource) || $resource === $element['annotation']->getResource()) {
                    $array[$index]['resource'] = $resource;

                    $hasResource = true;
                    break;
                }
            }

            if (false === $hasResource) {
                $array[$index]['resource'] = 'others';
            }
        }

        $methodOrder = array('GET', 'POST', 'PUT', 'DELETE');
        usort($array, function ($a, $b) use ($methodOrder) {
            if ($a['resource'] === $b['resource']) {
                if ($a['annotation']->getRoute()->getPath() === $b['annotation']->getRoute()->getPath()) {
                    $methodA = array_search($a['annotation']->getRoute()->getMethods(), $methodOrder);
                    $methodB = array_search($b['annotation']->getRoute()->getMethods(), $methodOrder);

                    if ($methodA === $methodB) {
                        return strcmp(
                            implode('|', $a['annotation']->getRoute()->getMethods()),
                            implode('|', $b['annotation']->getRoute()->getMethods())
                        );
                    }

                    return $methodA > $methodB ? 1 : -1;
                }

                return strcmp(
                    $a['annotation']->getRoute()->getPath(),
                    $b['annotation']->getRoute()->getPath()
                );
            }

            return strcmp($a['resource'], $b['resource']);
        });

        return $array;
    }

    /**
     * Returns the ReflectionMethod for the given controller string.
     *
     * @param string $controller
     * @return \ReflectionMethod|null
     */
    public function getReflectionMethod($controller)
    {
        if (null === $controller) {
            return null;
        }

        if (preg_match('#(.+)::([\w]+)#', $controller, $matches)) {
            $class = $matches[1];
            $method = $matches[2];
        } else {
            if (preg_match('#(.+):([\w]+)#', $controller, $matches)) {
                $controller = $matches[1];
                $method = $matches[2];
            }

            if ($this->container->has($controller)) {
                // BC SF < 3.0
                if (method_exists($this->container, 'enterScope')) {
                    $this->container->enterScope('request');
                    $this->container->set('request', new Request(), 'request');
                }
                $class = ClassUtils::getRealClass(get_class($this->container->get($controller)));
                // BC SF < 3.0
                if (method_exists($this->container, 'enterScope')) {
                    $this->container->leaveScope('request');
                }

                if (!isset($method) && method_exists($class, '__invoke')) {
                    $method = '__invoke';
                }
            }
        }

        if (isset($class) && isset($method)) {
            try {
                return new \ReflectionMethod($class, $method);
            } catch (\ReflectionException $e) {
            }
        }

        return null;
    }

    /**
     * Returns an ApiDoc annotation.
     *
     * @param string $controller
     * @param string $route
     * @return ApiDoc|null
     */
    public function get($controller, $route)
    {
        if ($method = $this->getReflectionMethod($controller)) {
            if ($annotation = $this->reader->getMethodAnnotation($method, static::ANNOTATION_CLASS)) {
                if ($route = $this->router->getRouteCollection()->get($route)) {
                    return $this->extractData($annotation, $route, $method);
                }
            }
        }

        return null;
    }

    /**
     * Registers a class parser to use for parsing input class metadata
     *
     * @param ParserInterface $parser
     */
    public function addParser(ParserInterface $parser)
    {
        $this->parsers[] = $parser;
    }

    /**
     * Returns a new ApiDoc instance with more data.
     *
     * @param  ApiDoc            $annotation
     * @param  Route             $route
     * @param  \ReflectionMethod $method
     * @return ApiDoc
     */
    protected function extractData(ApiDoc $annotation, Route $route, \ReflectionMethod $method)
    {
        // create a new annotation
        $annotation = clone $annotation;

        // doc
        $annotation->setDocumentation($this->commentExtractor->getDocCommentText($method));

        // parse annotations
        $this->parseAnnotations($annotation, $route, $method);

        // route
        $annotation->setRoute($route);

        $inputs = array();
        if (null !== $annotation->getInputs()) {
            $inputs = $annotation->getInputs();
        } elseif (null !== $annotation->getInput()) {
            $inputs[] = $annotation->getInput();
        }

        // input (populates 'parameters' for the formatters)
        if (sizeof($inputs)) {
            $parameters = array();
            foreach ($inputs as $input) {
                $normalizedInput = $this->normalizeClassParameter($input);
                $supportedParsers = array();
                foreach ($this->getParsers($normalizedInput) as $parser) {
                    if ($parser->supports($normalizedInput)) {
                        $supportedParsers[] = $parser;
                        $parameters         = $this->mergeParameters($parameters, $parser->parse($normalizedInput));
                    }
                }

                foreach ($supportedParsers as $parser) {
                    if ($parser instanceof PostParserInterface) {
                        $parameters = $this->mergeParameters(
                            $parameters,
                            $parser->postParse($normalizedInput, $parameters)
                        );
                    }
                }
            }

            $parameters = $this->setParentClasses($parameters);
            $parameters = $this->clearClasses($parameters);
            $parameters = $this->generateHumanReadableTypes($parameters);

            if ('PATCH' === $annotation->getMethod()) {
                // All parameters are optional with PATCH (update)
                foreach ($parameters as $key => $val) {
                    $parameters[$key]['required'] = false;
                }
            }

            // merge parameters with parameters block from ApiDoc annotation in controller method
            $parameters = $this->mergeParameters($parameters, $annotation->getParameters());
            $annotation->setParameters($parameters);
        }

        // output (populates 'response' for the formatters)
        if (null !== $output = $annotation->getOutput()) {
            $response         = array();
            $supportedParsers = array();

            $normalizedOutput = $this->normalizeClassParameter($output);

            foreach ($this->getParsers($normalizedOutput) as $parser) {
                if ($parser->supports($normalizedOutput)) {
                    $supportedParsers[] = $parser;
                    $response = $this->mergeParameters($response, $parser->parse($normalizedOutput));
                }
            }

            foreach ($supportedParsers as $parser) {
                if ($parser instanceof PostParserInterface) {
                    $mp = $parser->postParse($normalizedOutput, $response);
                    $response = $this->mergeParameters($response, $mp);
                }
            }

            $response = $this->clearClasses($response);
            $response = $this->generateHumanReadableTypes($response);

            $annotation->setResponse($response);
            $annotation->setResponseForStatusCode($response, $normalizedOutput, 200);
        }

        if (count($annotation->getResponseMap()) > 0) {

            foreach ($annotation->getResponseMap() as $code => $modelName) {

                if ('200' === (string) $code && isset($modelName['type']) && isset($modelName['model'])) {
                    /*
                     * Model was already parsed as the default `output` for this ApiDoc.
                     */
                    continue;
                }

                $normalizedModel = $this->normalizeClassParameter($modelName);

                $parameters = array();
                $supportedParsers = array();
                foreach ($this->getParsers($normalizedModel) as $parser) {
                    if ($parser->supports($normalizedModel)) {
                        $supportedParsers[] = $parser;
                        $parameters = $this->mergeParameters($parameters, $parser->parse($normalizedModel));
                    }
                }

                foreach ($supportedParsers as $parser) {
                    if ($parser instanceof PostParserInterface) {
                        $mp = $parser->postParse($normalizedModel, $parameters);
                        $parameters = $this->mergeParameters($parameters, $mp);
                    }
                }

                $parameters = $this->setParentClasses($parameters);
                $parameters = $this->clearClasses($parameters);
                $parameters = $this->generateHumanReadableTypes($parameters);

                $annotation->setResponseForStatusCode($parameters, $normalizedModel, $code);

            }

        }

        return $annotation;
    }

    protected function normalizeClassParameter($input)
    {
        $defaults = array(
            'class'   => '',
            'groups'  => array(),
            'options'  => array(),
        );

        // normalize strings
        if (is_string($input)) {
            $input = array('class' => $input);
        }

        $collectionData = array();

        /*
         * Match array<Fully\Qualified\ClassName> as alias; "as alias" optional.
         */
        if (preg_match_all("/^array<([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\\\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*)>(?:\\s+as\\s+(.+))?$/", $input['class'], $collectionData)) {
            $input['class'] = $collectionData[1][0];
            $input['collection'] = true;
            $input['collectionName'] = $collectionData[2][0];
        } elseif (preg_match('/^array</', $input['class'])) { //See if a collection directive was attempted. Must be malformed.
            throw new \InvalidArgumentException(
                sprintf(
                    'Malformed collection directive: %s. Proper format is: array<Fully\\Qualified\\ClassName> or array<Fully\\Qualified\\ClassName> as collectionName',
                    $input['class']
                )
            );
        }

        // normalize groups
        if (isset($input['groups']) && is_string($input['groups'])) {
            $input['groups'] = array_map('trim', explode(',', $input['groups']));
        }

        return array_merge($defaults, $input);
    }

    /**
     * Merges two parameter arrays together. This logic allows documentation to be built
     * from multiple parser passes, with later passes extending previous passes:
     *  - Boolean values are true if any parser returns true.
     *  - Requirement parameters are concatenated.
     *  - Other string values are overridden by later parsers when present.
     *  - Array parameters are recursively merged.
     *  - Non-null default values prevail over null default values. Later values overrides previous defaults.
     *
     * However, if newly-returned parameter array contains a parameter with NULL, the parameter is removed from the merged results.
     * If the parameter is not present in the newly-returned array, then it is left as-is.
     *
     * @param  array $p1 The pre-existing parameters array.
     * @param  array $p2 The newly-returned parameters array.
     * @return array The resulting, merged array.
     */
    protected function mergeParameters($p1, $p2)
    {
        $params = $p1;

        foreach ($p2 as $propname => $propvalue) {

            if ($propvalue === null) {
                unset($params[$propname]);
                continue;
            }

            if (!isset($p1[$propname])) {
                $params[$propname] = $propvalue;
            } elseif (is_array($propvalue)) {
                $v1 = $p1[$propname];

                foreach ($propvalue as $name => $value) {
                    if (is_array($value)) {
                        if (isset($v1[$name]) && is_array($v1[$name])) {
                            $v1[$name] = $this->mergeParameters($v1[$name], $value);
                        } else {
                            $v1[$name] = $value;
                        }
                    } elseif (!is_null($value)) {
                        if (in_array($name, array('required', 'readonly'))) {
                            $v1[$name] = $v1[$name] || $value;
                        } elseif (in_array($name, array('requirement'))) {
                            if (isset($v1[$name])) {
                                $v1[$name] .= ', ' . $value;
                            } else {
                                $v1[$name] = $value;
                            }
                        } elseif ($name == 'default') {
                            if (isset($v1[$name])) {
                                $v1[$name] = isset($value) ? $value : $v1[$name];
                            } else {
                                $v1[$name] = isset($value) ? $value : null;
                            }
                        } else {
                            $v1[$name] = $value;
                        }
                    }
                }

                $params[$propname] = $v1;
            }
        }

        return $params;
    }

    /**
     * Parses annotations for a given method, and adds new information to the given ApiDoc
     * annotation. Useful to extract information from the FOSRestBundle annotations.
     *
     * @param ApiDoc           $annotation
     * @param Route            $route
     * @param ReflectionMethod $method
     */
    protected function parseAnnotations(ApiDoc $annotation, Route $route, \ReflectionMethod $method)
    {
        $annots = $this->reader->getMethodAnnotations($method);
        foreach ($this->handlers as $handler) {
            $handler->handle($annotation, $annots, $route, $method);
        }
    }

    /**
     * Set parent class to children
     *
     * @param  array $array The source array.
     * @return array The updated array.
     */
    protected function setParentClasses($array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                if (isset($v['children'])) {
                    if (isset($v['class'])) {
                        foreach ($v['children'] as $key => $item) {
                            $array[$k]['children'][$key]['parentClass'] = $v['class'];
                            $array[$k]['children'][$key]['field'] = $key;
                        }
                    }
                    $array[$k]['children'] = $this->setParentClasses($array[$k]['children']);
                }
            }
        }
        return $array;
    }

    /**
     * Clears the temporary 'class' parameter from the parameters array before it is returned.
     *
     * @param  array $array The source array.
     * @return array The cleared array.
     */
    protected function clearClasses($array)
    {
        if (is_array($array)) {
            unset($array['class']);
            foreach ($array as $name => $item) {
                $array[$name] = $this->clearClasses($item);
            }
        }

        return $array;
    }

    /**
     * Populates the `dataType` properties in the parameter array if empty. Recurses through children when necessary.
     *
     * @param  array $array
     * @return array
     */
    protected function generateHumanReadableTypes(array $array)
    {
        foreach ($array as $name => $info) {

            if (empty($info['dataType']) && array_key_exists('subType', $info)) {
                $array[$name]['dataType'] = $this->generateHumanReadableType($info['actualType'], $info['subType']);
            }

            if (isset($info['children'])) {
                $array[$name]['children'] = $this->generateHumanReadableTypes($info['children']);
            }
        }

        return $array;
    }

    /**
     * Creates a human-readable version of the `actualType`. `subType` is taken into account.
     *
     * @param  string $actualType
     * @param  string $subType
     * @return string
     */
    protected function generateHumanReadableType($actualType, $subType)
    {
        if ($actualType == DataTypes::MODEL) {

            if ($subType && class_exists($subType)) {
                $parts = explode('\\', $subType);

                return sprintf('object (%s)', end($parts));
            }

            return sprintf('object (%s)', $subType);
        }

        if ($actualType == DataTypes::COLLECTION) {

            if (DataTypes::isPrimitive($subType)) {
                return sprintf('array of %ss', $subType);
            }

            if ($subType && class_exists($subType)) {
                $parts = explode('\\', $subType);

                return sprintf('array of objects (%s)', end($parts));
            }

            return sprintf('array of objects (%s)', $subType);
        }

        return $actualType;
    }

    private function getParsers(array $parameters)
    {
        if (isset($parameters['parsers'])) {
            $parsers = array();
            foreach ($this->parsers as $parser) {
                if (in_array(get_class($parser), $parameters['parsers'])) {
                    $parsers[] = $parser;
                }
            }
        } else {
            $parsers = $this->parsers;
        }

        return $parsers;
    }
}

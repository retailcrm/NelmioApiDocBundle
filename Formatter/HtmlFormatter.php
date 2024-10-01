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

use Symfony\Component\Templating\EngineInterface;
use Twig\Environment as TwigEnvironment;

class HtmlFormatter extends AbstractFormatter
{
    /**
     * @var string
     */
    protected $apiName;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $defaultRequestFormat;

    /**
     * @var EngineInterface|TwigEnvironment
     */
    protected $engine;

    /**
     * @var bool
     */
    private $enableSandbox;

    /**
     * @var array
     */
    private $requestFormats;

    /**
     * @var string
     */
    private $requestFormatMethod;

    /**
     * @var string
     */
    private $acceptType;

    /**
     * @var array
     */
    private $bodyFormats;

    /**
     * @var string
     */
    private $defaultBodyFormat;

    /**
     * @var array
     */
    private $authentication;

    /**
     * @var string
     */
    private $motdTemplate;

    /**
     * @var bool
     */
    private $defaultSectionsOpened;

    public function setAuthentication(?array $authentication = null): void
    {
        $this->authentication = $authentication;
    }

    /**
     * @param string $apiName
     */
    public function setApiName($apiName): void
    {
        $this->apiName = $apiName;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint($endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @param bool $enableSandbox
     */
    public function setEnableSandbox($enableSandbox): void
    {
        $this->enableSandbox = $enableSandbox;
    }

    /**
     * @param EngineInterface|TwigEnvironment $engine
     */
    public function setTemplatingEngine($engine): void
    {
        $this->engine = $engine;
    }

    /**
     * @param string $acceptType
     */
    public function setAcceptType($acceptType): void
    {
        $this->acceptType = $acceptType;
    }

    public function setBodyFormats(array $bodyFormats): void
    {
        $this->bodyFormats = $bodyFormats;
    }

    /**
     * @param string $defaultBodyFormat
     */
    public function setDefaultBodyFormat($defaultBodyFormat): void
    {
        $this->defaultBodyFormat = $defaultBodyFormat;
    }

    /**
     * @param string $method
     */
    public function setRequestFormatMethod($method): void
    {
        $this->requestFormatMethod = $method;
    }

    public function setRequestFormats(array $formats): void
    {
        $this->requestFormats = $formats;
    }

    /**
     * @param string $format
     */
    public function setDefaultRequestFormat($format): void
    {
        $this->defaultRequestFormat = $format;
    }

    /**
     * @param string $motdTemplate
     */
    public function setMotdTemplate($motdTemplate): void
    {
        $this->motdTemplate = $motdTemplate;
    }

    /**
     * @return string
     */
    public function getMotdTemplate()
    {
        return $this->motdTemplate;
    }

    /**
     * @param bool $defaultSectionsOpened
     */
    public function setDefaultSectionsOpened($defaultSectionsOpened): void
    {
        $this->defaultSectionsOpened = $defaultSectionsOpened;
    }

    protected function renderOne(array $data)
    {
        return $this->engine->render('@NelmioApiDoc/resource.html.twig', array_merge(
            [
                'data' => $data,
                'displayContent' => true,
            ],
            $this->getGlobalVars()
        ));
    }

    protected function render(array $collection)
    {
        return $this->engine->render('@NelmioApiDoc/resources.html.twig', array_merge(
            [
                'resources' => $collection,
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * @return array
     */
    private function getGlobalVars()
    {
        return [
            'apiName' => $this->apiName,
            'authentication' => $this->authentication,
            'endpoint' => $this->endpoint,
            'enableSandbox' => $this->enableSandbox,
            'requestFormatMethod' => $this->requestFormatMethod,
            'acceptType' => $this->acceptType,
            'bodyFormats' => $this->bodyFormats,
            'defaultBodyFormat' => $this->defaultBodyFormat,
            'requestFormats' => $this->requestFormats,
            'defaultRequestFormat' => $this->defaultRequestFormat,
            'date' => date(DATE_RFC822),
            'css' => file_get_contents(__DIR__ . '/../Resources/public/css/screen.css'),
            'js' => file_get_contents(__DIR__ . '/../Resources/public/js/all.js'),
            'motdTemplate' => $this->motdTemplate,
            'defaultSectionsOpened' => $this->defaultSectionsOpened,
        ];
    }
}

<?php

namespace Nelmio\ApiDocBundle\Twig\Extension;

use Michelf\MarkdownExtra;
use Twig\Extension\AbstractExtension;

class MarkdownExtension extends AbstractExtension
{
    protected $markdownParser;

    public function __construct()
    {
        $this->markdownParser = new MarkdownExtra();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig\TwigFilter('extra_markdown', array($this, 'markdown'), array('is_safe' => array('html'))),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'nelmio_api_doc';
    }

    public function markdown($text)
    {
        return $this->markdownParser->transform($text);
    }
}

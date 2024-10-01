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

    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('extra_markdown', [$this, 'markdown'], ['is_safe' => ['html']]),
        ];
    }

    public function getName()
    {
        return 'nelmio_api_doc';
    }

    public function markdown($text)
    {
        return $this->markdownParser->transform($text);
    }
}

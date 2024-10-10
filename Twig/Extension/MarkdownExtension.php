<?php

namespace Nelmio\ApiDocBundle\Twig\Extension;

use Michelf\MarkdownExtra;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    private MarkdownExtra $markdownParser;

    public function __construct()
    {
        $this->markdownParser = new MarkdownExtra();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('extra_markdown', [$this, 'markdown'], ['is_safe' => ['html']]),
        ];
    }

    public function getName(): string
    {
        return 'nelmio_api_doc';
    }

    public function markdown($text): string
    {
        return $this->markdownParser->transform($text);
    }
}

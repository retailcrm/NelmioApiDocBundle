<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Command;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Formatter\HtmlFormatter;
use Nelmio\ApiDocBundle\Formatter\MarkdownFormatter;
use Nelmio\ApiDocBundle\Formatter\SimpleFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'api:doc:dump',
    description: 'Dumps API documentation in various formats',
)]
class DumpCommand extends Command
{
    private const AVAILABLE_FORMATS = ['markdown', 'json', 'html'];

    /**
     * @param TranslatorInterface&LocaleAwareInterface $translator
     */
    public function __construct(
        private readonly SimpleFormatter $simpleFormatter,
        private readonly MarkdownFormatter $markdownFormatter,
        private readonly HtmlFormatter $htmlFormatter,
        private readonly ApiDocExtractor $apiDocExtractor,
        private readonly TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'format', '', InputOption::VALUE_REQUIRED,
                'Output format like: ' . implode(', ', self::AVAILABLE_FORMATS),
                self::AVAILABLE_FORMATS[0]
            )
            ->addOption('api-version', null, InputOption::VALUE_REQUIRED, 'The API version')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale for translation')
            ->addOption('view', '', InputOption::VALUE_OPTIONAL, '', ApiDoc::DEFAULT_VIEW)
            ->addOption('no-sandbox', '', InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        $view = $input->getOption('view');

        $formatter = match ($format) {
            'json' => $this->simpleFormatter,
            'markdown' => $this->markdownFormatter,
            'html' => $this->htmlFormatter,
            default => throw new \RuntimeException(sprintf('Format "%s" not supported.', $format)),
        };

        if ($input->hasOption('locale')) {
            $this->translator->setLocale($input->getOption('locale') ?? '');
        }

        if ($input->hasOption('api-version')) {
            $formatter->setVersion($input->getOption('api-version'));
        }

        if ($formatter instanceof HtmlFormatter && $input->getOption('no-sandbox')) {
            $formatter->setEnableSandbox(false);
        }

        $extractedDoc = $input->hasOption('api-version') ?
            $this->apiDocExtractor->allForVersion($input->getOption('api-version'), $view) :
            $this->apiDocExtractor->all($view);

        $formattedDoc = $formatter->format($extractedDoc);

        if ('json' === $format) {
            $output->writeln(json_encode($formattedDoc, JSON_THROW_ON_ERROR));
        } else {
            $output->writeln($formattedDoc, OutputInterface::OUTPUT_RAW);
        }

        return 0;
    }
}

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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'api:doc:dump',
    description: 'Dumps API documentation in various formats',
)]
class DumpCommand extends Command
{
    /**
     * @var array
     */
    protected $availableFormats = array('markdown', 'json', 'html');

    /**
     * @param TranslatorInterface&LocaleAwareInterface $translator
     */
    public function __construct(
        private ContainerInterface $container,
        private TranslatorInterface $translator,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->addOption(
                'format', '', InputOption::VALUE_REQUIRED,
                'Output format like: ' . implode(', ', $this->availableFormats),
                $this->availableFormats[0]
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

        if ($format === 'json') {
            $formatter = $this->container->get('nelmio_api_doc.formatter.simple_formatter');
        } else {
            if (!in_array($format, $this->availableFormats)) {
                throw new \RuntimeException(sprintf('Format "%s" not supported.', $format));
            }

            $formatter = $this->container->get(sprintf('nelmio_api_doc.formatter.%s_formatter', $format));
        }

        if ($input->hasOption('locale')) {
            $this->translator->setLocale($input->getOption('locale') ?? '');
        }

        if ($input->hasOption('api-version')) {
            $formatter->setVersion($input->getOption('api-version'));
        }

        if ($input->getOption('no-sandbox') && 'html' === $format) {
            $formatter->setEnableSandbox(false);
        }

        $extractor = $this->container->get('nelmio_api_doc.extractor.api_doc_extractor');
        $extractedDoc = $input->hasOption('api-version') ?
            $extractor->allForVersion($input->getOption('api-version'), $view) :
            $extractor->all($view);

        $formattedDoc = $formatter->format($extractedDoc);

        if ('json' === $format) {
            $output->writeln(json_encode($formattedDoc));
        } else {
            $output->writeln($formattedDoc, OutputInterface::OUTPUT_RAW);
        }

        return 0;
    }
}

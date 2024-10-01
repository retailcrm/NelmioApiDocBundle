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

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Formatter\SwaggerFormatter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Console command to dump Swagger-compliant API definitions.
 *
 * @author Bez Hermoso <bez@activelamp.com>
 */
#[AsCommand(
    name: 'api:swagger:dump',
    description: 'Dumps Swagger-compliant API definitions.',
)]
class SwaggerDumpCommand extends Command
{
    private Filesystem $filesystem;

    public function __construct(
        private readonly ApiDocExtractor $extractor,
        private readonly SwaggerFormatter $formatter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->filesystem = new Filesystem();

        $this
            ->addOption('resource', 'r', InputOption::VALUE_OPTIONAL, 'A specific resource API declaration to dump.')
            ->addOption('list-only', 'l', InputOption::VALUE_NONE, 'Dump resource list only.')
            ->addOption('pretty', 'p', InputOption::VALUE_NONE, 'Dump as prettified JSON.')
            ->addArgument('destination', InputArgument::OPTIONAL, 'Directory to dump JSON files in.', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('list-only') && $input->getOption('resource')) {
            throw new \RuntimeException('Cannot selectively dump a resource with the --list-only flag.');
        }

        $apiDocs = $this->extractor->all();

        if ($input->getOption('list-only')) {
            $data = $this->getResourceList($apiDocs);
            $this->dump($data, null, $input, $output);

            return 0;
        }

        if (false !== ($resource = $input->getOption('resource'))) {
            $data = $this->getApiDeclaration($apiDocs, $resource);
            if (0 === count($data['apis'])) {
                throw new \InvalidArgumentException(sprintf('Resource "%s" does not exist.', $resource));
            }
            $this->dump($data, $resource, $input, $output);

            return 0;
        }

        /*
         * If --list-only and --resource is not specified, dump everything.
         */
        $data = $this->getResourceList($apiDocs);

        if (!$input->getArguments('destination')) {
            $output->writeln('');
            $output->writeln('<comment>Resource list: </comment>');
        }

        $this->dump($data, null, $input, $output, false);

        foreach ($data['apis'] as $api) {
            $resource = substr($api['path'], 1);
            if (!$input->getArgument('destination')) {
                $output->writeln('');
                $output->writeln(sprintf('<comment>API declaration for <info>"%s"</info> resource: </comment>', $resource));
            }
            $data = $this->getApiDeclaration($apiDocs, $resource, $output);
            $this->dump($data, $resource, $input, $output, false);
        }

        return 0;
    }

    protected function dump(array $data, $resource, InputInterface $input, OutputInterface $output, $treatAsFile = true): void
    {
        $destination = $input->getArgument('destination');

        $content = json_encode($data, $input->getOption('pretty') ? JSON_PRETTY_PRINT : 0);

        if (!$destination) {
            $output->writeln($content);

            return;
        }

        if (false === $treatAsFile) {
            if (!$this->filesystem->exists($destination)) {
                $this->filesystem->mkdir($destination);
            }
        }

        if (!$resource) {
            if (!$treatAsFile) {
                $destination = sprintf('%s/api-docs.json', rtrim($destination, '\\/'));
            }
            $message = sprintf('<comment>Dumping resource list to %s: </comment>', $destination);
            $this->writeToFile($content, $destination, $output, $message);

            return;
        }

        if (false === $treatAsFile) {
            $destination = sprintf('%s/%s.json', rtrim($destination, '\\/'), $resource);
        }

        $message = sprintf('<comment>Dump API declaration to %s: </comment>', $destination);
        $this->writeToFile($content, $destination, $output, $message);
    }

    protected function writeToFile($content, $file, OutputInterface $output, $message): void
    {
        try {
            $this->filesystem->dumpFile($file, $content);
            $message .= ' <info>OK</info>';
        } catch (IOException $e) {
            $message .= sprintf(' <error>NOT OK - %s</error>', $e->getMessage());
        }

        $output->writeln($message);
    }

    protected function getResourceList(array $data)
    {
        return $this->formatter->format($data);
    }

    protected function getApiDeclaration(array $data, $resource)
    {
        return $this->formatter->format($data, '/' . $resource);
    }
}

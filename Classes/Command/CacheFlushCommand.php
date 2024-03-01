<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheFlushCommand extends AbstractCacheCommand
{
    protected function configure(): void
    {
        $this
            ->setHelp('Flush the api client\'s various caches.')
            ->addOption('client', null, InputOption::VALUE_REQUIRED, 'The client alias as defined in additional config')
            ->addOption('requests', null, InputOption::VALUE_REQUIRED, 'Comma separated uid\'s of reusable requests whose cache should be flushed (use --all if you like to flush all)')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Add this option to flush the caches of all requests of the given client')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // get config
        if (!($clientAlias = $input->getOption('client'))) {
            $io->error('The required option "client" is missing.');

            return Command::FAILURE;
        }

        // requests
        $requests = $this->getRequests($input, $output);

        // error?
        if (is_int($requests)) {
            return $requests;
        }

        // schema
        $io->writeln('Flushing schema cache for client alias "' . $clientAlias . '"...');

        $this->cacheHelper->getApiClientCache()->flushByTag(
            'schema-' . $clientAlias
        );

        foreach ($requests as $request) {
            $io->writeln('Flushing cache for request ' . $request->getUid() . ': ' . $request->getPreparedEndpoint() . '...');

            $this->cacheHelper->flushAllReusableRequests($request->getUid());
        }

        $io->success('Specified caches successfully flushed.');

        return Command::SUCCESS;
    }
}

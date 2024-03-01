<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Xima\XimaApiClient\Domain\Model\ReusableRequest;

class CacheWarmupCommand extends AbstractCacheCommand
{
    protected function configure(): void
    {
        $this
            ->setHelp('Warmup the api client\'s various caches.')
            ->addOption('client', null, InputOption::VALUE_REQUIRED, 'The client alias as defined in additional config')
            ->addOption('requests', null, InputOption::VALUE_REQUIRED, 'Comma separated uid\'s of reusable requests whose cache should be warmed up (use --all if you like to warmup all)')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Add this option to warmup the caches of all requests of the given client')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $requests = $this->getRequests($input, $output);

        // error?
        if (is_int($requests)) {
            return $requests;
        }

        $this->apiClient->init($input->getOption('client'));

        /** @var ReusableRequest $request */
        foreach ($requests as $request) {
            if (!$request->getCacheLifetime() || !$request->getCacheLifetimePeriod()) {
                continue;
            }

            // flush in advance in order to get a fresh new cache entry
            $this->cacheHelper->flushAllReusableRequests($request->getUid());

            $io->write('Warming up cache for request ' . $request->getUid() . ' (if not already cached): ' . $request->getPreparedEndpoint() . '... ');

            $this->filterService->processFilterableRequest(
                $request,
                0,
                $this->cacheHelper->getCacheOptionsByReusableRequest($request)
            );

            $result = $this->cacheHelper->getCurrentReusableRequestCacheState($request->getUid());

            if ($result === false) {
                $io->writeln('Failed');
            } else {
                $io->writeln('OK');
            }
        }

        $io->success('Specified caches successfully warmed up.');

        return Command::SUCCESS;
    }
}

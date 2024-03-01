<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Xima\XimaApiClient\ApiClient\ApiClient;
use Xima\XimaApiClient\Domain\Repository\ReusableRequestRepository;
use Xima\XimaApiClient\Helper\CacheHelper;
use Xima\XimaApiClient\Service\FilterService;

abstract class AbstractCacheCommand extends Command
{
    public function __construct(
        protected readonly ReusableRequestRepository $reusableRequestRepository,
        protected readonly FilterService $filterService,
        protected readonly ApiClient $apiClient,
        protected readonly CacheHelper $cacheHelper
    ) {
        parent::__construct();
    }

    protected function getRequests(InputInterface $input, OutputInterface $output): int|array
    {
        $io = new SymfonyStyle($input, $output);

        $clientAlias = $input->getOption('client');

        if (!($requests = $input->getOption('requests')) && !$input->getOption('all')) {
            $io->error('You need to either define request uid\'s by adding --requests=1,2,3 or set --all to target all caches.');

            return Command::FAILURE;
        }

        if ($requests) {
            $requests = explode(',', (string)$requests);
            $result = [];

            foreach ($requests as $requestUid) {
                $request = $this->reusableRequestRepository->findByUid((int)$requestUid);

                if ($request->getClientAlias() !== $clientAlias) {
                    $io->warning("Skipping reusable request uid $requestUid because its client alias isn't \"$clientAlias\".");

                    continue;
                }

                $result[] = $request;
            }

            $requests = $result;
        } else {
            $requests = $this->reusableRequestRepository->findByClientAlias($clientAlias);
        }

        $result = [];

        foreach ($requests as $request) {
            if (!$request->getCacheLifetime() || !$request->getCacheLifetimePeriod()) {
                continue;
            }

            $result[] = $request;
        }

        return $result;
    }
}

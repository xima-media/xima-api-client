<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Xima\XimaApiClient\ApiClient\ApiClient;
use Xima\XimaApiClient\ApiClient\ApiSchema;

class ApiQueryCommand extends Command
{
    public function __construct(
        protected readonly ApiClient $apiClient,
        protected readonly ApiSchema $apiSchema,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Query the apis for debugging purposes.')
            ->addOption('client', null, InputOption::VALUE_REQUIRED, 'The client alias as defined in additional config')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Api Client Query');

        // get config
        if (!($clientAlias = $input->getOption('client'))) {
            $io->error('The required option "client" is missing.');

            return Command::FAILURE;
        }

        $this->apiClient->init($input->getOption('client'), true);

        // get endpoints from schema
        $schema = $this->apiSchema->getSchema();
        $endpoints = [];
        $index = 0;

        foreach ($schema['paths'] as $endpoint => $pathData) {
            foreach ($pathData as $method => $operationData) {
                if (!isset($operationData['operationId'])) {
                    continue;
                }

                $endpoints[++$index] = [
                    'index' => $index,
                    'method' => strtoupper((string)$method),
                    'operationId' => $operationData['operationId'] . '()',
                    'endpoint' => $endpoint,
                    'summary' => $operationData['summary'] ?? ($operationData['description'] ?? '-'),
                    'produces' => $operationData['produces'] ?? [],
                    'consumes' => $operationData['consumes'] ?? [],
                ];
            }
        }

        $endpointsTableData = $endpoints;

        array_walk($endpointsTableData, function (&$endpoint) {
            unset($endpoint['produces']);
            unset($endpoint['consumes']);

            return $endpoint;
        });

        $io->table(['ID', 'Method', 'Operation ID', 'Endpoint', 'Summary'], $endpointsTableData);

        $endpointId = -1;

        while (!in_array($endpointId, array_keys($endpoints))) {
            $endpointId = $io->ask('Which endpoint would you like to query? Please type in the ID as shown in the table above');
        }

        $endpointData = $endpoints[$endpointId];

        // display parameters
        $parameterTable = [];
        $parameterData = $this->apiSchema->getParametersByEndpointAndMethod(
            $endpointData['endpoint'],
            $endpointData['method']
        );

        foreach ($parameterData as $data) {
            $parameterTable[] = [
                $data['name'],
                $data['type'] ?? 'unknown',
                ($data['required'] ?? false) ? 'yes' : 'no',
                $data['in'] ?? 'unknown',
                $data['description'] ?? '-',
            ];
        }

        // endpoint io formats
        $io->writeln('Endpoint input/output formats:' . "\n");
        $io->table(['Produces', 'Consumes'], [[
            implode("\n", $endpointData['produces']),
            implode("\n", $endpointData['consumes']),
        ]]);

        // endpoint parameters
        $io->writeln('Endpoint parameters:' . "\n");
        $io->table(['Name', 'Type', 'Required', 'In', 'Description'], $parameterTable);

        // ask for parameters
        $parameters = [];

        foreach ($parameterData as $data) {
            $parameterValue = null;

            if ($data['required'] ?? false) {
                while ($parameterValue === null) {
                    $parameterValue = $io->ask('Please type in the value for the parameter "' . $data['name'] . '"');
                }
            } else {
                $parameterValue = $io->ask('Please type in the value for the parameter "' . $data['name'] . '"');
            }

            if ($parameterValue !== null) {
                $parameters[$data['name']] = $parameterValue;
            }
        }

        // prepare endpoint
        $endpoint = $this->apiClient->prepareEndpoint($endpointData['endpoint'], $endpointData['method'], $parameters);

        // TODO prepare body parameters for POST/PUT

        // do the request
        $io->writeln("Calling $endpoint...");

        if (false === ($response = $this->apiClient->request($endpoint, $endpointData['method']))) {
            $io->error("The ApiClient Request to $endpoint failed");

            return Command::FAILURE;
        }

        $io->success('An API response has been retrieved:');

        // pretty print result
        $io->writeln((string)json_encode(
            $response,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_PRETTY_PRINT |
            JSON_PARTIAL_OUTPUT_ON_ERROR |
            JSON_INVALID_UTF8_SUBSTITUTE
        ));

        return Command::SUCCESS;
    }
}

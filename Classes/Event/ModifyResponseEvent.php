<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Event;

use Psr\Http\Message\ResponseInterface;

class ModifyResponseEvent
{
    final public const NAME = 'xima_api_client.response.modify';

    public function __construct(
        protected mixed $response
    ) {
    }

    public function getResponse(): array|bool|ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(array|bool|ResponseInterface $response): void
    {
        $this->response = $response;
    }
}

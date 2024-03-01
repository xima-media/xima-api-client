<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Event;

use Xima\XimaApiClient\Domain\Model\ReusableRequest;

class ModifyRequestFiltersEvent
{
    final public const NAME = 'xima_api_client.request.filters.modify';

    public function __construct(
        protected ReusableRequest $reusableRequest,
        protected int $page
    ) {
    }

    public function getReusableRequest(): ReusableRequest
    {
        return $this->reusableRequest;
    }

    public function setReusableRequest(ReusableRequest $reusableRequest): void
    {
        $this->reusableRequest = $reusableRequest;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}

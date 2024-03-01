<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Event;

class ModifySchemaEvent
{
    final public const NAME = 'xima_api_client.schema.modify';

    public function __construct(
        protected array $schema
    ) {
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function setSchema(array $schema): void
    {
        $this->schema = $schema;
    }
}

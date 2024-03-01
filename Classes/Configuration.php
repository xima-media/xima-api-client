<?php

declare(strict_types=1);

namespace Xima\XimaApiClient;

class Configuration
{
    final public const EXT_KEY = 'xima_api_client';
    final public const EXT_NAME = 'XimaApiClient';
    final public const CACHE_IDENTIFIER = 'api_client_db_cache';
    final public const CACHE_DEFAULT_LIFETIME = 86400;
    final public const PLUGIN_NAME = 'ReusableRequest';
}

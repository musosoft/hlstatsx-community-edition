<?php

return [
    // Loaded after the environment file; bootstrap middleware may be resolved
    // before Dotenv has populated env() values.
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];

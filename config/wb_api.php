<?php

return [
    'host' => env('WB_API_HOST'),
    'key' => env('WB_API_KEY'),
    'limit' => (int) env('WB_API_LIMIT', 500),
    'date_from' => env('WB_API_DATE_FROM', '2020-01-01'),
    'date_to' => env('WB_API_DATE_TO', '2025-12-31'),
    'request_delay_ms' => (int) env('WB_API_REQUEST_DELAY_MS', 1100),
    'max_retries' => (int) env('WB_API_MAX_RETRIES', 15),
    'fresh_buffer_days' => (int) env('WB_API_FRESH_BUFFER_DAYS', 1),
    'default_service' => env('WB_API_DEFAULT_SERVICE', 'wb_test'),
];

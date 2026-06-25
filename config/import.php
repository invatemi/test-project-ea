<?php

return [
    'schedule_times' => array_map('intval', explode(',', env('IMPORT_SCHEDULE_TIMES', '8,20'))),
    'queue_worker' => env('RUN_QUEUE_WORKER', false),
];

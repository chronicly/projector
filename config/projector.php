<?php

use Chronhub\Contracts\Projecting\ProjectorOption;

return [
    'provider' => [
        'eloquent' => \Chronhub\Projector\Model\Projection::class,

        'in_memory' => \Chronhub\Projector\Model\InMemoryProjectionProvider::class
    ],

    'projectors' => [
        'default' => [
            'chronicler' => 'pgsql',
            'options' => 'lazy',
            'provider' => 'eloquent',
            'event_stream_provider' => 'eloquent', // key from chronicler config,
            'scope' => \Chronhub\Projector\Support\Scope\PgsqlProjectionQueryScope::class
        ],

        'in_memory' => [
            'chronicler' => 'in_memory',
            'options' => 'in_memory',
            'provider' => 'in_memory',
            'event_stream_provider' => 'in_memory', // key from chronicler config,
            'scope' => \Chronhub\Projector\Support\Scope\InMemoryProjectionQueryScope::class
        ]
    ],

    'options' => [
        'lazy' => [
            ProjectorOption::OPTION_PCNTL_DISPATCH => true,
            ProjectorOption::OPTION_LOCK_TIMEOUT_MS => 20000,
            ProjectorOption::OPTION_SLEEP => 10000,
            ProjectorOption::OPTION_UPDATE_LOCK_THRESHOLD => 15000,
            ProjectorOption::OPTION_PERSIST_BLOCK_SIZE => 1000,
            ProjectorOption::OPTION_RETRIES_MS => [0, 5, 100, 500, 1000],
            ProjectorOption::OPTION_DETECTION_WINDOWS => 'PT1S',
        ],

        'in_memory' => [
            ProjectorOption::OPTION_PCNTL_DISPATCH => false,
            ProjectorOption::OPTION_LOCK_TIMEOUT_MS => 0, // 0 === threshold, timeout has not effect
            ProjectorOption::OPTION_UPDATE_LOCK_THRESHOLD => 0,
            ProjectorOption::OPTION_SLEEP => 0,
            ProjectorOption::OPTION_PERSIST_BLOCK_SIZE => 1,// 1 === persist block size, sleep has no effect,
            ProjectorOption::OPTION_RETRIES_MS => [0, 5, 100, 500, 1000],
            ProjectorOption::OPTION_DETECTION_WINDOWS => 'PT1S',
        ],

        'snapshot' => [
            ProjectorOption::OPTION_PCNTL_DISPATCH => true,
            ProjectorOption::OPTION_LOCK_TIMEOUT_MS => 20000,
            ProjectorOption::OPTION_SLEEP => 10000,
            ProjectorOption::OPTION_UPDATE_LOCK_THRESHOLD => 15000,
            ProjectorOption::OPTION_PERSIST_BLOCK_SIZE => 100, // in sync with persist every x events
            ProjectorOption::OPTION_RETRIES_MS => [0, 5, 100, 500, 1000],
            ProjectorOption::OPTION_DETECTION_WINDOWS => 'PT1S',
        ],
    ],

    'console' => [
        'load_migrations' => true,
        'load_commands' => true,
        'commands' => [
            \Chronhub\Projector\Console\ReadProjectionCommand::class,
            \Chronhub\Projector\Console\WriteProjectionCommand::class,
            \Chronhub\Projector\Console\ProjectAllStreamCommand::class,
            \Chronhub\Projector\Console\ProjectCategoryStreamCommand::class,
            \Chronhub\Projector\Console\ProjectMessageNameCommand::class
        ]
    ]
];

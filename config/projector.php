<?php

use Chronhub\Contracts\Projecting\ProjectorOption;

return [
    'provider' => [
        'eloquent' => \Chronhub\Projector\Model\Projection::class,
    ],

    'projectors' => [
        'default' => [
            'chronicler' => 'pgsql',
            'options' => 'lazy',
            'provider' => 'eloquent',
            'event_stream_provider' => 'eloquent', // key from chronicler config,
            'scope' => \Chronhub\Projector\Support\Scope\ProjectionPgsqlQueryScope::class
        ],
    ],

    'options' => [
        'lazy' => [
            ProjectorOption::OPTION_PCNTL_DISPATCH => true,
            ProjectorOption::OPTION_LOCK_TIMEOUT_MS => 20000,
            ProjectorOption::OPTION_SLEEP => 10000,
            ProjectorOption::OPTION_UPDATE_LOCK_THRESHOLD => 15000,
            ProjectorOption::OPTION_PERSIST_BLOCK_SIZE => 1000,
        ],

        'in_memory' => [
            ProjectorOption::OPTION_PCNTL_DISPATCH => false,
            ProjectorOption::OPTION_LOCK_TIMEOUT_MS => 1,
            ProjectorOption::OPTION_SLEEP => 1,
            ProjectorOption::OPTION_UPDATE_LOCK_THRESHOLD => 1,
            ProjectorOption::OPTION_PERSIST_BLOCK_SIZE => 1,
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

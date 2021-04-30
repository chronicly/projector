<?php

use Chronhub\Contracts\Projecting\ProjectorOption;

return [

    /*
    |--------------------------------------------------------------------------
    | Projection provider
    |--------------------------------------------------------------------------
    |
    */

    'provider' => [

        'eloquent' => \Chronhub\Projector\Model\Projection::class,

        'in_memory' => \Chronhub\Projector\Model\InMemoryProjectionProvider::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Projectors
    |--------------------------------------------------------------------------
    |
    | Each projector is tied to an event store
    | caution as Dev is responsible to match connection between various services
    |
    |       chronicler:                 chronicler configuration key
    |       options:                    options key
    |       provider:                   projection provider key
    |       event_stream_provider:      from chronicler configuration key
    |       dispatch_projector_events:  dispatch on event projection status (start, stop, reset, delete)
    |       scope:                      projection query filter
    */

    'projectors' => [

        'default' => [
            'chronicler' => 'pgsql',
            'options' => 'lazy',
            'provider' => 'eloquent',
            'event_stream_provider' => 'eloquent',
            'dispatch_projector_events' => true,
            'scope' => \Chronhub\Projector\Support\Scope\PgsqlProjectionQueryScope::class
        ],

        'in_memory' => [
            'chronicler' => 'in_memory',
            'options' => 'in_memory',
            'provider' => 'in_memory',
            'event_stream_provider' => 'in_memory',
            'scope' => \Chronhub\Projector\Support\Scope\InMemoryProjectionQueryScope::class
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Projector options
    |--------------------------------------------------------------------------
    |
    | Options can be an array or a service implementing projector option contract
    | with pre defined options which can not be mutated
    |
    */
    'options' => [

        'default' => \Chronhub\Projector\Support\Option\ConstructableProjectorOption::class,

        'lazy' => \Chronhub\Projector\Support\Option\LazyProjectorOption::class,

        'in_memory' => \Chronhub\Projector\Support\Option\InMemoryProjectorOption::class,

        'snapshot' => [
            ProjectorOption::OPTION_PCNTL_DISPATCH => true,
            ProjectorOption::OPTION_STREAM_CACHE_SIZE => 1000,
            ProjectorOption::OPTION_LOCK_TIMEOUT_MS => 20000,
            ProjectorOption::OPTION_SLEEP => 10000,
            ProjectorOption::OPTION_UPDATE_LOCK_THRESHOLD => 15000,
            // in sync with persist every x events
            ProjectorOption::OPTION_PERSIST_BLOCK_SIZE => 100,
            ProjectorOption::OPTION_RETRIES_MS => [0, 5, 100, 500, 1000],
            ProjectorOption::OPTION_DETECTION_WINDOWS => 'PT60S',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Console and commands
    |--------------------------------------------------------------------------
    |
    */
    'console' => [

        'load_migrations' => true,

        'load_commands' => true,

        'commands' => [
            \Chronhub\Projector\Console\ReadProjectionCommand::class,
            \Chronhub\Projector\Console\WriteProjectionCommand::class,

            // commands below are only meant to optimize projection queries
            \Chronhub\Projector\Console\ProjectAllStreamCommand::class,
            \Chronhub\Projector\Console\ProjectCategoryStreamCommand::class,
            \Chronhub\Projector\Console\ProjectMessageNameCommand::class
        ]
    ]
];

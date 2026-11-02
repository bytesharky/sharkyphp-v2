<?php


declare (strict_types = 1);

return [
    'priority' => 20,
    'bindings' => [
        \Sharky\Console\Commanding\Resolver::class => [
            'concrete' => \Sharky\Console\Commanding\Resolver::class,
            'shared'   => true,
        ],
    ],
];

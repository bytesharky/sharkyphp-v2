<?php


declare (strict_types = 1);

use Sharky\Clock\SystemClock;

return [
    'priority' => 20,
    'bindings' => [

        // 真实时钟
        \Psr\Clock\ClockInterface::class                => [
            'concrete' => static fn() => SystemClock::fromSystemTimezone(),
            'shared'   => true,
        ],
    ],
];

<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->skip([
        __DIR__.'/bootstrap/cache',
        __DIR__.'/storage',
        __DIR__.'/vendor',
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_84);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ]);

    $rectorConfig->parallel();
};

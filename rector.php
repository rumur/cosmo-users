<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
       // define paths where to process...
    ]);

    $rectorConfig->bootstrapFiles([
    ]);

	$rectorConfig->phpVersion(
		PhpVersion::PHP_81,
	);

    // define sets of rules
    $rectorConfig->sets([
       LevelSetList::UP_TO_PHP_81,
       SetList::CODE_QUALITY,
       SetList::EARLY_RETURN,
       SetList::DEAD_CODE,
    ]);

    $rectorConfig->skip([
        // skip var annotation rule since it flags where we use it to coerce variable types
        Rector\CodeQuality\Rector\If_\CombineIfRector::class,
        Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class,
        Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class,
    ]);
};

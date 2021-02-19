<?php


namespace dbx12\jsonCensor\censorStrategies;


/**
 * Class NoopStrategy
 *
 * This strategy does not do anything and just returns what it got.
 *
 * @package dbx12\jsonRedact\censorStrategies
 */
final class NoopStrategy implements CensorStrategyInterface
{

    /**
     * @inheritDoc
     */
    public static function censorScalar($input)
    {
        return $input;
    }

    /**
     * @inheritDoc
     */
    public static function censorArray(array $input): array
    {
        return $input;
    }
}

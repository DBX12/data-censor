<?php


namespace dbx12\jsonCensor\censorStrategies;


/**
 * Class ConstantCensorStrategy
 *
 * Replaces the value with a predefined static value.
 * The values can be changed by changing the `$stringValue` and `$arrayValue` properties.
 *
 * @package dbx12\jsonRedact\censorStrategies
 */
class ConstantCensorStrategy implements CensorStrategyInterface
{
    public static $stringValue = '--censored--';
    public static $arrayValue = ['--censored--'];

    /**
     * @inheritDoc
     */
    public static function censorScalar($input): string
    {
        return self::$stringValue;
    }

    /**
     * @inheritDoc
     */
    public static function censorArray(array $input): array
    {
        return self::$arrayValue;
    }
}

<?php


namespace dbx12\jsonCensor\censorStrategies;

/**
 * Class HashCensorStrategy
 *
 * Censors the input by applying a hash function on the value (in case of an array, the function is applied to key and value)
 * The hash function can be changed by setting the property `$algorithm` to any supported by the hash() function.
 *
 * @see     https://www.php.net/manual/en/function.hash.php
 * @package dbx12\jsonRedact\censorStrategies
 */
class HashCensorStrategy implements CensorStrategyInterface
{
    public static $algorithm = 'md5';

    /**
     * @inheritDoc
     */
    public static function censorScalar($input): string
    {
        return hash(self::$algorithm, $input);
    }

    /**
     * @inheritDoc
     */
    public static function censorArray(array $input): array
    {
        $output = [];
        foreach ($input as $key => $value) {
            $censoredKey = self::censorScalar($key);
            if (is_array($value)) {
                $censoredValue = self::censorArray($value);
            } else {
                $censoredValue = self::censorScalar($value);
            }
            $output[$censoredKey] = $censoredValue;
        }
        return $output;
    }
}

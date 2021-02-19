<?php


namespace dbx12\jsonCensor\censorStrategies;


interface CensorStrategyInterface
{
    /**
     * Redacts a string / number
     *
     * @param string|int|float $input
     * @return mixed
     */
    public static function censorScalar($input);

    /**
     * Redacts an object / array
     *
     * @param array $input
     * @return mixed
     */
    public static function censorArray(array $input);
}

<?php

namespace dbx12\jsonCensor\tests\censorStrategies;

use dbx12\jsonCensor\censorStrategies\HashCensorStrategy;
use PHPUnit\Framework\TestCase;

class HashCensorStrategyTest extends TestCase
{

    /**
     * @covers \dbx12\jsonCensor\censorStrategies\HashCensorStrategy::censorScalar
     */
    public function testCensorScalar(): void
    {
        $input = 'my secret input';

        HashCensorStrategy::$algorithm = 'md5';
        $expected                      = hash('md5', $input);
        $actual                        = HashCensorStrategy::censorScalar($input);
        self::assertEquals($expected, $actual, 'string censored correctly');
    }

    /**
     * @covers \dbx12\jsonCensor\censorStrategies\HashCensorStrategy::censorArray
     * @uses \dbx12\jsonCensor\censorStrategies\HashCensorStrategy
     */
    public function testCensorArray(): void
    {
        $input    = [
            'secretKey1' => [
                'key1' => 'value1',
                'key2' => 'value2',
            ],
            'secretKey2' => 'value1',
        ];
        $expected = [
            md5('secretKey1') => [
                md5('key1') => md5('value1'),
                md5('key2') => md5('value2'),
            ],
            md5('secretKey2') => md5('value1'),
        ];
        HashCensorStrategy::$algorithm = 'md5';
        $actual   = HashCensorStrategy::censorArray($input);
        self::assertEquals($expected, $actual, 'array censored correctly');
    }

}

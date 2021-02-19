<?php

namespace dbx12\jsonCensor\tests\censorStrategies;

use dbx12\jsonCensor\censorStrategies\NoopStrategy;
use PHPUnit\Framework\TestCase;

class NoopStrategyTest extends TestCase
{

    /**
     * @covers \dbx12\jsonCensor\censorStrategies\NoopStrategy::censorScalar
     */
    public function testCensorScalar(): void
    {
        $input    = 'noopInput';
        $expected = 'noopInput';
        $actual   = NoopStrategy::censorScalar($input);
        self::assertEquals($expected, $actual, 'string censored correctly');
    }

    /**
     * @covers \dbx12\jsonCensor\censorStrategies\NoopStrategy::censorArray
     */
    public function testCensorArray(): void
    {
        $input    = ['noopKey' => 'noopValue', 'noopValue2'];
        $expected = ['noopKey' => 'noopValue', 'noopValue2'];
        $actual   = NoopStrategy::censorArray($input);
        self::assertEquals($expected, $actual, 'array censored correctly');
    }

}

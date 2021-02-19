<?php

namespace dbx12\jsonCensor\tests\censorStrategies;

use dbx12\jsonCensor\censorStrategies\ConstantCensorStrategy;
use PHPUnit\Framework\TestCase;

class ConstantCensorStrategyTest extends TestCase
{

    /**
     * @covers \dbx12\jsonCensor\censorStrategies\ConstantCensorStrategy::censorScalar
     */
    public function testCensorScalar(): void
    {
        $expected                            = '--testedCensor--';
        ConstantCensorStrategy::$stringValue = $expected;
        $actual                              = ConstantCensorStrategy::censorScalar('my secret input');
        self::assertEquals($expected, $actual, 'string censored correctly');
    }

    /**
     * @covers \dbx12\jsonCensor\censorStrategies\ConstantCensorStrategy::censorArray
     */
    public function testCensorArray(): void
    {
        $expected                           = ['--testedCensor--'];
        ConstantCensorStrategy::$arrayValue = $expected;
        $actual                             = ConstantCensorStrategy::censorArray(['secret key' => 'secret value']);
        self::assertEquals($expected, $actual, 'Array censored correctly');
    }
}

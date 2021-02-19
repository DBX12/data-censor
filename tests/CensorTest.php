<?php

namespace dbx12\jsonCensor\tests;

use dbx12\jsonCensor\Censor;
use dbx12\jsonCensor\censorStrategies\ConstantCensorStrategy;
use dbx12\jsonCensor\censorStrategies\HashCensorStrategy;
use dbx12\jsonCensor\censorStrategies\NoopStrategy;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class CensorTest extends TestCase
{

    use ReflectionHelperTrait;

    /** @var Censor */
    public $censor;

    public function setUp(): void
    {
        parent::setUp();
        $this->censor = new Censor();
    }

    /**
     * @covers \dbx12\jsonCensor\Censor::censorJsonFile
     * @uses   \dbx12\jsonCensor\Censor::hasRule
     * @uses   \dbx12\jsonCensor\Censor::internalCensor
     */
    public function testCensorJsonFile(): void
    {
        $inPath = __DIR__ . '/_data/input.json';

        $tmpFile        = tmpfile();
        $outPath        = stream_get_meta_data($tmpFile)['uri'];
        $expectedOutput = __DIR__ . '/_data/expectedOutput.json';
        $this->censor->censorJsonFile($inPath, $outPath);
        self::assertJsonFileEqualsJsonFile($expectedOutput, $outPath, 'output matches expectation');
    }

    /**
     * @covers       \dbx12\jsonCensor\Censor::censorJsonFile
     * @param string $inPath
     * @param string $outPath
     * @param string $message
     * @uses         \dbx12\jsonCensor\Censor::internalCensor
     * @dataProvider \dbx12\jsonCensor\tests\CensorTest::dataProvider_testCensorJsonFile_badInputs
     * @uses         \dbx12\jsonCensor\Censor::hasRule
     */
    public function testCensorJsonFile_badInputs(string $inPath, string $outPath, string $message): void
    {
        $this->expectExceptionMessage($message);
        $this->censor->censorJsonFile($inPath, $outPath);
    }

    public function dataProvider_testCensorJsonFile_badInputs(): array
    {
        $tmpFile = tmpfile();
        $outPath = stream_get_meta_data($tmpFile)['uri'];
        return [
            [
                '/not_a_file',
                $outPath,
                'File does not exist or is not readable',
            ], [
                __DIR__ . '/_data/bad_json.json',
                $outPath,
                'File does not contain valid JSON',
            ],
            [
                __DIR__ . '/_data/input.json',
                __DIR__,
                'Cannot write to outPath (is not writeable or is a directory)',
            ],
        ];
    }

    /**
     * @covers \dbx12\jsonCensor\Censor::addRule
     * @throws ReflectionException
     */
    public function testAddRule(): void
    {
        self::assertCount(0, $this->getInaccessibleProperty($this->censor, 'rules'));
        $this->censor->addRule('.key1', ['conditionKey' => ['val1', 'val2']], HashCensorStrategy::class);
        $expectedRules = [
            '.key1' => [
                [
                    'conditions' => [
                        'conditionKey' => ['val1', 'val2'],
                    ],
                    'strategy'   => HashCensorStrategy::class,
                ],
            ],
        ];
        $actualRules   = $this->getInaccessibleProperty($this->censor, 'rules');
        self::assertCount(1, $actualRules);
        self::assertEquals($expectedRules, $actualRules, 'rule with single path added correctly');

        $this->censor->addRule(['.key1', '.key2'], ['k2' => ['v1', 'v2']], NoopStrategy::class);
        $expectedRules = [
            '.key1' => [
                [
                    // this block already exists from the previous test case with the single path
                    'conditions' => [
                        'conditionKey' => ['val1', 'val2'],
                    ],
                    'strategy'   => HashCensorStrategy::class,
                ],
                [
                    'conditions' => [
                        'k2' => ['v1', 'v2'],
                    ],
                    'strategy'   => NoopStrategy::class,
                ],
            ],
            '.key2' => [
                [
                    'conditions' => [
                        'k2' => ['v1', 'v2'],
                    ],
                    'strategy'   => NoopStrategy::class,
                ],
            ],
        ];
        $actualRules   = $this->getInaccessibleProperty($this->censor, 'rules');
        self::assertEquals($expectedRules, $actualRules, 'rule with multiple paths added correctly');
    }

    /**
     * @covers \dbx12\jsonCensor\Censor::internalCensor
     * @uses   \dbx12\jsonCensor\Censor::addRule
     * @uses   \dbx12\jsonCensor\Censor::censor
     * @uses   \dbx12\jsonCensor\Censor::getStrategy
     * @uses   \dbx12\jsonCensor\Censor::meetsSingleConditionSet
     * @uses   \dbx12\jsonCensor\Censor::hasRule
     * @uses   \dbx12\jsonCensor\censorStrategies\ConstantCensorStrategy
     * @uses   \dbx12\jsonCensor\censorStrategies\NoopStrategy
     */
    public function testInternalCensor(): void
    {
        $this->censor->addRule('.group1.key1', ['field1' => 'val1'], ConstantCensorStrategy::class);
        $input    = [
            'group1' => [
                [
                    'key1'   => 'secret',
                    'field1' => 'val1',
                ],
                [
                    'key1'   => 'not secret',
                    'field1' => 'wrong',
                ],
                [
                    'key1'   => ['secret1', 'secret2'],
                    'field1' => 'val1',
                ],
            ],
        ];
        $expected = [
            'group1' => [
                [
                    'key1'   => '--censored--',
                    'field1' => 'val1',
                ],
                [
                    'key1'   => 'not secret',
                    'field1' => 'wrong',
                ],
                [
                    'key1'   => ['--censored--'],
                    'field1' => 'val1',
                ],
            ],
        ];
        $actual   = $this->censor->censor($input);
        self::assertEquals($expected, $actual, 'censored correctly');
    }

    /**
     * @covers  \dbx12\jsonCensor\Censor::hasRule
     * @throws ReflectionException
     * @uses    \dbx12\jsonCensor\Censor::addRule
     */
    public function testHasRule(): void
    {
        $this->censor->addRule('.existent.path', ['conditionKey' => ['val1', 'val2']], HashCensorStrategy::class);
        $actual = $this->invokeMethod($this->censor, 'hasRule', ['.existent.path']);
        self::assertTrue($actual, 'rule for existent path is found');

        $actual = $this->invokeMethod($this->censor, 'hasRule', ['.nonexistent.path']);
        self::assertFalse($actual, 'rule for nonexistent path is not found');
    }

    /**
     * @covers \dbx12\jsonCensor\Censor::getStrategy
     * @throws ReflectionException
     * @uses   \dbx12\jsonCensor\Censor::addRule
     * @uses   \dbx12\jsonCensor\Censor::meetsSingleConditionSet
     */
    public function testGetStrategy(): void
    {
        $input = [
            'conditionKey' => 'val1',
        ];
        $this->censor->addRule('.existent.path', ['conditionKey' => ['val1', 'val2']], HashCensorStrategy::class);
        $actualStrategy = $this->invokeMethod($this->censor, 'getStrategy', ['.existent.path', $input]);
        self::assertEquals(HashCensorStrategy::class, $actualStrategy, 'correct strategy returned for existent path');

        $input          = [
            'conditionKey' => 'wrong',
        ];
        $actualStrategy = $this->invokeMethod($this->censor, 'getStrategy', ['.existent.path', $input]);
        self::assertEquals(NoopStrategy::class, $actualStrategy, 'noop strategy returned for unmatched conditions');
    }

    /**
     * @covers       \dbx12\jsonCensor\Censor::meetsSingleConditionSet
     * @dataProvider \dbx12\jsonCensor\tests\CensorTest::dataProvider_testMeetsSingleConditionSet
     * @param array  $input
     * @param bool   $expectation
     * @param string $message
     * @throws ReflectionException
     */
    public function testMeetsSingleConditionSet(array $input, bool $expectation, string $message): void
    {
        $conditionSet = [
            'field_contains' => ['val1', 'val2'],
            'field_equal'    => 'val_constant',
        ];
        $actual       = $this->invokeMethod($this->censor, 'meetsSingleConditionSet', [$conditionSet, $input]);
        self::assertEquals($expectation, $actual, $message);
    }

    public function dataProvider_testMeetsSingleConditionSet(): array
    {
        return [
            'wrong constant value' => [
                [
                    'field_contains' => 'val1',
                    'field_equal'    => 'wrong',
                ],
                false,
                'mismatching',
            ],
            'wrong group value'    => [
                [
                    'field_contains' => 'wrong',
                    'field_equal'    => 'val_constant',
                ],
                false,
                'mismatching',
            ],
            'good input'           => [
                [
                    'field_contains' => 'val1',
                    'field_equal'    => 'val_constant',
                ],
                true,
                'matching',
            ],
        ];
    }
}

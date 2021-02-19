<?php


namespace dbx12\jsonCensor;


use dbx12\jsonCensor\censorStrategies\CensorStrategyInterface;
use dbx12\jsonCensor\censorStrategies\ConstantCensorStrategy;
use dbx12\jsonCensor\censorStrategies\NoopStrategy;
use RuntimeException;

class Censor
{
    public $defaultStrategy = ConstantCensorStrategy::class;

    protected $rules = [
    ];

    /**
     * Adds a rule for one or more paths
     *
     * @param string|string[] $path      One or more paths this rule applies to
     * @param array           $condition The condition which must be met, empty array for "matches always"
     * @param string|null     $strategy  The strategy for the censor. If not specified, it defaults to Censor::$defaultStrategy during Censor::censor()
     */
    public function addRule($path, array $condition = [], string $strategy = null): void
    {
        $rule = [
            'conditions' => $condition,
        ];
        if ($strategy) {
            $rule['strategy'] = $strategy;
        }
        if (is_array($path)) {
            foreach ($path as $singlePath) {
                $this->rules[$singlePath][] = $rule;
            }
        } else {
            $this->rules[$path][] = $rule;
        }
    }

    /**
     * Censors an array with given rule set.
     *
     * @param array $input
     * @return array
     * @codeCoverageIgnore as this is only a wrapper method
     */
    public function censor(array $input): array
    {
        return $this->internalCensor($input, '');
    }

    /**
     * Reads a file, parses the contents as JSON and censors them.
     * Puts the censored contents encoded as JSON into the file at $outPath
     *
     * @param string $inPath          Path to the input file
     * @param string $outPath         Path to the output file
     * @param int    $jsonEncodeFlags Flags to give to json_encode()
     * @throws RuntimeException If the input file does not exist or does not contain valid Json, the output file exists
     *                                and is not writeable or the output path points to a directory.
     */
    public function censorJsonFile(string $inPath, string $outPath, int $jsonEncodeFlags = 0): void
    {
        if (!is_readable($inPath) || !is_file($inPath)) {
            throw new RuntimeException('File does not exist or is not readable');
        }
        if (
            is_dir($outPath)
            || (file_exists($outPath) && !is_writable($outPath))) {
            throw new RuntimeException('Cannot write to outPath (is not writeable or is a directory)');
        }

        $fileContents = file_get_contents($inPath);
        $inJson       = json_decode($fileContents, true);
        if ($inJson === null) {
            throw new RuntimeException('File does not contain valid JSON');
        }
        $outJson = $this->censor((array) $inJson);
        file_put_contents($outPath, json_encode($outJson, $jsonEncodeFlags));
    }

    /**
     * Internal, recursive censor function. Censors given structure.
     *
     * @param array  $input
     * @param string $path
     * @return array
     */
    protected function internalCensor(array &$input, string $path): array
    {
        foreach ($input as $key => &$value) {
            if (!is_int($key)) {
                $localPath = "${path}.${key}";
            } else {
                $localPath = $path;
            }

            if ($this->hasRule($localPath)) {
                /** @var CensorStrategyInterface $strategy */
                $strategy = $this->getStrategy($localPath, $input);
                if (is_array($value)) {
                    $value = $strategy::censorArray($value);
                } else {
                    $value = $strategy::censorScalar($value);
                }
            } else {
                // no rule exists
                // go deeper
                /** @noinspection NestedPositiveIfStatementsInspection */
                if (is_array($value)) {
                    $this->internalCensor($value, $localPath);
                }
            }
        }
        return $input;
    }

    /**
     * Check if a rule for a given path exists.
     *
     * @param string $path
     * @return bool
     */
    protected function hasRule(string $path): bool
    {
        return array_key_exists($path, $this->rules);
    }

    /**
     * Find the strategy for this field. If none is found, the NoopStrategy is returned.
     *
     * @param string $path  The path to find a strategy for
     * @param array  $input The current node to work on
     * @return string Class name of a class implementing CensorStrategyInterface
     */
    protected function getStrategy(string $path, array $input): string
    {
        $rule = $this->rules[$path];
        foreach ($rule as $conditionStrategy) {
            if ($this->meetsSingleConditionSet($conditionStrategy['conditions'], $input)) {
                // meets conditions, we have the strategy
                return $conditionStrategy['strategy'] ?? $this->defaultStrategy;
            }
        }
        return NoopStrategy::class;
    }

    /**
     * Checks if a single set of conditions is met.
     * Multiple conditions are connected with a boolean AND.
     *
     * @param array $conditionSet Condition description
     * @param array $input        Input node
     * @return bool
     */
    protected function meetsSingleConditionSet(array $conditionSet, array $input): bool
    {
        foreach ($conditionSet as $field => $value) {
            // handle array-type conditions
            if (is_array($value)) {
                if (!in_array($input[$field], $value, true)) {
                    return false;
                }
            } else {
                /** @noinspection TypeUnsafeComparisonInspection */
                /** @noinspection NestedPositiveIfStatementsInspection */
                if ($input[$field] != $value) {
                    return false;
                }
            }
        }
        return true;
    }
}

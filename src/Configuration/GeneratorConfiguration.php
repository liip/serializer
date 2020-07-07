<?php

declare(strict_types=1);

namespace Liip\Serializer\Configuration;

/**
 * Configuration for the serializer generator.
 *
 * The configuration has a list of default group combinations, a list of
 * default versions, and a list of classes. For each class, you can overwrite
 * the group combinations. For each group combination, you can again overwrite
 * the versions to generate.
 */
class GeneratorConfiguration implements \IteratorAggregate
{
    /**
     * A list of group combinations.
     *
     * @see GroupCombination::$groups
     *
     * @var string[][]
     */
    private $defaultGroupCombinations;

    /**
     * List of versions to generate. An empty string '' means to generate without a version.
     * e.g. ['', '2', '3']
     *
     * @var string[]
     */
    private $defaultVersions;

    /**
     * @var ClassToGenerate[]
     */
    private $classesToGenerate = [];

    public function __construct(array $defaultGroupCombinations, array $defaultVersions)
    {
        $this->defaultGroupCombinations = $defaultGroupCombinations ?: [[]];
        $this->defaultVersions = array_map('strval', $defaultVersions) ?: [''];
    }

    /**
     * Create configuration from array definition
     *
     * [
     *     'default_group_combinations' => ['api'],
     *     'default_versions' => ['', '1', '2'],
     *     'classes' => [
     *          Product::class => [
     *              'default_versions' => ['1', '2'], // optional, falls back to global list
     *              'group_combinations' => [ // optional, falls back to global default_group_combinations
     *                  [
     *                      'groups' => [], // generate without groups
     *                  ],
     *                  [
     *                      'groups' => ['api'], // global groups are overwritten, not merged. versions are taken from class default
     *                  ],
     *                  [
     *                      'groups' => ['api', 'detail'],
     *                      'versions' => ['2'], // only generate the combination of api and detail for version 2
     *                  ],
     *              ],
     *          ],
     *          Other::class => [], // generate this class with default groups and versions
     *     ]
     * ]
     */
    public static function createFomArray(array $config): self
    {
        if (!\array_key_exists('classes', $config) || \count($config['classes']) < 1) {
            throw new \InvalidArgumentException('You need to specify the classes to generate');
        }
        $instance = new self($config['default_group_combinations'] ?? [], $config['default_versions'] ?? []);
        foreach ($config['classes'] as $className => $classConfig) {
            $classToGenerate = new ClassToGenerate($instance, $className, $classConfig['default_versions'] ?? null);
            foreach ($classConfig['group_combinations'] ?? [] as $groupCombination) {
                $classToGenerate->addGroupCombination(
                    new GroupCombination($classToGenerate, $groupCombination['groups'], $groupCombination['versions'] ?? null)
                );
            }
            $instance->addClassToGenerate($classToGenerate);
        }

        return $instance;
    }

    public function addClassToGenerate(ClassToGenerate $classToGenerate): void
    {
        $this->classesToGenerate[] = $classToGenerate;
    }

    /**
     * @return string[]
     */
    public function getDefaultVersions(): array
    {
        return $this->defaultVersions;
    }

    public function getDefaultGroupCombinations(ClassToGenerate $classToGenerate): array
    {
        return array_map(static function (array $combination) use ($classToGenerate) {
            return new GroupCombination($classToGenerate, $combination);
        }, $this->defaultGroupCombinations);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->classesToGenerate);
    }
}

<?php

namespace GregPriday\Version\Tests\Unit;

use GregPriday\Version\Constraint\VersionConstraint;
use GregPriday\Version\Constraint\VersionConstraintSet;
use GregPriday\Version\Constraint\VersionRangeSet;
use GregPriday\Version\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionRangeSetTest extends TestCase
{
    /**
     * Test constructor with valid constraint sets.
     */
    public function test_constructor_with_valid_constraint_sets(): void
    {
        $constraintSet1 = new VersionConstraintSet([
            new VersionConstraint('>=', new Version('1.0.0')),
            new VersionConstraint('<', new Version('2.0.0')),
        ]);
        $constraintSet2 = new VersionConstraintSet([
            new VersionConstraint('>=', new Version('3.0.0')),
            new VersionConstraint('<', new Version('4.0.0')),
        ]);

        $rangeSet = new VersionRangeSet([$constraintSet1, $constraintSet2]);
        $this->assertInstanceOf(VersionRangeSet::class, $rangeSet);
        $this->assertEquals([$constraintSet1, $constraintSet2], $rangeSet->getConstraintSets());
    }

    /**
     * Test constructor with invalid constraint sets.
     */
    public function test_constructor_with_invalid_constraint_sets(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All constraint sets must be instances of');

        new VersionRangeSet(['not a constraint set']);
    }

    /**
     * Test adding constraint sets.
     */
    public function test_add_constraint_set(): void
    {
        $constraintSet1 = new VersionConstraintSet([
            new VersionConstraint('>=', new Version('1.0.0')),
            new VersionConstraint('<', new Version('2.0.0')),
        ]);
        $constraintSet2 = new VersionConstraintSet([
            new VersionConstraint('>=', new Version('3.0.0')),
            new VersionConstraint('<', new Version('4.0.0')),
        ]);

        $rangeSet = new VersionRangeSet;
        $this->assertEmpty($rangeSet->getConstraintSets());

        $rangeSet->addConstraintSet($constraintSet1);
        $this->assertCount(1, $rangeSet->getConstraintSets());
        $this->assertSame($constraintSet1, $rangeSet->getConstraintSets()[0]);

        $rangeSet->addConstraintSet($constraintSet2);
        $this->assertCount(2, $rangeSet->getConstraintSets());
        $this->assertSame($constraintSet2, $rangeSet->getConstraintSets()[1]);
    }

    /**
     * Test checking if a version satisfies an empty range set.
     */
    public function test_is_satisfied_by_with_empty_range_set(): void
    {
        $rangeSet = new VersionRangeSet;
        $version = new Version('1.0.0');

        // An empty range set should accept any version
        $this->assertTrue($rangeSet->isSatisfiedBy($version));
    }

    /**
     * Test checking if a version satisfies a range set with one constraint set.
     */
    public function test_is_satisfied_by_with_single_constraint_set(): void
    {
        $constraintSet = new VersionConstraintSet([
            new VersionConstraint('>=', new Version('1.0.0')),
            new VersionConstraint('<', new Version('2.0.0')),
        ]);
        $rangeSet = new VersionRangeSet([$constraintSet]);

        $this->assertTrue($rangeSet->isSatisfiedBy(new Version('1.0.0')));
        $this->assertTrue($rangeSet->isSatisfiedBy(new Version('1.5.0')));
        $this->assertFalse($rangeSet->isSatisfiedBy(new Version('0.9.0')));
        $this->assertFalse($rangeSet->isSatisfiedBy(new Version('2.0.0')));
    }

    /**
     * Test checking if a version satisfies a range set with multiple constraint sets.
     */
    public function test_is_satisfied_by_with_multiple_constraint_sets(): void
    {
        $constraintSet1 = new VersionConstraintSet([
            new VersionConstraint('>=', new Version('1.0.0')),
            new VersionConstraint('<', new Version('2.0.0')),
        ]);
        $constraintSet2 = new VersionConstraintSet([
            new VersionConstraint('>=', new Version('3.0.0')),
            new VersionConstraint('<', new Version('4.0.0')),
        ]);
        $rangeSet = new VersionRangeSet([$constraintSet1, $constraintSet2]);

        // Version must satisfy ANY constraint set in the range set (OR logic)
        $this->assertTrue($rangeSet->isSatisfiedBy(new Version('1.0.0')));
        $this->assertTrue($rangeSet->isSatisfiedBy(new Version('1.5.0')));
        $this->assertTrue($rangeSet->isSatisfiedBy(new Version('3.0.0')));
        $this->assertTrue($rangeSet->isSatisfiedBy(new Version('3.5.0')));
        $this->assertFalse($rangeSet->isSatisfiedBy(new Version('0.9.0')));
        $this->assertFalse($rangeSet->isSatisfiedBy(new Version('2.0.0')));
        $this->assertFalse($rangeSet->isSatisfiedBy(new Version('2.5.0')));
        $this->assertFalse($rangeSet->isSatisfiedBy(new Version('4.0.0')));
    }

    /**
     * Test the combined functionality with real-world examples.
     */
    #[DataProvider('realWorldRangeSetsProvider')]
    public function test_real_world_range_sets(array $orSets, string $versionString, bool $expected): void
    {
        $constraintSets = [];
        foreach ($orSets as $andSet) {
            $constraints = [];
            foreach ($andSet as $constraintStr) {
                // Parse the constraint string manually for testing
                preg_match('/^([<>=^~!]+)(.+)$/', $constraintStr, $matches);
                $operator = $matches[1];
                $version = new Version($matches[2]);
                $constraints[] = new VersionConstraint($operator, $version);
            }
            $constraintSets[] = new VersionConstraintSet($constraints);
        }

        $rangeSet = new VersionRangeSet($constraintSets);
        $version = new Version($versionString);

        $this->assertEquals($expected, $rangeSet->isSatisfiedBy($version));
    }

    /**
     * Data provider for real-world range sets.
     */
    public static function realWorldRangeSetsProvider(): array
    {
        return [
            // (>=1.0.0 <2.0.0) || (>=3.0.0 <4.0.0)
            [[['>=1.0.0', '<2.0.0'], ['>=3.0.0', '<4.0.0']], '1.0.0', true],
            [[['>=1.0.0', '<2.0.0'], ['>=3.0.0', '<4.0.0']], '1.5.0', true],
            [[['>=1.0.0', '<2.0.0'], ['>=3.0.0', '<4.0.0']], '2.0.0', false],
            [[['>=1.0.0', '<2.0.0'], ['>=3.0.0', '<4.0.0']], '2.5.0', false],
            [[['>=1.0.0', '<2.0.0'], ['>=3.0.0', '<4.0.0']], '3.0.0', true],
            [[['>=1.0.0', '<2.0.0'], ['>=3.0.0', '<4.0.0']], '3.5.0', true],
            [[['>=1.0.0', '<2.0.0'], ['>=3.0.0', '<4.0.0']], '4.0.0', false],

            // (^1.0.0) || (^2.0.0)
            [[['^1.0.0'], ['^2.0.0']], '1.0.0', true],
            [[['^1.0.0'], ['^2.0.0']], '1.9.9', true],
            [[['^1.0.0'], ['^2.0.0']], '2.0.0', true],
            [[['^1.0.0'], ['^2.0.0']], '2.9.9', true],
            [[['^1.0.0'], ['^2.0.0']], '3.0.0', false],
            [[['^1.0.0'], ['^2.0.0']], '0.9.9', false],

            // (~1.2.3) || (>=2.0.0 <3.0.0)
            [[['~1.2.3'], ['>=2.0.0', '<3.0.0']], '1.2.3', true],
            [[['~1.2.3'], ['>=2.0.0', '<3.0.0']], '1.2.9', true],
            [[['~1.2.3'], ['>=2.0.0', '<3.0.0']], '1.3.0', false],
            [[['~1.2.3'], ['>=2.0.0', '<3.0.0']], '2.0.0', true],
            [[['~1.2.3'], ['>=2.0.0', '<3.0.0']], '2.9.9', true],
            [[['~1.2.3'], ['>=2.0.0', '<3.0.0']], '3.0.0', false],
        ];
    }
}

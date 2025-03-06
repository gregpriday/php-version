<?php

namespace GregPriday\Version\Tests\Unit;

use GregPriday\Version\Constraint\VersionConstraint;
use GregPriday\Version\Constraint\VersionConstraintSet;
use GregPriday\Version\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionConstraintSetTest extends TestCase
{
    /**
     * Test constructor with valid constraints.
     */
    public function test_constructor_with_valid_constraints(): void
    {
        $constraints = [
            new VersionConstraint('>=', new Version('1.0.0')),
            new VersionConstraint('<', new Version('2.0.0')),
        ];

        $constraintSet = new VersionConstraintSet($constraints);
        $this->assertInstanceOf(VersionConstraintSet::class, $constraintSet);
        $this->assertEquals($constraints, $constraintSet->getConstraints());
    }

    /**
     * Test constructor with invalid constraints.
     */
    public function test_constructor_with_invalid_constraints(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All constraints must be instances of');

        new VersionConstraintSet(['not a constraint']);
    }

    /**
     * Test adding constraints.
     */
    public function test_add_constraint(): void
    {
        $constraint1 = new VersionConstraint('>=', new Version('1.0.0'));
        $constraint2 = new VersionConstraint('<', new Version('2.0.0'));

        $constraintSet = new VersionConstraintSet;
        $this->assertEmpty($constraintSet->getConstraints());

        $constraintSet->addConstraint($constraint1);
        $this->assertCount(1, $constraintSet->getConstraints());
        $this->assertSame($constraint1, $constraintSet->getConstraints()[0]);

        $constraintSet->addConstraint($constraint2);
        $this->assertCount(2, $constraintSet->getConstraints());
        $this->assertSame($constraint2, $constraintSet->getConstraints()[1]);
    }

    /**
     * Test checking if a version satisfies an empty constraint set.
     */
    public function test_is_satisfied_by_with_empty_constraint_set(): void
    {
        $constraintSet = new VersionConstraintSet;
        $version = new Version('1.0.0');

        // An empty constraint set should accept any version
        $this->assertTrue($constraintSet->isSatisfiedBy($version));
    }

    /**
     * Test checking if a version satisfies a constraint set with one constraint.
     */
    public function test_is_satisfied_by_with_single_constraint(): void
    {
        $constraint = new VersionConstraint('>=', new Version('1.0.0'));
        $constraintSet = new VersionConstraintSet([$constraint]);

        $this->assertTrue($constraintSet->isSatisfiedBy(new Version('1.0.0')));
        $this->assertTrue($constraintSet->isSatisfiedBy(new Version('1.1.0')));
        $this->assertFalse($constraintSet->isSatisfiedBy(new Version('0.9.0')));
    }

    /**
     * Test checking if a version satisfies a constraint set with multiple constraints.
     */
    public function test_is_satisfied_by_with_multiple_constraints(): void
    {
        $constraints = [
            new VersionConstraint('>=', new Version('1.0.0')),
            new VersionConstraint('<', new Version('2.0.0')),
        ];
        $constraintSet = new VersionConstraintSet($constraints);

        // Version must satisfy ALL constraints in the set
        $this->assertTrue($constraintSet->isSatisfiedBy(new Version('1.0.0')));
        $this->assertTrue($constraintSet->isSatisfiedBy(new Version('1.5.0')));
        $this->assertTrue($constraintSet->isSatisfiedBy(new Version('1.9.9')));
        $this->assertFalse($constraintSet->isSatisfiedBy(new Version('0.9.9')));
        $this->assertFalse($constraintSet->isSatisfiedBy(new Version('2.0.0')));
    }

    /**
     * Test the combined functionality with real-world examples.
     */
    #[DataProvider('realWorldConstraintSetsProvider')]
    public function test_real_world_constraint_sets(array $constraintStrings, string $versionString, bool $expected): void
    {
        $constraints = [];
        foreach ($constraintStrings as $str) {
            // Parse the constraint string manually for testing
            preg_match('/^([<>=^~!]+)(.+)$/', $str, $matches);
            $operator = $matches[1];
            $version = new Version($matches[2]);
            $constraints[] = new VersionConstraint($operator, $version);
        }

        $constraintSet = new VersionConstraintSet($constraints);
        $version = new Version($versionString);

        $this->assertEquals($expected, $constraintSet->isSatisfiedBy($version));
    }

    /**
     * Data provider for real-world constraint sets.
     */
    public static function realWorldConstraintSetsProvider(): array
    {
        return [
            // >=1.0.0 <2.0.0
            [['>=1.0.0', '<2.0.0'], '1.0.0', true],
            [['>=1.0.0', '<2.0.0'], '1.5.0', true],
            [['>=1.0.0', '<2.0.0'], '2.0.0', false],
            [['>=1.0.0', '<2.0.0'], '0.9.9', false],

            // >=1.0.0 <1.1.0 || >=2.0.0 <3.0.0
            // These would be handled by VersionRangeSet in practice, but we're
            // testing individual constraint sets here
            [['>=1.0.0', '<1.1.0'], '1.0.0', true],
            [['>=1.0.0', '<1.1.0'], '1.0.9', true],
            [['>=1.0.0', '<1.1.0'], '1.1.0', false],
            [['>=2.0.0', '<3.0.0'], '2.0.0', true],
            [['>=2.0.0', '<3.0.0'], '2.9.9', true],
            [['>=2.0.0', '<3.0.0'], '3.0.0', false],

            // ~1.2.3
            [['~1.2.3'], '1.2.3', true],
            [['~1.2.3'], '1.2.9', true],
            [['~1.2.3'], '1.3.0', false],

            // ^1.2.3
            [['^1.2.3'], '1.2.3', true],
            [['^1.2.3'], '1.9.9', true],
            [['^1.2.3'], '2.0.0', false],
        ];
    }
}

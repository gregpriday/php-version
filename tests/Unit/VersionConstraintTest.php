<?php

namespace GregPriday\Version\Tests\Unit;

use GregPriday\Version\Constraint\VersionConstraint;
use GregPriday\Version\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionConstraintTest extends TestCase
{
    /**
     * Test the constructor with valid operators.
     */
    public function test_constructor_with_valid_operators(): void
    {
        $validOperators = ['=', '==', '>', '>=', '<', '<=', '^', '~', '!', '!='];
        $version = new Version('1.0.0');

        foreach ($validOperators as $operator) {
            $constraint = new VersionConstraint($operator, $version);
            $this->assertInstanceOf(VersionConstraint::class, $constraint);
            $this->assertEquals($operator, $constraint->getOperator());
            $this->assertSame($version, $constraint->getReferenceVersion());
        }
    }

    /**
     * Test the constructor with an invalid operator.
     */
    public function test_constructor_with_invalid_operator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported operator');

        new VersionConstraint('invalid', new Version('1.0.0'));
    }

    /**
     * Test equality constraints (= and ==).
     */
    #[DataProvider('equalityConstraintProvider')]
    public function test_equality_constraint(string $operator, string $referenceVersion, string $testVersion, bool $expected): void
    {
        $constraint = new VersionConstraint($operator, new Version($referenceVersion));
        $version = new Version($testVersion);

        $this->assertEquals($expected, $constraint->isSatisfiedBy($version));
    }

    /**
     * Test greater than constraints (> and >=).
     */
    #[DataProvider('greaterThanConstraintProvider')]
    public function test_greater_than_constraint(string $operator, string $referenceVersion, string $testVersion, bool $expected): void
    {
        $constraint = new VersionConstraint($operator, new Version($referenceVersion));
        $version = new Version($testVersion);

        $this->assertEquals($expected, $constraint->isSatisfiedBy($version));
    }

    /**
     * Test less than constraints (< and <=).
     */
    #[DataProvider('lessThanConstraintProvider')]
    public function test_less_than_constraint(string $operator, string $referenceVersion, string $testVersion, bool $expected): void
    {
        $constraint = new VersionConstraint($operator, new Version($referenceVersion));
        $version = new Version($testVersion);

        $this->assertEquals($expected, $constraint->isSatisfiedBy($version));
    }

    /**
     * Test caret constraints (^).
     */
    #[DataProvider('caretConstraintProvider')]
    public function test_caret_constraint(string $referenceVersion, string $testVersion, bool $expected): void
    {
        $constraint = new VersionConstraint('^', new Version($referenceVersion));
        $version = new Version($testVersion);

        $this->assertEquals($expected, $constraint->isSatisfiedBy($version));
    }

    /**
     * Test tilde constraints (~).
     */
    #[DataProvider('tildeConstraintProvider')]
    public function test_tilde_constraint(string $referenceVersion, string $testVersion, bool $expected): void
    {
        $constraint = new VersionConstraint('~', new Version($referenceVersion));
        $version = new Version($testVersion);

        $this->assertEquals($expected, $constraint->isSatisfiedBy($version));
    }

    /**
     * Test not equal constraints (! and !=).
     */
    #[DataProvider('notEqualConstraintProvider')]
    public function test_not_equal_constraint(string $operator, string $referenceVersion, string $testVersion, bool $expected): void
    {
        $constraint = new VersionConstraint($operator, new Version($referenceVersion));
        $version = new Version($testVersion);

        $this->assertEquals($expected, $constraint->isSatisfiedBy($version));
    }

    /**
     * Data provider for equality constraints.
     */
    public static function equalityConstraintProvider(): array
    {
        return [
            // operator, reference, test, expected
            ['=', '1.0.0', '1.0.0', true],
            ['=', '1.0.0', '1.0.1', false],
            ['=', '1.0.0', '1.1.0', false],
            ['=', '1.0.0', '2.0.0', false],
            ['=', '1.0.0-alpha', '1.0.0-alpha', true],
            ['=', '1.0.0-alpha', '1.0.0-beta', false],
            ['=', '1.0.0-alpha', '1.0.0', false],
            ['==', '1.0.0', '1.0.0', true],
            ['==', '1.0.0', '1.0.1', false],
        ];
    }

    /**
     * Data provider for greater than constraints.
     */
    public static function greaterThanConstraintProvider(): array
    {
        return [
            // operator, reference, test, expected
            ['>', '1.0.0', '1.0.1', true],
            ['>', '1.0.0', '1.1.0', true],
            ['>', '1.0.0', '2.0.0', true],
            ['>', '1.0.0', '1.0.0', false],
            ['>', '1.0.0', '0.9.9', false],
            ['>', '1.0.0-alpha', '1.0.0-beta', true],
            ['>', '1.0.0-alpha', '1.0.0', true],
            ['>', '1.0.0-beta', '1.0.0-alpha', false],
            ['>=', '1.0.0', '1.0.0', true],
            ['>=', '1.0.0', '1.0.1', true],
            ['>=', '1.0.0', '0.9.9', false],
        ];
    }

    /**
     * Data provider for less than constraints.
     */
    public static function lessThanConstraintProvider(): array
    {
        return [
            // operator, reference, test, expected
            ['<', '1.0.0', '0.9.9', true],
            ['<', '1.0.0', '0.9.0', true],
            ['<', '1.0.0', '1.0.0', false],
            ['<', '1.0.0', '1.0.1', false],
            ['<', '1.0.0', '1.1.0', false],
            ['<', '1.0.0', '2.0.0', false],
            ['<', '1.0.0', '1.0.0-alpha', true],
            ['<', '1.0.0-alpha', '0.9.9', true],
            ['<', '1.0.0-beta', '1.0.0-alpha', true],
            ['<=', '1.0.0', '1.0.0', true],
            ['<=', '1.0.0', '0.9.9', true],
            ['<=', '1.0.0', '1.0.1', false],
        ];
    }

    /**
     * Data provider for caret constraints.
     */
    public static function caretConstraintProvider(): array
    {
        return [
            // reference, test, expected
            // ^1.2.3 means >=1.2.3 <2.0.0
            ['1.2.3', '1.2.3', true],
            ['1.2.3', '1.2.4', true],
            ['1.2.3', '1.3.0', true],
            ['1.2.3', '1.9.9', true],
            ['1.2.3', '2.0.0', false],
            ['1.2.3', '1.2.2', false],
            // ^0.2.3 means >=0.2.3 <0.3.0
            ['0.2.3', '0.2.3', true],
            ['0.2.3', '0.2.4', true],
            ['0.2.3', '0.2.9', true],
            ['0.2.3', '0.3.0', false],
            ['0.2.3', '0.2.2', false],
            // ^0.0.3 means >=0.0.3 <0.0.4
            ['0.0.3', '0.0.3', true],
            ['0.0.3', '0.0.4', false],
            ['0.0.3', '0.0.2', false],
            ['0.0.3', '0.1.0', false],
        ];
    }

    /**
     * Data provider for tilde constraints.
     */
    public static function tildeConstraintProvider(): array
    {
        return [
            // reference, test, expected
            // ~1.2.3 means >=1.2.3 <1.3.0
            ['1.2.3', '1.2.3', true],
            ['1.2.3', '1.2.4', true],
            ['1.2.3', '1.2.9', true],
            ['1.2.3', '1.3.0', false],
            ['1.2.3', '1.2.2', false],
            ['1.2.3', '2.0.0', false],
            // ~1.2 should mean >=1.2.0 <1.3.0
            ['1.2.0', '1.2.0', true],
            ['1.2.0', '1.2.9', true],
            ['1.2.0', '1.3.0', false],
        ];
    }

    /**
     * Data provider for not equal constraints.
     */
    public static function notEqualConstraintProvider(): array
    {
        return [
            // operator, reference, test, expected
            ['!', '1.0.0', '1.0.0', false],
            ['!', '1.0.0', '1.0.1', true],
            ['!', '1.0.0', '0.9.9', true],
            ['!=', '1.0.0', '1.0.0', false],
            ['!=', '1.0.0', '1.0.1', true],
            ['!=', '1.0.0-alpha', '1.0.0-alpha', false],
            ['!=', '1.0.0-alpha', '1.0.0-beta', true],
            ['!=', '1.0.0-alpha', '1.0.0', true],
        ];
    }
}

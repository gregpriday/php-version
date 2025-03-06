<?php

namespace GregPriday\Version\Tests\Unit;

use GregPriday\Version\Constraint\VersionConstraint;
use GregPriday\Version\Constraint\VersionConstraintParser;
use GregPriday\Version\Constraint\VersionConstraintSet;
use GregPriday\Version\Constraint\VersionRangeSet;
use GregPriday\Version\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionConstraintParserTest extends TestCase
{
    private VersionConstraintParser $parser;

    protected function setUp(): void
    {
        $this->parser = new VersionConstraintParser;
    }

    /**
     * Test parsing an empty constraint string.
     */
    public function test_parse_empty_constraint(): void
    {
        $rangeSet = $this->parser->parseConstraints('');
        $this->assertInstanceOf(VersionRangeSet::class, $rangeSet);
        $this->assertEmpty($rangeSet->getConstraintSets());

        // An empty range set should match any version
        $this->assertTrue($rangeSet->isSatisfiedBy(new Version('1.0.0')));
    }

    /**
     * Test parsing a single constraint.
     */
    #[DataProvider('singleConstraintProvider')]
    public function test_parse_single_constraint(string $constraintString, string $testVersion, bool $expected): void
    {
        $rangeSet = $this->parser->parseConstraints($constraintString);
        $this->assertInstanceOf(VersionRangeSet::class, $rangeSet);
        $this->assertCount(1, $rangeSet->getConstraintSets());

        $constraintSets = $rangeSet->getConstraintSets();
        $constraintSet = $constraintSets[0];
        $this->assertInstanceOf(VersionConstraintSet::class, $constraintSet);
        $this->assertCount(1, $constraintSet->getConstraints());

        $constraints = $constraintSet->getConstraints();
        $constraint = $constraints[0];
        $this->assertInstanceOf(VersionConstraint::class, $constraint);

        // Verify the constraint is satisfied as expected
        $version = new Version($testVersion);
        $this->assertEquals($expected, $rangeSet->isSatisfiedBy($version));
    }

    /**
     * Test parsing multiple AND constraints.
     */
    #[DataProvider('andConstraintsProvider')]
    public function test_parse_and_constraints(string $constraintString, string $testVersion, bool $expected): void
    {
        $rangeSet = $this->parser->parseConstraints($constraintString);
        $this->assertInstanceOf(VersionRangeSet::class, $rangeSet);
        $this->assertCount(1, $rangeSet->getConstraintSets());

        $constraintSets = $rangeSet->getConstraintSets();
        $constraintSet = $constraintSets[0];
        $this->assertInstanceOf(VersionConstraintSet::class, $constraintSet);
        $this->assertGreaterThan(1, count($constraintSet->getConstraints()));

        // Verify the constraint set is satisfied as expected
        $version = new Version($testVersion);
        $this->assertEquals($expected, $rangeSet->isSatisfiedBy($version));
    }

    /**
     * Test parsing OR constraints.
     */
    #[DataProvider('orConstraintsProvider')]
    public function test_parse_or_constraints(string $constraintString, string $testVersion, bool $expected): void
    {
        $rangeSet = $this->parser->parseConstraints($constraintString);
        $this->assertInstanceOf(VersionRangeSet::class, $rangeSet);
        $this->assertGreaterThan(1, count($rangeSet->getConstraintSets()));

        // Verify the range set is satisfied as expected
        $version = new Version($testVersion);
        $this->assertEquals($expected, $rangeSet->isSatisfiedBy($version));
    }

    /**
     * Test parsing complex constraint strings.
     */
    #[DataProvider('complexConstraintProvider')]
    public function test_parse_complex_constraints(string $constraintString, array $testVersions): void
    {
        $rangeSet = $this->parser->parseConstraints($constraintString);

        foreach ($testVersions as $versionString => $expected) {
            $version = new Version($versionString);
            $this->assertEquals(
                $expected,
                $rangeSet->isSatisfiedBy($version),
                "Version {$versionString} should ".($expected ? 'satisfy' : 'not satisfy')." constraint {$constraintString}"
            );
        }
    }

    /**
     * Test parsing invalid constraint strings.
     */
    #[DataProvider('invalidConstraintProvider')]
    public function test_parse_invalid_constraints(string $constraintString): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->parseConstraints($constraintString);
    }

    /**
     * Test handling pre-release versions in constraints.
     */
    #[DataProvider('preReleaseConstraintProvider')]
    public function test_pre_release_constraints(string $constraintString, string $testVersion, bool $expected): void
    {
        $rangeSet = $this->parser->parseConstraints($constraintString);
        $version = new Version($testVersion);
        $this->assertEquals(
            $expected,
            $rangeSet->isSatisfiedBy($version),
            "Version {$testVersion} should ".($expected ? 'satisfy' : 'not satisfy')." constraint {$constraintString}"
        );
    }

    /**
     * Test the entire workflow from parsing constraints to checking version satisfaction.
     */
    public function test_constraint_parsing_integration(): void
    {
        $complexConstraint = '^1.0.0,!=1.1.0 || ~2.0.0';
        $rangeSet = $this->parser->parseConstraints($complexConstraint);

        // Test various versions against this constraint
        $testVersions = [
            '1.0.0' => true,       // Satisfies ^1.0.0
            '1.1.0' => false,      // Excluded by !=1.1.0
            '1.9.9' => true,       // Satisfies ^1.0.0
            '2.0.0' => true,       // Satisfies ~2.0.0
            '2.0.9' => true,       // Satisfies ~2.0.0
            '2.1.0' => false,      // Outside ~2.0.0
            '3.0.0' => false,      // Outside both ranges
        ];

        foreach ($testVersions as $versionString => $expected) {
            $version = new Version($versionString);
            $result = $rangeSet->isSatisfiedBy($version);

            $this->assertEquals(
                $expected,
                $result,
                "Version {$versionString} should ".($expected ? 'satisfy' : 'not satisfy')." constraint {$complexConstraint}"
            );
        }

        // Test with complex constraints including pre-releases
        $preReleaseConstraint = '^1.0.0-alpha,!=1.0.0-beta || >=2.0.0-rc,<2.1.0';
        $rangeSet = $this->parser->parseConstraints($preReleaseConstraint);

        $testVersions = [
            '1.0.0-alpha' => true,     // Satisfies ^1.0.0-alpha
            '1.0.0-beta' => false,     // Excluded by !=1.0.0-beta
            '1.0.0' => true,           // Satisfies ^1.0.0-alpha
            '1.9.9' => true,           // Satisfies ^1.0.0-alpha
            '2.0.0-beta' => false,     // Less than 2.0.0-rc
            '2.0.0-rc' => true,        // Satisfies >=2.0.0-rc
            '2.0.0' => true,           // Satisfies >=2.0.0-rc
            '2.0.9' => true,           // Satisfies <2.1.0
            '2.1.0' => false,          // Equal to 2.1.0 (excluded by <2.1.0)
        ];

        foreach ($testVersions as $versionString => $expected) {
            $version = new Version($versionString);
            $result = $rangeSet->isSatisfiedBy($version);

            $this->assertEquals(
                $expected,
                $result,
                "Version {$versionString} should ".($expected ? 'satisfy' : 'not satisfy')." constraint {$preReleaseConstraint}"
            );
        }
    }

    /**
     * Data provider for single constraints.
     */
    public static function singleConstraintProvider(): array
    {
        return [
            ['>=1.0.0', '1.0.0', true],
            ['>=1.0.0', '0.9.9', false],
            ['<2.0.0', '1.9.9', true],
            ['<2.0.0', '2.0.0', false],
            ['>1.0.0', '1.0.0', false],
            ['>1.0.0', '1.0.1', true],
            ['<=1.0.0', '1.0.0', true],
            ['<=1.0.0', '1.0.1', false],
            ['=1.0.0', '1.0.0', true],
            ['=1.0.0', '1.0.1', false],
            ['==1.0.0', '1.0.0', true],
            ['==1.0.0', '1.0.1', false],
            ['!=1.0.0', '1.0.0', false],
            ['!=1.0.0', '1.0.1', true],
            ['!1.0.0', '1.0.0', false],
            ['!1.0.0', '1.0.1', true],
            ['^1.0.0', '1.9.9', true],
            ['^1.0.0', '2.0.0', false],
            ['~1.0.0', '1.0.9', true],
            ['~1.0.0', '1.1.0', false],
            // Test with a simple version number (no operator) - should be treated as =
            ['1.0.0', '1.0.0', true],
            ['1.0.0', '1.0.1', false],
        ];
    }

    /**
     * Data provider for AND constraints.
     */
    public static function andConstraintsProvider(): array
    {
        return [
            ['>=1.0.0 <2.0.0', '1.0.0', true],
            ['>=1.0.0 <2.0.0', '1.5.0', true],
            ['>=1.0.0 <2.0.0', '0.9.9', false],
            ['>=1.0.0 <2.0.0', '2.0.0', false],
            ['>1.0.0 <2.0.0', '1.0.0', false],
            ['>1.0.0 <2.0.0', '1.0.1', true],
            ['>1.0.0 <2.0.0', '2.0.0', false],
            // Test with comma separation
            ['>=1.0.0, <2.0.0', '1.0.0', true],
            ['>=1.0.0, <2.0.0', '1.5.0', true],
            ['>=1.0.0, <2.0.0', '0.9.9', false],
            ['>=1.0.0, <2.0.0', '2.0.0', false],
            // Test with mixed spacing
            ['>=1.0.0   <2.0.0', '1.5.0', true],
            ['>=1.0.0,<2.0.0', '1.5.0', true],
            // Test with more than two constraints
            ['>=1.0.0 <2.0.0 !=1.5.0', '1.0.0', true],
            ['>=1.0.0 <2.0.0 !=1.5.0', '1.5.0', false],
            ['>=1.0.0 <2.0.0 !=1.5.0', '1.9.9', true],
            // Test with caret and tilde operators in AND combinations
            ['^1.0.0 <1.5.0', '1.0.0', true],
            ['^1.0.0 <1.5.0', '1.4.9', true],
            ['^1.0.0 <1.5.0', '1.5.0', false],
            ['^1.0.0 <1.5.0', '2.0.0', false],
            ['~1.0.0 >=1.0.5', '1.0.0', false],
            ['~1.0.0 >=1.0.5', '1.0.5', true],
            ['~1.0.0 >=1.0.5', '1.0.9', true],
            ['~1.0.0 >=1.0.5', '1.1.0', false],
        ];
    }

    /**
     * Data provider for OR constraints.
     */
    public static function orConstraintsProvider(): array
    {
        return [
            ['>=1.0.0 <2.0.0 || >=3.0.0 <4.0.0', '1.0.0', true],
            ['>=1.0.0 <2.0.0 || >=3.0.0 <4.0.0', '1.5.0', true],
            ['>=1.0.0 <2.0.0 || >=3.0.0 <4.0.0', '2.0.0', false],
            ['>=1.0.0 <2.0.0 || >=3.0.0 <4.0.0', '2.5.0', false],
            ['>=1.0.0 <2.0.0 || >=3.0.0 <4.0.0', '3.0.0', true],
            ['>=1.0.0 <2.0.0 || >=3.0.0 <4.0.0', '3.5.0', true],
            ['>=1.0.0 <2.0.0 || >=3.0.0 <4.0.0', '4.0.0', false],
            // Test with leading/trailing spaces around ||
            ['>=1.0.0 <2.0.0||>=3.0.0 <4.0.0', '1.5.0', true],
            ['>=1.0.0 <2.0.0|| >=3.0.0 <4.0.0', '3.5.0', true],
            ['>=1.0.0 <2.0.0 ||>=3.0.0 <4.0.0', '2.5.0', false],
            // Test with caret and tilde operators in OR combinations
            ['^1.0.0 || ^2.0.0', '1.0.0', true],
            ['^1.0.0 || ^2.0.0', '1.9.9', true],
            ['^1.0.0 || ^2.0.0', '2.0.0', true],
            ['^1.0.0 || ^2.0.0', '2.9.9', true],
            ['^1.0.0 || ^2.0.0', '3.0.0', false],
            ['~1.0.0 || ~2.0.0', '1.0.0', true],
            ['~1.0.0 || ~2.0.0', '1.0.9', true],
            ['~1.0.0 || ~2.0.0', '1.1.0', false],
            ['~1.0.0 || ~2.0.0', '2.0.0', true],
            ['~1.0.0 || ~2.0.0', '2.0.9', true],
            ['~1.0.0 || ~2.0.0', '2.1.0', false],
        ];
    }

    /**
     * Data provider for complex constraints.
     */
    public static function complexConstraintProvider(): array
    {
        return [
            [
                '^1.0.0 <1.5.0 || ^2.0.0',
                [
                    '1.0.0' => true,
                    '1.4.9' => true,
                    '1.5.0' => false,
                    '1.9.9' => false,
                    '2.0.0' => true,
                    '2.9.9' => true,
                    '3.0.0' => false,
                ],
            ],
            [
                '>=1.0.0 <1.5.0, !=1.2.3 || >=2.0.0 <3.0.0, !=2.2.3',
                [
                    '1.0.0' => true,
                    '1.2.3' => false,
                    '1.4.9' => true,
                    '1.5.0' => false,
                    '2.0.0' => true,
                    '2.2.3' => false,
                    '2.9.9' => true,
                    '3.0.0' => false,
                ],
            ],
        ];
    }

    /**
     * Data provider for invalid constraints.
     */
    public static function invalidConstraintProvider(): array
    {
        return [
            // Invalid operators
            'double greater-than' => ['>>1.0.0'],
            'double less-than' => ['<<1.0.0'],
            'mixed operators' => ['><1.0.0'],
            'inverted mixed operators' => ['<>1.0.0'],
            'double exclamation' => ['!!1.0.0'],
            'triple equals' => ['===1.0.0'],
            'double not equals' => ['!==1.0.0'],
            'starts with or' => ['|| >=1.0.0'],
            'ends with or' => ['>=1.0.0 ||'],
            'double or' => ['>=1.0.0 || || <2.0.0'],
            'empty parts in or' => ['>=1.0.0 || '],
            // Additional edge cases
            'invalid operator' => ['&1.0.0'],
            'unexpected punctuation' => ['>=1.0.0 & <2.0.0'],
            'multiple spaces in operator' => ['>=   1.0.0'],
            'missing version' => ['>='],
            'empty constraint after operator' => ['>= '],
            'lone operator' => ['>'],
            'partial version number' => ['1.2'],
        ];
    }

    /**
     * Data provider for pre-release version constraints.
     */
    public static function preReleaseConstraintProvider(): array
    {
        return [
            // Pre-release versions in constraints
            ['>1.0.0-alpha', '1.0.0-beta', true],
            ['>1.0.0-alpha', '1.0.0-alpha', false],
            ['>1.0.0-alpha', '1.0.0', true],
            ['>=1.0.0-alpha', '1.0.0-alpha', true],
            ['>=1.0.0-alpha', '1.0.0-alpha.1', true],
            ['>=1.0.0-alpha', '1.0.0', true],
            ['<1.0.0', '1.0.0-alpha', true],
            ['<1.0.0', '1.0.0', false],
            ['<=1.0.0', '1.0.0-alpha', true],
            ['<=1.0.0', '1.0.0', true],
            ['<=1.0.0', '1.0.0-rc', true],
            ['<=1.0.0-alpha', '1.0.0-alpha', true],
            ['<=1.0.0-alpha', '1.0.0-alpha.9', false],
            ['=1.0.0-alpha', '1.0.0-alpha', true],
            ['=1.0.0-alpha', '1.0.0-alpha.1', false],
            ['=1.0.0-alpha', '1.0.0', false],
            // Caret with pre-releases
            ['^1.0.0-alpha', '1.0.0-alpha', true],
            ['^1.0.0-alpha', '1.0.0-beta', true],
            ['^1.0.0-alpha', '1.0.0', true],
            ['^1.0.0-alpha', '1.9.9', true],
            ['^1.0.0-alpha', '2.0.0', false],
            // Tilde with pre-releases
            ['~1.0.0-alpha', '1.0.0-alpha', true],
            ['~1.0.0-alpha', '1.0.0-beta', true],
            ['~1.0.0-alpha', '1.0.0', true],
            ['~1.0.0-alpha', '1.0.9', true],
            ['~1.0.0-alpha', '1.1.0', false],
        ];
    }
}

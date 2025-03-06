<?php

namespace GregPriday\Version\Constraint;

use GregPriday\Version\Version;

/**
 * Class VersionConstraint
 *
 * Represents a single version constraint like >=1.2.3 or ^1.0.0.
 */
class VersionConstraint
{
    /**
     * List of supported operators.
     */
    public const OPERATORS = [
        '=', '==',
        '>', '>=',
        '<', '<=',
        '^', '~',
        '!', '!=',
    ];

    /**
     * The constraint operator.
     */
    protected string $operator;

    /**
     * The reference version.
     */
    protected Version $referenceVersion;

    /**
     * VersionConstraint constructor.
     *
     * @param  string  $operator  The operator (e.g., ">=", "^", "~")
     * @param  Version  $referenceVersion  The reference version
     *
     * @throws \InvalidArgumentException If the operator is not supported
     */
    public function __construct(string $operator, Version $referenceVersion)
    {
        if (! in_array($operator, self::OPERATORS)) {
            throw new \InvalidArgumentException("Unsupported operator: {$operator}");
        }

        $this->operator = $operator;
        $this->referenceVersion = $referenceVersion;
    }

    /**
     * Get the constraint operator.
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Get the reference version.
     */
    public function getReferenceVersion(): Version
    {
        return $this->referenceVersion;
    }

    /**
     * Check if a version satisfies this constraint.
     *
     * @param  Version  $version  The version to check
     */
    public function isSatisfiedBy(Version $version): bool
    {
        // Handle special operators first
        if ($this->operator === '^') {
            return $this->isSatisfiedByCaret($version);
        }

        if ($this->operator === '~') {
            return $this->isSatisfiedByTilde($version);
        }

        if ($this->operator === '!' || $this->operator === '!=') {
            return ! $this->compareVersions($version, '=', $this->referenceVersion);
        }

        // Handle standard comparison operators
        return $this->compareVersions($version, $this->operator, $this->referenceVersion);
    }

    /**
     * Check if a version satisfies the caret constraint (^).
     *
     * The caret constraint allows changes that do not modify the left-most non-zero digit:
     * ^1.2.3 means >=1.2.3 <2.0.0
     * ^0.2.3 means >=0.2.3 <0.3.0
     * ^0.0.3 means >=0.0.3 <0.0.4
     *
     * @param  Version  $version  The version to check
     */
    protected function isSatisfiedByCaret(Version $version): bool
    {
        $major = $this->referenceVersion->getMajor();
        $minor = $this->referenceVersion->getMinor();
        $patch = $this->referenceVersion->getPatch();

        // ^0.0.x constraint
        if ($major === 0 && $minor === 0) {
            return $this->compareVersions($version, '>=', $this->referenceVersion) &&
                   ($version->getMajor() === 0 && $version->getMinor() === 0 && $version->getPatch() < $patch + 1);
        }

        // ^0.x.y constraint
        if ($major === 0) {
            return $this->compareVersions($version, '>=', $this->referenceVersion) &&
                   ($version->getMajor() === 0 && $version->getMinor() < $minor + 1);
        }

        // ^x.y.z constraint where x > 0
        return $this->compareVersions($version, '>=', $this->referenceVersion) &&
               $version->getMajor() < $major + 1;
    }

    /**
     * Check if a version satisfies the tilde constraint (~).
     *
     * The tilde constraint acts like the caret constraint, but is more strict on the minor version:
     * ~1.2.3 means >=1.2.3 <1.3.0
     * ~1.2 means >=1.2.0 <1.3.0
     *
     * @param  Version  $version  The version to check
     */
    protected function isSatisfiedByTilde(Version $version): bool
    {
        $major = $this->referenceVersion->getMajor();
        $minor = $this->referenceVersion->getMinor();

        return $this->compareVersions($version, '>=', $this->referenceVersion) &&
               ($version->getMajor() === $major && $version->getMinor() < $minor + 1);
    }

    /**
     * Compare two version objects using the specified operator.
     *
     * @param  Version  $version1  First version
     * @param  string  $operator  Comparison operator
     * @param  Version  $version2  Second version
     */
    protected function compareVersions(Version $version1, string $operator, Version $version2): bool
    {
        // Extract version parts for comparison
        $v1Major = $version1->getMajor() ?? 0;
        $v1Minor = $version1->getMinor() ?? 0;
        $v1Patch = $version1->getPatch() ?? 0;
        $v1Pre = $version1->getPreRelease();

        $v2Major = $version2->getMajor() ?? 0;
        $v2Minor = $version2->getMinor() ?? 0;
        $v2Patch = $version2->getPatch() ?? 0;
        $v2Pre = $version2->getPreRelease();

        // First, compare version numbers
        if ($v1Major !== $v2Major) {
            $comparison = $v1Major <=> $v2Major;
        } elseif ($v1Minor !== $v2Minor) {
            $comparison = $v1Minor <=> $v2Minor;
        } elseif ($v1Patch !== $v2Patch) {
            $comparison = $v1Patch <=> $v2Patch;
        } else {
            // If numbers are equal, compare pre-release tags
            // No pre-release is greater than any pre-release
            if ($v1Pre === null && $v2Pre !== null) {
                $comparison = 1;
            } elseif ($v1Pre !== null && $v2Pre === null) {
                $comparison = -1;
            } elseif ($v1Pre === $v2Pre) {
                $comparison = 0;
            } else {
                // Compare pre-release tags alphanumerically
                $comparison = strcmp($v1Pre, $v2Pre);
            }
        }

        // Apply the operator to the comparison result
        switch ($operator) {
            case '=':
            case '==':
                return $comparison === 0;
            case '>':
                return $comparison > 0;
            case '>=':
                return $comparison >= 0;
            case '<':
                return $comparison < 0;
            case '<=':
                return $comparison <= 0;
            default:
                throw new \InvalidArgumentException("Unsupported comparison operator: {$operator}");
        }
    }
}

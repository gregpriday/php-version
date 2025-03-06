<?php

namespace GregPriday\Version\Constraint;

use GregPriday\Version\Version;

/**
 * Class VersionConstraintParser
 *
 * Parses version constraint strings to create structured constraint objects.
 */
class VersionConstraintParser
{
    /**
     * Parse a version constraint string into a VersionRangeSet.
     *
     * @param  string  $constraintString  The constraint string to parse, e.g., ">=1.2.3 <2.0.0 || ^3.0"
     * @return VersionRangeSet The resulting version range set
     *
     * @throws \InvalidArgumentException If the constraint is invalid
     */
    public function parseConstraints(string $constraintString): VersionRangeSet
    {
        // Trim any whitespace
        $constraintString = trim($constraintString);

        // If empty, return an empty range set (matches anything)
        if (empty($constraintString)) {
            return new VersionRangeSet;
        }

        // Validate the constraint string doesn't end with or start with ||
        if (preg_match('/^\s*\|\|\s*|\s*\|\|\s*$/', $constraintString)) {
            throw new \InvalidArgumentException("Invalid constraint format: {$constraintString}");
        }

        // Split by || to get OR constraints
        $orParts = array_map('trim', preg_split('/\s*\|\|\s*/', $constraintString));
        $rangeSet = new VersionRangeSet;

        foreach ($orParts as $orPart) {
            // Check for invalid orPart
            if (empty($orPart)) {
                throw new \InvalidArgumentException("Invalid constraint format: {$constraintString}");
            }

            // Parse each OR part as an AND group
            $constraintSet = $this->parseAndConstraints($orPart);
            $rangeSet->addConstraintSet($constraintSet);
        }

        return $rangeSet;
    }

    /**
     * Parse a string of AND constraints (e.g., ">=1.2.3 <2.0.0").
     *
     * @param  string  $andConstraintString  The AND constraint string to parse
     * @return VersionConstraintSet The resulting version constraint set
     */
    protected function parseAndConstraints(string $andConstraintString): VersionConstraintSet
    {
        // Split the input by whitespace and/or commas to get individual constraints
        $parts = preg_split('/[\s,]+/', trim($andConstraintString));
        $constraintSet = new VersionConstraintSet;

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            // Parse single constraints
            $constraint = $this->parseSingleConstraint($part);
            $constraintSet->addConstraint($constraint);
        }

        return $constraintSet;
    }

    /**
     * Parse a single constraint (e.g., ">=1.2.3" or "^1.0").
     *
     * @param  string  $constraintString  The single constraint string to parse
     * @return VersionConstraint The resulting version constraint
     *
     * @throws \InvalidArgumentException If the constraint is invalid
     */
    protected function parseSingleConstraint(string $constraintString): VersionConstraint
    {
        // Empty constraint is invalid
        if (empty($constraintString)) {
            throw new \InvalidArgumentException('Empty constraint string');
        }

        // Explicitly check for invalid operators
        if (preg_match('/^(>>|<<|><|<>|!!|==\=|\={3,}|!={2,})/', $constraintString)) {
            throw new \InvalidArgumentException("Invalid constraint format: {$constraintString}");
        }

        // Check for equality operator first (no prefix means equal)
        if (preg_match('/^[0-9]/', $constraintString)) {
            return new VersionConstraint('=', Version::fromString($constraintString));
        }

        // Match operators like >=, >, <, <=, =, ==, ^, ~, !=, !
        if (preg_match('/^(!=|==|>=|<=|>|<|=|\^|~|!)(.+)$/', $constraintString, $matches)) {
            $operator = $matches[1];
            $versionString = trim($matches[2]);

            // Handle ! operator (convert to !=)
            if ($operator === '!') {
                $operator = '!=';
            }

            // Validate the version part is not empty
            if (empty($versionString)) {
                throw new \InvalidArgumentException("Invalid constraint format: {$constraintString}");
            }

            // Create a Version object for the reference version
            $referenceVersion = Version::fromString($versionString);

            return new VersionConstraint($operator, $referenceVersion);
        }

        throw new \InvalidArgumentException("Invalid constraint format: {$constraintString}");
    }
}

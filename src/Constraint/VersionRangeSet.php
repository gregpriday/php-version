<?php

namespace GregPriday\Version\Constraint;

use GregPriday\Version\Version;

/**
 * Class VersionRangeSet
 *
 * Represents a set of constraint sets combined with logical OR.
 * For example: (>=1.0.0 <2.0.0) || (>=3.0.0) would be a range set.
 */
class VersionRangeSet
{
    /**
     * The set of constraint sets, combined with logical OR.
     *
     * @var VersionConstraintSet[]
     */
    protected array $constraintSets = [];

    /**
     * VersionRangeSet constructor.
     *
     * @param  array  $constraintSets  An array of VersionConstraintSet objects
     */
    public function __construct(array $constraintSets = [])
    {
        foreach ($constraintSets as $constraintSet) {
            if (! $constraintSet instanceof VersionConstraintSet) {
                throw new \InvalidArgumentException(
                    'All constraint sets must be instances of '.VersionConstraintSet::class
                );
            }
            $this->constraintSets[] = $constraintSet;
        }
    }

    /**
     * Add a constraint set to the range set.
     *
     * @param  VersionConstraintSet  $constraintSet  The constraint set to add
     * @return $this
     */
    public function addConstraintSet(VersionConstraintSet $constraintSet): self
    {
        $this->constraintSets[] = $constraintSet;

        return $this;
    }

    /**
     * Get all constraint sets in the range set.
     *
     * @return VersionConstraintSet[]
     */
    public function getConstraintSets(): array
    {
        return $this->constraintSets;
    }

    /**
     * Check if a version satisfies any of the constraint sets.
     *
     * @param  Version  $version  The version to check
     * @return bool True if the version satisfies any constraint set
     */
    public function isSatisfiedBy(Version $version): bool
    {
        // If there are no constraint sets, any version is accepted
        if (empty($this->constraintSets)) {
            return true;
        }

        // Check if the version satisfies any constraint set (logical OR)
        foreach ($this->constraintSets as $constraintSet) {
            if ($constraintSet->isSatisfiedBy($version)) {
                return true;
            }
        }

        return false;
    }
}

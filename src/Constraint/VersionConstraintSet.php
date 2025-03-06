<?php

namespace GregPriday\Version\Constraint;

use GregPriday\Version\Version;

/**
 * Class VersionConstraintSet
 *
 * Represents a set of version constraints combined with logical AND.
 * For example: >=1.0.0 <2.0.0 would be a constraint set.
 */
class VersionConstraintSet
{
    /**
     * The set of constraints, combined with logical AND.
     *
     * @var VersionConstraint[]
     */
    protected array $constraints = [];

    /**
     * VersionConstraintSet constructor.
     *
     * @param  array  $constraints  An array of VersionConstraint objects
     */
    public function __construct(array $constraints = [])
    {
        foreach ($constraints as $constraint) {
            if (! $constraint instanceof VersionConstraint) {
                throw new \InvalidArgumentException(
                    'All constraints must be instances of '.VersionConstraint::class
                );
            }
            $this->constraints[] = $constraint;
        }
    }

    /**
     * Add a constraint to the set.
     *
     * @param  VersionConstraint  $constraint  The constraint to add
     * @return $this
     */
    public function addConstraint(VersionConstraint $constraint): self
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    /**
     * Get all constraints in the set.
     *
     * @return VersionConstraint[]
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Check if a version satisfies all constraints in the set.
     *
     * @param  Version  $version  The version to check
     * @return bool True if the version satisfies all constraints
     */
    public function isSatisfiedBy(Version $version): bool
    {
        // If there are no constraints, any version is accepted
        if (empty($this->constraints)) {
            return true;
        }

        // Check if the version satisfies all constraints (logical AND)
        foreach ($this->constraints as $constraint) {
            if (! $constraint->isSatisfiedBy($version)) {
                return false;
            }
        }

        return true;
    }
}

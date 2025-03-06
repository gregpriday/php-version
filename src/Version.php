<?php

namespace GregPriday\Version;

/**
 * Class Version
 *
 * A simple utility class to analyze a version string and determine if it's stable.
 * A version is considered stable if:
 * - Its major version is at least 1, and
 * - It does not include any pre-release identifiers (e.g. "alpha", "beta", "rc").
 *
 * It also provides methods to get the major, minor, patch numbers and the pre-release string (if any).
 */
class Version
{
    /**
     * The version string.
     *
     * @var string
     */
    protected string $version;

    /**
     * Parsed version parts.
     *
     * @var array|null
     */
    protected ?array $parts = null;

    /**
     * Version constructor.
     *
     * @param string $version A version string like "1.2.3", "0.9.0-beta", or "1.0.0-rc1".
     */
    public function __construct(string $version)
    {
        $this->version = $version;
        $this->parseVersion();
    }

    /**
     * Parse the version string into its components.
     *
     * Expected format: major.minor.patch[-preRelease]
     */
    protected function parseVersion(): void
    {
        if (preg_match(
            '/^(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)(?:-(?P<pre>[0-9A-Za-z.-]+))?$/',
            $this->version,
            $matches
        )) {
            $this->parts = [
                'major' => (int) $matches['major'],
                'minor' => (int) $matches['minor'],
                'patch' => (int) $matches['patch'],
                'pre'   => $matches['pre'] ?? null,
            ];
        }
    }

    /**
     * Determine if the version is stable.
     *
     * A version is stable if:
     * - The major version is at least 1, and
     * - There is no pre-release part (e.g., "beta", "rc", etc.)
     *
     * @return bool
     */
    public function isStable(): bool
    {
        if (!$this->parts) {
            return false;
        }
        if ($this->parts['major'] < 1) {
            return false;
        }
        if ($this->parts['pre'] !== null) {
            return false;
        }
        return true;
    }

    /**
     * Determine if the version is a pre-release.
     *
     * @return bool
     */
    public function isPreRelease(): bool
    {
        return $this->parts['pre'] !== null;
    }

    /**
     * Get the major version number.
     *
     * @return int|null
     */
    public function getMajor(): ?int
    {
        return $this->parts['major'] ?? null;
    }

    /**
     * Get the minor version number.
     *
     * @return int|null
     */
    public function getMinor(): ?int
    {
        return $this->parts['minor'] ?? null;
    }

    /**
     * Get the patch version number.
     *
     * @return int|null
     */
    public function getPatch(): ?int
    {
        return $this->parts['patch'] ?? null;
    }

    /**
     * Get the pre-release identifier, if any.
     *
     * @return string|null
     */
    public function getPreRelease(): ?string
    {
        return $this->parts['pre'] ?? null;
    }

    /**
     * Get extra information about the version.
     *
     * @return array Associative array of version details.
     */
    public function getExtraInfo(): array
    {
        return [
            'version'     => $this->version,
            'major'       => $this->getMajor(),
            'minor'       => $this->getMinor(),
            'patch'       => $this->getPatch(),
            'pre_release' => $this->getPreRelease(),
            'is_stable'   => $this->isStable(),
        ];
    }
}
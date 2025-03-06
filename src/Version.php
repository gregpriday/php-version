<?php

namespace GregPriday\Version;

use GregPriday\Version\Parser\VersionParser;
use GregPriday\Version\Parser\VersionParserInterface;

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
     */
    protected string $version;

    /**
     * Parsed version parts.
     */
    protected ?array $parts = null;

    /**
     * The version parser instance.
     */
    private static ?VersionParserInterface $parser = null;

    /**
     * Version constructor.
     *
     * @param  string  $version  A version string like "1.2.3", "0.9.0-beta", or "1.0.0-rc1".
     * @param  array|null  $parts  Optional pre-parsed parts
     */
    public function __construct(string $version, ?array $parts = null)
    {
        $this->version = $version;
        $this->parts = $parts;

        if ($this->parts === null) {
            $this->parts = self::getParser()->parse($version);
        }
    }

    /**
     * Set the parser instance to use.
     */
    public static function setParser(VersionParserInterface $parser): void
    {
        self::$parser = $parser;
    }

    /**
     * Get the parser instance.
     */
    public static function getParser(): VersionParserInterface
    {
        if (self::$parser === null) {
            self::$parser = new VersionParser;
        }

        return self::$parser;
    }

    /**
     * Create a Version instance from a version string.
     *
     * @param  string  $versionString  A version string
     * @return static
     */
    public static function fromString(string $versionString): self
    {
        return self::getParser()->createVersion($versionString);
    }

    /**
     * Determine if the version is stable.
     *
     * A version is stable if:
     * - The major version is at least 1, and
     * - There is no pre-release part (e.g., "beta", "rc", etc.)
     */
    public function isStable(): bool
    {
        if (! $this->parts) {
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
     */
    public function isPreRelease(): bool
    {
        if (! $this->parts) {
            return false;
        }

        return $this->parts['pre'] !== null;
    }

    /**
     * Get the major version number.
     */
    public function getMajor(): ?int
    {
        return $this->parts['major'] ?? null;
    }

    /**
     * Get the minor version number.
     */
    public function getMinor(): ?int
    {
        return $this->parts['minor'] ?? null;
    }

    /**
     * Get the patch version number.
     */
    public function getPatch(): ?int
    {
        return $this->parts['patch'] ?? null;
    }

    /**
     * Get the pre-release identifier, if any.
     */
    public function getPreRelease(): ?string
    {
        return $this->parts['pre'] ?? null;
    }

    /**
     * Get the build metadata, if any.
     */
    public function getBuildMetadata(): ?string
    {
        return $this->parts['build'] ?? null;
    }

    /**
     * Get extra information about the version.
     *
     * @return array Associative array of version details.
     */
    public function getExtraInfo(): array
    {
        return [
            'version' => $this->version,
            'major' => $this->getMajor(),
            'minor' => $this->getMinor(),
            'patch' => $this->getPatch(),
            'pre_release' => $this->getPreRelease(),
            'build_metadata' => $this->getBuildMetadata(),
            'is_stable' => $this->isStable(),
        ];
    }
}

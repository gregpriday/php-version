<?php

namespace GregPriday\Version\Parser;

use GregPriday\Version\Version;

/**
 * Interface VersionParserInterface
 *
 * Defines the contract for parsing version strings.
 */
interface VersionParserInterface
{
    /**
     * Parse a version string into its components.
     *
     * @param  string  $versionString  The version string to parse
     * @param  bool  $strict  Whether to strictly adhere to semver format (default: true)
     * @return array|null Parsed version parts or null if the version string is invalid
     */
    public function parse(string $versionString, bool $strict = true): ?array;

    /**
     * Create a Version object from a version string.
     *
     * @param  string  $versionString  The version string to parse
     * @param  bool  $strict  Whether to strictly adhere to semver format (default: true)
     * @return Version A new Version object
     */
    public function createVersion(string $versionString, bool $strict = true): Version;
}

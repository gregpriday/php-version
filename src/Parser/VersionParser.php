<?php

namespace GregPriday\Version\Parser;

use GregPriday\Version\Version;

/**
 * Class VersionParser
 *
 * Handles parsing of version strings according to semantic versioning format.
 */
class VersionParser implements VersionParserInterface
{
    /**
     * Parse a version string into its components.
     *
     * Expected format: major.minor.patch[-preRelease]
     *
     * @param  string  $versionString  The version string to parse
     * @return array|null Parsed version parts or null if the version string is invalid
     */
    public function parse(string $versionString): ?array
    {
        if (preg_match(
            '/^(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)(?:-(?P<pre>[0-9A-Za-z.-]+))?(?:\+(?P<build>[0-9A-Za-z.-]+))?$/',
            $versionString,
            $matches
        )) {
            return [
                'major' => (int) $matches['major'],
                'minor' => (int) $matches['minor'],
                'patch' => (int) $matches['patch'],
                'pre' => isset($matches['pre']) && $matches['pre'] !== '' ? $matches['pre'] : null,
                'build' => isset($matches['build']) && $matches['build'] !== '' ? $matches['build'] : null,
            ];
        }

        return null;
    }

    /**
     * Create a Version object from a version string.
     *
     * @param  string  $versionString  The version string to parse
     * @return Version A new Version object
     */
    public function createVersion(string $versionString): Version
    {
        return new Version($versionString, $this->parse($versionString));
    }
}

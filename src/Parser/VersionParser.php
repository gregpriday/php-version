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
     * Expected format: major.minor.patch[-preRelease][+build]
     *
     * @param  string  $versionString  The version string to parse
     * @param  bool  $strict  Whether to strictly adhere to semver format (default: true)
     * @return array|null Parsed version parts or null if the version string is invalid
     */
    public function parse(string $versionString, bool $strict = true): ?array
    {
        // Strict mode - use the standard semver regex
        if ($strict) {
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

        // Loose mode - handle various non-standard formats

        // Step 1: Remove 'v' prefix if present
        $versionString = preg_replace('/^v/', '', $versionString);

        // Step 2: Parse different version formats

        // Full format: major.minor.patch[-preRelease][+build]
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

        // Format: major.minor[-preRelease][+build]
        if (preg_match(
            '/^(?P<major>\d+)\.(?P<minor>\d+)(?:-(?P<pre>[0-9A-Za-z.-]+))?(?:\+(?P<build>[0-9A-Za-z.-]+))?$/',
            $versionString,
            $matches
        )) {
            return [
                'major' => (int) $matches['major'],
                'minor' => (int) $matches['minor'],
                'patch' => 0,
                'pre' => isset($matches['pre']) && $matches['pre'] !== '' ? $matches['pre'] : null,
                'build' => isset($matches['build']) && $matches['build'] !== '' ? $matches['build'] : null,
            ];
        }

        // Format: major[-preRelease][+build]
        if (preg_match(
            '/^(?P<major>\d+)(?:-(?P<pre>[0-9A-Za-z.-]+))?(?:\+(?P<build>[0-9A-Za-z.-]+))?$/',
            $versionString,
            $matches
        )) {
            return [
                'major' => (int) $matches['major'],
                'minor' => 0,
                'patch' => 0,
                'pre' => isset($matches['pre']) && $matches['pre'] !== '' ? $matches['pre'] : null,
                'build' => isset($matches['build']) && $matches['build'] !== '' ? $matches['build'] : null,
            ];
        }

        // Could not parse even in loose mode
        return null;
    }

    /**
     * Create a Version object from a version string.
     *
     * @param  string  $versionString  The version string to parse
     * @param  bool  $strict  Whether to strictly adhere to semver format (default: true)
     * @return Version A new Version object
     */
    public function createVersion(string $versionString, bool $strict = true): Version
    {
        $parts = $this->parse($versionString, $strict);

        if ($parts === null) {
            throw new \InvalidArgumentException("Invalid version string: {$versionString}");
        }

        return new Version($versionString, $parts);
    }
}

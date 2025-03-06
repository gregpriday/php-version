<?php

namespace GregPriday\Version\Traits;

/**
 * Trait VersionBumpingTrait
 *
 * Provides methods to bump or lower version numbers according to semantic versioning rules.
 * This trait is designed to be used with the GregPriday\Version\Version class.
 */
trait VersionBumpingTrait
{
    /**
     * Bumps the major version number by 1.
     * Resets minor and patch to 0.
     * By default, strips pre-release and build metadata.
     *
     * @param bool $preservePreRelease Whether to preserve the pre-release info (default: false)
     * @param bool $preserveBuildMetadata Whether to preserve the build metadata (default: false)
     * @return self A new Version instance with the updated version
     */
    public function bumpMajor(bool $preservePreRelease = false, bool $preserveBuildMetadata = false): self
    {
        $parts = $this->parts;
        $parts['major']++;
        $parts['minor'] = 0;
        $parts['patch'] = 0;
        
        if (!$preservePreRelease) {
            $parts['pre'] = null;
        }
        
        if (!$preserveBuildMetadata) {
            $parts['build'] = null;
        }
        
        $newVersionString = $this->buildVersionString($parts);
        
        return new self($newVersionString, $parts);
    }

    /**
     * Bumps the minor version number by 1.
     * Resets patch to 0.
     * By default, strips pre-release and build metadata.
     *
     * @param bool $preservePreRelease Whether to preserve the pre-release info (default: false)
     * @param bool $preserveBuildMetadata Whether to preserve the build metadata (default: false)
     * @return self A new Version instance with the updated version
     */
    public function bumpMinor(bool $preservePreRelease = false, bool $preserveBuildMetadata = false): self
    {
        $parts = $this->parts;
        $parts['minor']++;
        $parts['patch'] = 0;
        
        if (!$preservePreRelease) {
            $parts['pre'] = null;
        }
        
        if (!$preserveBuildMetadata) {
            $parts['build'] = null;
        }
        
        $newVersionString = $this->buildVersionString($parts);
        
        return new self($newVersionString, $parts);
    }

    /**
     * Bumps the patch version number by 1.
     * By default, strips pre-release and build metadata.
     *
     * @param bool $preservePreRelease Whether to preserve the pre-release info (default: false)
     * @param bool $preserveBuildMetadata Whether to preserve the build metadata (default: false)
     * @return self A new Version instance with the updated version
     */
    public function bumpPatch(bool $preservePreRelease = false, bool $preserveBuildMetadata = false): self
    {
        $parts = $this->parts;
        $parts['patch']++;
        
        if (!$preservePreRelease) {
            $parts['pre'] = null;
        }
        
        if (!$preserveBuildMetadata) {
            $parts['build'] = null;
        }
        
        $newVersionString = $this->buildVersionString($parts);
        
        return new self($newVersionString, $parts);
    }

    /**
     * Bumps or sets the pre-release identifier.
     * If no existing pre-release tag, sets it to the specified identifier.
     * If a pre-release tag exists with a numeric suffix, increments that suffix.
     *
     * @param string|null $identifier The pre-release identifier to use (default: 'beta')
     * @param bool $preserveBuildMetadata Whether to preserve the build metadata (default: false)
     * @return self A new Version instance with the updated version
     */
    public function bumpPreRelease(?string $identifier = 'beta', bool $preserveBuildMetadata = false): self
    {
        $parts = $this->parts;
        $currentPreRelease = $parts['pre'];
        
        if ($currentPreRelease === null) {
            // No pre-release exists, create one
            $parts['pre'] = $identifier;
        } else {
            // Pre-release exists, try to increment any numeric suffix
            if (preg_match('/^(.+)\.(\d+)$/', $currentPreRelease, $matches)) {
                // If it has a numeric suffix (e.g., beta.1), increment the number
                $baseIdentifier = $matches[1];
                $numericSuffix = (int) $matches[2];
                $parts['pre'] = $baseIdentifier . '.' . ($numericSuffix + 1);
            } else {
                // If it doesn't have a numeric suffix, add .1
                $parts['pre'] = $currentPreRelease . '.1';
            }
        }
        
        if (!$preserveBuildMetadata) {
            $parts['build'] = null;
        }
        
        $newVersionString = $this->buildVersionString($parts);
        
        return new self($newVersionString, $parts);
    }

    /**
     * Lowers the major version number by 1.
     * Optionally resets minor and patch.
     * Throws an exception if the major version is already 0.
     *
     * @param bool $resetMinor Whether to reset the minor version to 0 (default: false)
     * @param bool $resetPatch Whether to reset the patch version to 0 (default: false)
     * @param bool $preservePreRelease Whether to preserve the pre-release info (default: false)
     * @param bool $preserveBuildMetadata Whether to preserve the build metadata (default: false)
     * @return self A new Version instance with the updated version
     * @throws \InvalidArgumentException If the major version is already 0
     */
    public function lowerMajor(
        bool $resetMinor = false,
        bool $resetPatch = false,
        bool $preservePreRelease = false,
        bool $preserveBuildMetadata = false
    ): self {
        $parts = $this->parts;
        
        if ($parts['major'] <= 0) {
            throw new \InvalidArgumentException('Cannot lower major version below 0');
        }
        
        $parts['major']--;
        
        if ($resetMinor) {
            $parts['minor'] = 0;
        }
        
        if ($resetPatch) {
            $parts['patch'] = 0;
        }
        
        if (!$preservePreRelease) {
            $parts['pre'] = null;
        }
        
        if (!$preserveBuildMetadata) {
            $parts['build'] = null;
        }
        
        $newVersionString = $this->buildVersionString($parts);
        
        return new self($newVersionString, $parts);
    }

    /**
     * Lowers the minor version number by 1.
     * Optionally resets patch.
     * Throws an exception if the minor version is already 0.
     *
     * @param bool $resetPatch Whether to reset the patch version to 0 (default: false)
     * @param bool $preservePreRelease Whether to preserve the pre-release info (default: false)
     * @param bool $preserveBuildMetadata Whether to preserve the build metadata (default: false)
     * @return self A new Version instance with the updated version
     * @throws \InvalidArgumentException If the minor version is already 0
     */
    public function lowerMinor(
        bool $resetPatch = false,
        bool $preservePreRelease = false,
        bool $preserveBuildMetadata = false
    ): self {
        $parts = $this->parts;
        
        if ($parts['minor'] <= 0) {
            throw new \InvalidArgumentException('Cannot lower minor version below 0');
        }
        
        $parts['minor']--;
        
        if ($resetPatch) {
            $parts['patch'] = 0;
        }
        
        if (!$preservePreRelease) {
            $parts['pre'] = null;
        }
        
        if (!$preserveBuildMetadata) {
            $parts['build'] = null;
        }
        
        $newVersionString = $this->buildVersionString($parts);
        
        return new self($newVersionString, $parts);
    }

    /**
     * Lowers the patch version number by 1.
     * Throws an exception if the patch version is already 0.
     *
     * @param bool $preservePreRelease Whether to preserve the pre-release info (default: false)
     * @param bool $preserveBuildMetadata Whether to preserve the build metadata (default: false)
     * @return self A new Version instance with the updated version
     * @throws \InvalidArgumentException If the patch version is already 0
     */
    public function lowerPatch(bool $preservePreRelease = false, bool $preserveBuildMetadata = false): self
    {
        $parts = $this->parts;
        
        if ($parts['patch'] <= 0) {
            throw new \InvalidArgumentException('Cannot lower patch version below 0');
        }
        
        $parts['patch']--;
        
        if (!$preservePreRelease) {
            $parts['pre'] = null;
        }
        
        if (!$preserveBuildMetadata) {
            $parts['build'] = null;
        }
        
        $newVersionString = $this->buildVersionString($parts);
        
        return new self($newVersionString, $parts);
    }

    /**
     * Builds a version string from version parts.
     *
     * @param array $parts The version parts
     * @return string The formatted version string
     */
    private function buildVersionString(array $parts): string
    {
        $version = "{$parts['major']}.{$parts['minor']}.{$parts['patch']}";
        
        if (!empty($parts['pre'])) {
            $version .= "-{$parts['pre']}";
        }
        
        if (!empty($parts['build'])) {
            $version .= "+{$parts['build']}";
        }
        
        return $version;
    }
} 
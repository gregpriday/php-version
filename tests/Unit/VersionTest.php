<?php

namespace Tests\Unit;

use GregPriday\Version\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    #[DataProvider('validVersionsProvider')]
    public function test_constructor_with_valid_versions(
        string $versionString,
        int $expectedMajor,
        int $expectedMinor,
        int $expectedPatch,
        ?string $expectedPreRelease
    ): void {
        $version = Version::fromString($versionString);

        $this->assertEquals($expectedMajor, $version->getMajor());
        $this->assertEquals($expectedMinor, $version->getMinor());
        $this->assertEquals($expectedPatch, $version->getPatch());
        $this->assertEquals($expectedPreRelease, $version->getPreRelease());
    }

    public static function validVersionsProvider(): array
    {
        return [
            'standard version' => ['1.2.3', 1, 2, 3, null],
            'zero major version' => ['0.9.5', 0, 9, 5, null],
            'alpha prerelease' => ['1.0.0-alpha', 1, 0, 0, 'alpha'],
            'beta prerelease' => ['2.3.4-beta', 2, 3, 4, 'beta'],
            'rc prerelease' => ['1.0.0-rc1', 1, 0, 0, 'rc1'],
            'complex prerelease' => ['3.1.4-beta.2', 3, 1, 4, 'beta.2'],
            'complex prerelease with dots' => ['0.9.9-alpha.beta.1', 0, 9, 9, 'alpha.beta.1'],
        ];
    }

    #[DataProvider('invalidVersionsProvider')]
    public function test_constructor_with_invalid_versions(string $versionString): void
    {
        $version = Version::fromString($versionString);

        $this->assertNull($version->getMajor());
        $this->assertNull($version->getMinor());
        $this->assertNull($version->getPatch());
        $this->assertNull($version->getPreRelease());
    }

    public static function invalidVersionsProvider(): array
    {
        return [
            'missing patch' => ['1.2'],
            'missing minor and patch' => ['1'],
            'text only' => ['version'],
            'invalid format' => ['1.2.3.4'],
            'invalid characters' => ['1.2.x'],
            'empty string' => [''],
        ];
    }

    #[DataProvider('stableVersionsProvider')]
    public function test_is_stable(string $versionString, bool $expectedIsStable): void
    {
        $version = Version::fromString($versionString);
        $this->assertEquals($expectedIsStable, $version->isStable());
    }

    public static function stableVersionsProvider(): array
    {
        return [
            'stable 1.0.0' => ['1.0.0', true],
            'stable higher major' => ['2.0.0', true],
            'stable with minor and patch' => ['1.2.3', true],
            'unstable 0.x' => ['0.9.5', false],
            'unstable with prerelease' => ['1.0.0-alpha', false],
            'unstable with beta' => ['1.0.0-beta', false],
            'unstable with rc' => ['1.0.0-rc1', false],
            'invalid version' => ['invalid', false],
        ];
    }

    #[DataProvider('preReleaseVersionsProvider')]
    public function test_is_pre_release(string $versionString, bool $expectedIsPreRelease): void
    {
        $version = Version::fromString($versionString);
        $this->assertEquals($expectedIsPreRelease, $version->isPreRelease());
    }

    public static function preReleaseVersionsProvider(): array
    {
        return [
            'stable version' => ['1.0.0', false],
            'stable with minor and patch' => ['1.2.3', false],
            'unstable 0.x but not prerelease' => ['0.9.5', false],
            'alpha prerelease' => ['1.0.0-alpha', true],
            'beta prerelease' => ['2.3.4-beta', true],
            'rc prerelease' => ['1.0.0-rc1', true],
            'complex prerelease' => ['3.1.4-beta.2', true],
            'invalid version' => ['invalid', false],
        ];
    }

    public function test_get_extra_info(): void
    {
        $version = Version::fromString('1.2.3-beta');
        $extraInfo = $version->getExtraInfo();

        $this->assertEquals('1.2.3-beta', $extraInfo['version']);
        $this->assertEquals(1, $extraInfo['major']);
        $this->assertEquals(2, $extraInfo['minor']);
        $this->assertEquals(3, $extraInfo['patch']);
        $this->assertEquals('beta', $extraInfo['pre_release']);
        $this->assertEquals(false, $extraInfo['is_stable']);

        // Test with a stable version
        $stableVersion = Version::fromString('2.0.0');
        $stableExtraInfo = $stableVersion->getExtraInfo();

        $this->assertEquals('2.0.0', $stableExtraInfo['version']);
        $this->assertEquals(2, $stableExtraInfo['major']);
        $this->assertEquals(0, $stableExtraInfo['minor']);
        $this->assertEquals(0, $stableExtraInfo['patch']);
        $this->assertNull($stableExtraInfo['pre_release']);
        $this->assertTrue($stableExtraInfo['is_stable']);
    }
}

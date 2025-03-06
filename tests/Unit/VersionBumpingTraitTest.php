<?php

namespace Tests\Unit;

use GregPriday\Version\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionBumpingTraitTest extends TestCase
{
    /**
     * Test bumping major version.
     */
    #[DataProvider('majorBumpProvider')]
    public function test_bump_major(
        string $initialVersion,
        string $expectedVersion,
        bool $preservePreRelease = false,
        bool $preserveBuildMetadata = false
    ): void {
        $version = Version::fromString($initialVersion);
        $bumpedVersion = $version->bumpMajor($preservePreRelease, $preserveBuildMetadata);

        $this->assertEquals($expectedVersion, $bumpedVersion->getExtraInfo()['version']);
        $this->assertEquals($version->getMajor() + 1, $bumpedVersion->getMajor());
        $this->assertEquals(0, $bumpedVersion->getMinor());
        $this->assertEquals(0, $bumpedVersion->getPatch());

        if ($preservePreRelease) {
            $this->assertEquals($version->getPreRelease(), $bumpedVersion->getPreRelease());
        } else {
            $this->assertNull($bumpedVersion->getPreRelease());
        }

        if ($preserveBuildMetadata) {
            $this->assertEquals($version->getBuildMetadata(), $bumpedVersion->getBuildMetadata());
        } else {
            $this->assertNull($bumpedVersion->getBuildMetadata());
        }
    }

    /**
     * Data provider for major version bumping tests.
     */
    public static function majorBumpProvider(): array
    {
        return [
            'simple version' => ['1.2.3', '2.0.0'],
            'zero major version' => ['0.9.5', '1.0.0'],
            'preserving pre-release' => ['1.2.3-beta', '2.0.0-beta', true],
            'preserving build metadata' => ['1.2.3+build.123', '2.0.0+build.123', false, true],
            'preserving both' => ['1.2.3-alpha+build.456', '2.0.0-alpha+build.456', true, true],
        ];
    }

    /**
     * Test bumping minor version.
     */
    #[DataProvider('minorBumpProvider')]
    public function test_bump_minor(
        string $initialVersion,
        string $expectedVersion,
        bool $preservePreRelease = false,
        bool $preserveBuildMetadata = false
    ): void {
        $version = Version::fromString($initialVersion);
        $bumpedVersion = $version->bumpMinor($preservePreRelease, $preserveBuildMetadata);

        $this->assertEquals($expectedVersion, $bumpedVersion->getExtraInfo()['version']);
        $this->assertEquals($version->getMajor(), $bumpedVersion->getMajor());
        $this->assertEquals($version->getMinor() + 1, $bumpedVersion->getMinor());
        $this->assertEquals(0, $bumpedVersion->getPatch());

        if ($preservePreRelease) {
            $this->assertEquals($version->getPreRelease(), $bumpedVersion->getPreRelease());
        } else {
            $this->assertNull($bumpedVersion->getPreRelease());
        }

        if ($preserveBuildMetadata) {
            $this->assertEquals($version->getBuildMetadata(), $bumpedVersion->getBuildMetadata());
        } else {
            $this->assertNull($bumpedVersion->getBuildMetadata());
        }
    }

    /**
     * Data provider for minor version bumping tests.
     */
    public static function minorBumpProvider(): array
    {
        return [
            'simple version' => ['1.2.3', '1.3.0'],
            'zero minor version' => ['1.0.5', '1.1.0'],
            'preserving pre-release' => ['1.2.3-beta', '1.3.0-beta', true],
            'preserving build metadata' => ['1.2.3+build.123', '1.3.0+build.123', false, true],
            'preserving both' => ['1.2.3-alpha+build.456', '1.3.0-alpha+build.456', true, true],
        ];
    }

    /**
     * Test bumping patch version.
     */
    #[DataProvider('patchBumpProvider')]
    public function test_bump_patch(
        string $initialVersion,
        string $expectedVersion,
        bool $preservePreRelease = false,
        bool $preserveBuildMetadata = false
    ): void {
        $version = Version::fromString($initialVersion);
        $bumpedVersion = $version->bumpPatch($preservePreRelease, $preserveBuildMetadata);

        $this->assertEquals($expectedVersion, $bumpedVersion->getExtraInfo()['version']);
        $this->assertEquals($version->getMajor(), $bumpedVersion->getMajor());
        $this->assertEquals($version->getMinor(), $bumpedVersion->getMinor());
        $this->assertEquals($version->getPatch() + 1, $bumpedVersion->getPatch());

        if ($preservePreRelease) {
            $this->assertEquals($version->getPreRelease(), $bumpedVersion->getPreRelease());
        } else {
            $this->assertNull($bumpedVersion->getPreRelease());
        }

        if ($preserveBuildMetadata) {
            $this->assertEquals($version->getBuildMetadata(), $bumpedVersion->getBuildMetadata());
        } else {
            $this->assertNull($bumpedVersion->getBuildMetadata());
        }
    }

    /**
     * Data provider for patch version bumping tests.
     */
    public static function patchBumpProvider(): array
    {
        return [
            'simple version' => ['1.2.3', '1.2.4'],
            'zero patch version' => ['1.2.0', '1.2.1'],
            'preserving pre-release' => ['1.2.3-beta', '1.2.4-beta', true],
            'preserving build metadata' => ['1.2.3+build.123', '1.2.4+build.123', false, true],
            'preserving both' => ['1.2.3-alpha+build.456', '1.2.4-alpha+build.456', true, true],
        ];
    }

    /**
     * Test bumping pre-release version.
     */
    #[DataProvider('preReleaseBumpProvider')]
    public function test_bump_pre_release(
        string $initialVersion,
        string $expectedVersion,
        ?string $identifier = 'beta',
        bool $preserveBuildMetadata = false
    ): void {
        $version = Version::fromString($initialVersion);
        $bumpedVersion = $version->bumpPreRelease($identifier, $preserveBuildMetadata);

        $this->assertEquals($expectedVersion, $bumpedVersion->getExtraInfo()['version']);
        $this->assertEquals($version->getMajor(), $bumpedVersion->getMajor());
        $this->assertEquals($version->getMinor(), $bumpedVersion->getMinor());
        $this->assertEquals($version->getPatch(), $bumpedVersion->getPatch());
        $this->assertNotNull($bumpedVersion->getPreRelease());

        if ($preserveBuildMetadata) {
            $this->assertEquals($version->getBuildMetadata(), $bumpedVersion->getBuildMetadata());
        } else {
            $this->assertNull($bumpedVersion->getBuildMetadata());
        }
    }

    /**
     * Data provider for pre-release version bumping tests.
     */
    public static function preReleaseBumpProvider(): array
    {
        return [
            'add beta to stable version' => ['1.2.3', '1.2.3-beta'],
            'add alpha to stable version' => ['1.2.3', '1.2.3-alpha', 'alpha'],
            'increment beta with number' => ['1.2.3-beta.1', '1.2.3-beta.2'],
            'add number to beta' => ['1.2.3-beta', '1.2.3-beta.1'],
            'preserve build metadata' => ['1.2.3-beta+build.123', '1.2.3-beta.1+build.123', 'beta', true],
        ];
    }

    /**
     * Test lowering major version.
     */
    #[DataProvider('majorLowerProvider')]
    public function test_lower_major(
        string $initialVersion,
        string $expectedVersion,
        bool $resetMinor = false,
        bool $resetPatch = false,
        bool $preservePreRelease = false,
        bool $preserveBuildMetadata = false
    ): void {
        $version = Version::fromString($initialVersion);
        $loweredVersion = $version->lowerMajor($resetMinor, $resetPatch, $preservePreRelease, $preserveBuildMetadata);

        $this->assertEquals($expectedVersion, $loweredVersion->getExtraInfo()['version']);
        $this->assertEquals($version->getMajor() - 1, $loweredVersion->getMajor());

        if ($resetMinor) {
            $this->assertEquals(0, $loweredVersion->getMinor());
        } else {
            $this->assertEquals($version->getMinor(), $loweredVersion->getMinor());
        }

        if ($resetPatch) {
            $this->assertEquals(0, $loweredVersion->getPatch());
        } else {
            $this->assertEquals($version->getPatch(), $loweredVersion->getPatch());
        }

        if ($preservePreRelease) {
            $this->assertEquals($version->getPreRelease(), $loweredVersion->getPreRelease());
        } else {
            $this->assertNull($loweredVersion->getPreRelease());
        }

        if ($preserveBuildMetadata) {
            $this->assertEquals($version->getBuildMetadata(), $loweredVersion->getBuildMetadata());
        } else {
            $this->assertNull($loweredVersion->getBuildMetadata());
        }
    }

    /**
     * Data provider for major version lowering tests.
     */
    public static function majorLowerProvider(): array
    {
        return [
            'simple version' => ['2.3.4', '1.3.4'],
            'reset minor' => ['2.3.4', '1.0.4', true],
            'reset patch' => ['2.3.4', '1.3.0', false, true],
            'reset both' => ['2.3.4', '1.0.0', true, true],
            'preserve pre-release' => ['2.3.4-beta', '1.3.4-beta', false, false, true],
            'preserve build metadata' => ['2.3.4+build.123', '1.3.4+build.123', false, false, false, true],
            'preserve both' => ['2.3.4-alpha+build.456', '1.3.4-alpha+build.456', false, false, true, true],
        ];
    }

    /**
     * Test that lowering major version throws an exception when at 0.
     */
    public function test_lower_major_throws_exception_at_zero(): void
    {
        $version = Version::fromString('0.1.0');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot lower major version below 0');

        $version->lowerMajor();
    }

    /**
     * Test lowering minor version.
     */
    #[DataProvider('minorLowerProvider')]
    public function test_lower_minor(
        string $initialVersion,
        string $expectedVersion,
        bool $resetPatch = false,
        bool $preservePreRelease = false,
        bool $preserveBuildMetadata = false
    ): void {
        $version = Version::fromString($initialVersion);
        $loweredVersion = $version->lowerMinor($resetPatch, $preservePreRelease, $preserveBuildMetadata);

        $this->assertEquals($expectedVersion, $loweredVersion->getExtraInfo()['version']);
        $this->assertEquals($version->getMajor(), $loweredVersion->getMajor());
        $this->assertEquals($version->getMinor() - 1, $loweredVersion->getMinor());

        if ($resetPatch) {
            $this->assertEquals(0, $loweredVersion->getPatch());
        } else {
            $this->assertEquals($version->getPatch(), $loweredVersion->getPatch());
        }

        if ($preservePreRelease) {
            $this->assertEquals($version->getPreRelease(), $loweredVersion->getPreRelease());
        } else {
            $this->assertNull($loweredVersion->getPreRelease());
        }

        if ($preserveBuildMetadata) {
            $this->assertEquals($version->getBuildMetadata(), $loweredVersion->getBuildMetadata());
        } else {
            $this->assertNull($loweredVersion->getBuildMetadata());
        }
    }

    /**
     * Data provider for minor version lowering tests.
     */
    public static function minorLowerProvider(): array
    {
        return [
            'simple version' => ['1.3.4', '1.2.4'],
            'reset patch' => ['1.3.4', '1.2.0', true],
            'preserve pre-release' => ['1.3.4-beta', '1.2.4-beta', false, true],
            'preserve build metadata' => ['1.3.4+build.123', '1.2.4+build.123', false, false, true],
            'preserve both' => ['1.3.4-alpha+build.456', '1.2.4-alpha+build.456', false, true, true],
        ];
    }

    /**
     * Test that lowering minor version throws an exception when at 0.
     */
    public function test_lower_minor_throws_exception_at_zero(): void
    {
        $version = Version::fromString('1.0.0');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot lower minor version below 0');

        $version->lowerMinor();
    }

    /**
     * Test lowering patch version.
     */
    #[DataProvider('patchLowerProvider')]
    public function test_lower_patch(
        string $initialVersion,
        string $expectedVersion,
        bool $preservePreRelease = false,
        bool $preserveBuildMetadata = false
    ): void {
        $version = Version::fromString($initialVersion);
        $loweredVersion = $version->lowerPatch($preservePreRelease, $preserveBuildMetadata);

        $this->assertEquals($expectedVersion, $loweredVersion->getExtraInfo()['version']);
        $this->assertEquals($version->getMajor(), $loweredVersion->getMajor());
        $this->assertEquals($version->getMinor(), $loweredVersion->getMinor());
        $this->assertEquals($version->getPatch() - 1, $loweredVersion->getPatch());

        if ($preservePreRelease) {
            $this->assertEquals($version->getPreRelease(), $loweredVersion->getPreRelease());
        } else {
            $this->assertNull($loweredVersion->getPreRelease());
        }

        if ($preserveBuildMetadata) {
            $this->assertEquals($version->getBuildMetadata(), $loweredVersion->getBuildMetadata());
        } else {
            $this->assertNull($loweredVersion->getBuildMetadata());
        }
    }

    /**
     * Data provider for patch version lowering tests.
     */
    public static function patchLowerProvider(): array
    {
        return [
            'simple version' => ['1.2.3', '1.2.2'],
            'preserve pre-release' => ['1.2.3-beta', '1.2.2-beta', true],
            'preserve build metadata' => ['1.2.3+build.123', '1.2.2+build.123', false, true],
            'preserve both' => ['1.2.3-alpha+build.456', '1.2.2-alpha+build.456', true, true],
        ];
    }

    /**
     * Test that lowering patch version throws an exception when at 0.
     */
    public function test_lower_patch_throws_exception_at_zero(): void
    {
        $version = Version::fromString('1.2.0');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot lower patch version below 0');

        $version->lowerPatch();
    }
}

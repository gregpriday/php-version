<?php

namespace Tests\Unit;

use GregPriday\Version\Parser\VersionParser;
use GregPriday\Version\Version;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionParserTest extends TestCase
{
    #[DataProvider('validVersionsProvider')]
    public function test_parse(
        string $versionString,
        int $expectedMajor,
        int $expectedMinor,
        int $expectedPatch,
        ?string $expectedPreRelease,
        ?string $expectedBuild = null
    ): void {
        $parser = new VersionParser;
        $parts = $parser->parse($versionString);

        $this->assertNotNull($parts);
        $this->assertEquals($expectedMajor, $parts['major']);
        $this->assertEquals($expectedMinor, $parts['minor']);
        $this->assertEquals($expectedPatch, $parts['patch']);
        $this->assertEquals($expectedPreRelease, $parts['pre']);
        $this->assertEquals($expectedBuild, $parts['build']);
    }

    public static function validVersionsProvider(): array
    {
        return [
            'standard version' => ['1.2.3', 1, 2, 3, null, null],
            'zero major version' => ['0.9.5', 0, 9, 5, null, null],
            'alpha prerelease' => ['1.0.0-alpha', 1, 0, 0, 'alpha', null],
            'beta prerelease' => ['2.3.4-beta', 2, 3, 4, 'beta', null],
            'rc prerelease' => ['1.0.0-rc1', 1, 0, 0, 'rc1', null],
            'complex prerelease' => ['3.1.4-beta.2', 3, 1, 4, 'beta.2', null],
            'complex prerelease with dots' => ['0.9.9-alpha.beta.1', 0, 9, 9, 'alpha.beta.1', null],
            'with build metadata' => ['1.2.3+build.1', 1, 2, 3, null, 'build.1'],
            'prerelease with build metadata' => ['1.0.0-alpha+build.123', 1, 0, 0, 'alpha', 'build.123'],
        ];
    }

    #[DataProvider('invalidVersionsProvider')]
    public function test_parse_invalid(string $versionString): void
    {
        $parser = new VersionParser;
        $parts = $parser->parse($versionString);

        $this->assertNull($parts);
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

    public function test_create_version(): void
    {
        $parser = new VersionParser;
        $version = $parser->createVersion('1.2.3-beta');

        $this->assertInstanceOf(Version::class, $version);
        $this->assertEquals(1, $version->getMajor());
        $this->assertEquals(2, $version->getMinor());
        $this->assertEquals(3, $version->getPatch());
        $this->assertEquals('beta', $version->getPreRelease());
    }

    public static function parseVersionProvider(): array
    {
        return [
            'simple version' => ['1.0.0', 1, 0, 0, null, null],
            'alpha prerelease' => ['1.0.0-alpha', 1, 0, 0, 'alpha', null],
            'beta prerelease' => ['2.3.4-beta', 2, 3, 4, 'beta', null],
            'rc prerelease' => ['1.0.0-rc1', 1, 0, 0, 'rc1', null],
            'complex prerelease' => ['3.1.4-beta.2', 3, 1, 4, 'beta.2', null],
            'complex prerelease with dots' => ['0.9.9-alpha.beta.1', 0, 9, 9, 'alpha.beta.1', null],
            'with build metadata' => ['1.2.3+build.1', 1, 2, 3, null, 'build.1'],
        ];
    }

    /**
     * Test parsing version strings with build metadata.
     */
    public function test_parse_version_with_build_metadata(): void
    {
        $parser = new VersionParser;

        // Test without build metadata
        $parts = $parser->parse('1.2.3');
        $this->assertEquals(1, $parts['major']);
        $this->assertEquals(2, $parts['minor']);
        $this->assertEquals(3, $parts['patch']);
        $this->assertNull($parts['pre']);
        $this->assertNull($parts['build']);

        // Test with build metadata only
        $parts = $parser->parse('1.2.3+build.1');
        $this->assertEquals(1, $parts['major']);
        $this->assertEquals(2, $parts['minor']);
        $this->assertEquals(3, $parts['patch']);
        $this->assertNull($parts['pre']);
        $this->assertEquals('build.1', $parts['build']);

        // Test with pre-release and build metadata
        $parts = $parser->parse('1.2.3-alpha+build.1');
        $this->assertEquals(1, $parts['major']);
        $this->assertEquals(2, $parts['minor']);
        $this->assertEquals(3, $parts['patch']);
        $this->assertEquals('alpha', $parts['pre']);
        $this->assertEquals('build.1', $parts['build']);

        // Test complex build metadata
        $parts = $parser->parse('2.0.0+exp.sha.5114f85');
        $this->assertEquals(2, $parts['major']);
        $this->assertEquals(0, $parts['minor']);
        $this->assertEquals(0, $parts['patch']);
        $this->assertNull($parts['pre']);
        $this->assertEquals('exp.sha.5114f85', $parts['build']);
    }

    /**
     * Test loose parsing of version strings.
     */
    #[DataProvider('looseVersionsProvider')]
    public function test_loose_parsing(
        string $versionString,
        int $expectedMajor,
        int $expectedMinor,
        int $expectedPatch,
        ?string $expectedPreRelease,
        ?string $expectedBuild = null
    ): void {
        $parser = new VersionParser;
        $parts = $parser->parse($versionString, false); // strict = false

        $this->assertNotNull($parts);
        $this->assertEquals($expectedMajor, $parts['major']);
        $this->assertEquals($expectedMinor, $parts['minor']);
        $this->assertEquals($expectedPatch, $parts['patch']);
        $this->assertEquals($expectedPreRelease, $parts['pre']);
        $this->assertEquals($expectedBuild, $parts['build']);
    }

    public static function looseVersionsProvider(): array
    {
        return [
            'standard version still works' => ['1.2.3', 1, 2, 3, null, null],
            'v prefix' => ['v1.2.3', 1, 2, 3, null, null],
            'major.minor only' => ['1.2', 1, 2, 0, null, null],
            'major only' => ['1', 1, 0, 0, null, null],
            'v prefix with major only' => ['v2', 2, 0, 0, null, null],
            'v prefix with major.minor' => ['v2.1', 2, 1, 0, null, null],
            'v prefix with prerelease' => ['v1.2.3-beta', 1, 2, 3, 'beta', null],
            'v prefix with build metadata' => ['v1.2.3+build.1', 1, 2, 3, null, 'build.1'],
            'major only with prerelease' => ['1-alpha', 1, 0, 0, 'alpha', null],
            'major.minor with prerelease' => ['1.2-beta', 1, 2, 0, 'beta', null],
        ];
    }

    /**
     * Test that invalid version strings stay invalid even in loose mode.
     */
    #[DataProvider('invalidLooseVersionsProvider')]
    public function test_loose_parsing_invalid(string $versionString): void
    {
        $parser = new VersionParser;
        $parts = $parser->parse($versionString, false); // strict = false

        $this->assertNull($parts);
    }

    public static function invalidLooseVersionsProvider(): array
    {
        return [
            'text only' => ['version'],
            'invalid format' => ['1.2.3.4'],
            'invalid characters' => ['1.2.x'],
            'empty string' => [''],
        ];
    }

    /**
     * Test Version::fromString with strict and loose modes
     */
    public function test_version_from_string_modes(): void
    {
        // Test strict mode (should throw an exception)
        $this->expectException(\InvalidArgumentException::class);
        Version::fromString('v1.2.3');

        // This line won't be executed due to the exception
    }

    /**
     * Test Version::fromString with loose mode
     */
    public function test_version_from_string_loose_mode(): void
    {
        // Test loose mode
        $version = Version::fromString('v1.2.3', false);
        $this->assertInstanceOf(Version::class, $version);
        $this->assertEquals(1, $version->getMajor());
        $this->assertEquals(2, $version->getMinor());
        $this->assertEquals(3, $version->getPatch());
        $this->assertNull($version->getPreRelease());

        // Test loose mode with major.minor only
        $version = Version::fromString('1.2', false);
        $this->assertInstanceOf(Version::class, $version);
        $this->assertEquals(1, $version->getMajor());
        $this->assertEquals(2, $version->getMinor());
        $this->assertEquals(0, $version->getPatch());
        $this->assertNull($version->getPreRelease());

        // Test loose mode with major only
        $version = Version::fromString('1', false);
        $this->assertInstanceOf(Version::class, $version);
        $this->assertEquals(1, $version->getMajor());
        $this->assertEquals(0, $version->getMinor());
        $this->assertEquals(0, $version->getPatch());
        $this->assertNull($version->getPreRelease());
    }
}

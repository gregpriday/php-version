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
        ?string $expectedPreRelease
    ): void {
        $parser = new VersionParser;
        $parts = $parser->parse($versionString);

        $this->assertNotNull($parts);
        $this->assertEquals($expectedMajor, $parts['major']);
        $this->assertEquals($expectedMinor, $parts['minor']);
        $this->assertEquals($expectedPatch, $parts['patch']);
        $this->assertEquals($expectedPreRelease, $parts['pre']);
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
}

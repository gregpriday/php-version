# PHP Version Parser

A simple, powerful PHP class for parsing, validating, and comparing semantic version strings.

## Installation

```bash
composer require gregpriday/php-version
```

## Usage

```php
use GregPriday\Version\Version;

// Create a new Version instance
$version = new Version('1.2.3-beta');

// Check if the version is stable
if ($version->isStable()) {
    echo "The version is stable\n";
} else {
    echo "The version is not stable\n";
}

// Check if it's a pre-release
if ($version->isPreRelease()) {
    echo "This is a pre-release version: " . $version->getPreRelease() . "\n";
}

// Get version components
echo "Major: " . $version->getMajor() . "\n";
echo "Minor: " . $version->getMinor() . "\n";
echo "Patch: " . $version->getPatch() . "\n";

// Get all version information at once
$info = $version->getExtraInfo();
print_r($info);
```

## Running Tests

To run the tests, first install the dependencies:

```bash
composer install
```

Then run PHPUnit:

```bash
vendor/bin/phpunit
```

## Requirements

- PHP 8.0 or higher

## License

[MIT](LICENSE) 
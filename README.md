# PHP Version

A simple yet powerful library for parsing, validating, comparing, and manipulating semantic version strings in PHP. It also includes flexible support for version constraints (e.g., `^1.2.3`, `>=1.0.0 <2.0.0`) to check whether a particular version satisfies one or more complex conditions.

## Installation

```bash
composer require gregpriday/php-version
```

## Overview

This library offers:

- **Strict or Loose Parsing** of version strings (e.g. `"1.2.3"`, `"v1.2.3"`, `"1.2"`, `"1"`).
- **Version Object** to access and modify version components (major, minor, patch, pre-release, and build metadata).
- **SemVer Checks** to see if a version is stable or a pre-release.
- **Version Bumping/Lowering** (increment/decrement major, minor, patch) including preserving or clearing pre-release/build metadata.
- **Constraint Parsing and Evaluation** using a fluent, chainable syntax with logical **AND** and **OR** conditions.

## Basic Usage Example

Below is a quick snapshot of how you might use the library to parse a version, check its properties, bump the version, and validate it against constraints.

```php
use GregPriday\Version\Version;
use GregPriday\Version\Constraint\VersionConstraintParser;

// 1. Create a version object (strict mode by default).
$version = new Version('1.2.3-beta');

// 2. Inspect the version
echo "Major: " . $version->getMajor() . "\n";       // 1
echo "Minor: " . $version->getMinor() . "\n";       // 2
echo "Patch: " . $version->getPatch() . "\n";       // 3
echo "Pre-release: " . $version->getPreRelease() . "\n"; // beta

// 3. Check stability
if ($version->isStable()) {
    echo "Version is stable.\n";
} else {
    echo "Version is not stable.\n";
}

// 4. Bump the version (bump minor, reset patch to 0, remove pre-release)
$bumped = $version->bumpMinor();
echo "Bumped Version: " . $bumped->getExtraInfo()['version'] . "\n"; // "1.3.0"

// 5. Parse constraints and check if a version satisfies them
$parser = new VersionConstraintParser();
$rangeSet = $parser->parseConstraints('>=1.0.0 <2.0.0 || ^3.0.0');

if ($rangeSet->isSatisfiedBy($bumped)) {
    echo $bumped->getExtraInfo()['version']." satisfies the constraint.\n";
} else {
    echo $bumped->getExtraInfo()['version']." does not satisfy the constraint.\n";
}
```

---

## Creating and Inspecting Versions

### Strict vs. Loose Parsing

- **Strict mode** (default): Expects full `major.minor.patch` (optionally `-preRelease` and/or `+buildMetadata`). Examples of valid strict versions:
    - `1.0.0`
    - `0.9.5-alpha+build.1`
- **Loose mode**: More lenient. Accepts shorter forms and can include a leading `v`. Examples of acceptable loose versions:
    - `v1.2.3`
    - `1.2` (interpreted as `1.2.0`)
    - `1` (interpreted as `1.0.0`)

```php
// Strict mode (throws InvalidArgumentException if invalid)
$strictVersion = new Version('1.2.3'); 

// Loose mode
$looseVersion = new Version('v1.2', null, false);
// Internally becomes 1.2.0
```

### Accessing Components

```php
$version = new Version('1.2.3-alpha+build.123');

// Basic components
echo $version->getMajor();      // 1
echo $version->getMinor();      // 2
echo $version->getPatch();      // 3

// Pre-release and build metadata
echo $version->getPreRelease();    // alpha
echo $version->getBuildMetadata(); // build.123

// Extra info array (includes stability check)
$info = $version->getExtraInfo();
print_r($info);
/*
Array
(
    [version] => 1.2.3-alpha+build.123
    [major] => 1
    [minor] => 2
    [patch] => 3
    [pre_release] => alpha
    [build_metadata] => build.123
    [is_stable] => 
)
*/
```

### Stability and Pre-Release Checks

```php
$version = new Version('1.0.0-rc1');
if ($version->isStable()) {
    // Major >= 1 and no pre-release
    echo "Stable release.\n";
} else {
    echo "Not stable.\n"; // This will run in this example
}

if ($version->isPreRelease()) {
    echo "It's a pre-release!\n"; // True for "1.0.0-rc1"
}
```

---

## Bumping and Lowering Version Numbers

The `VersionBumpingTrait` gives you methods to increment or decrement specific parts of a version. Each method returns a **new** `Version` instance. By default, these operations **clear** pre-release and build metadata, but you can preserve them if you wish.

### Bumping (Incrementing)

```php
$version = new Version('1.2.3-beta+build.123');

// Bump Major: becomes 2.0.0 (clears pre-release & build by default)
$bumpedMajor = $version->bumpMajor();
echo $bumpedMajor->getExtraInfo()['version']; // 2.0.0

// Bump Minor but preserve pre-release and build metadata
$bumpedMinor = $version->bumpMinor(true, true);
echo $bumpedMinor->getExtraInfo()['version']; // 1.3.0-beta+build.123

// Bump Patch: 1.2.4 (default clears pre-release and build)
$bumpedPatch = $version->bumpPatch();
echo $bumpedPatch->getExtraInfo()['version']; // 1.2.4

// Bump (or set) Pre-release: 
// - If no pre-release, sets the given identifier. 
// - If pre-release is something like "beta.1", it increments the last number.
$newPre = $version->bumpPreRelease('alpha', true);
echo $newPre->getExtraInfo()['version']; // "1.2.3-beta.1+build.123"
```

### Lowering (Decrementing)

You can similarly reduce major, minor, or patch. By default, these also clear pre-release/build metadata, unless you preserve them.

```php
$version = new Version('2.3.4-beta+build.456');

// Lower Major, resetting minor/patch, discarding pre-release/build
$loweredMajor = $version->lowerMajor(true, true);
echo $loweredMajor->getExtraInfo()['version']; // "1.0.0"

// Lower Minor, preserving pre-release
$loweredMinor = $version->lowerMinor(false, true);
echo $loweredMinor->getExtraInfo()['version']; // "2.2.4-beta"

// Lower Patch, preserving build metadata
$loweredPatch = $version->lowerPatch(false, true);
echo $loweredPatch->getExtraInfo()['version']; // "2.3.3+build.456"
```

> **Note**: Lowering a `0` major/minor/patch throws an exception, since negative version segments are invalid.

---

## Parsing and Evaluating Constraints

The library can parse powerful **OR** and **AND** constraints using the `VersionConstraintParser`. This enables checks such as `>=1.0.0 <2.0.0 || ^3.0.0`.

### Operators

- **Basic**: `>`, `>=`, `<`, `<=`, `=`, `==`, `!`, `!=`
- **Caret** `^`: e.g. `^1.2.3` means `>=1.2.3` and `<2.0.0` (for 1.x versions)
- **Tilde** `~`: e.g. `~1.2.3` means `>=1.2.3` and `<1.3.0`

### Combining Constraints

- **AND**: Use space or commas. For example:  
  `>=1.0.0 <2.0.0`  
  `>=1.0.0, <2.0.0`  
  This means the version must satisfy **both** constraints.
- **OR**: Split with `||`. For example:  
  `^1.0.0 || ^2.0.0`  
  Means the version must satisfy **either** `^1.0.0` **or** `^2.0.0`.

### Example: Parsing Constraints

```php
use GregPriday\Version\Constraint\VersionConstraintParser;
use GregPriday\Version\Version;

$parser = new VersionConstraintParser();

// Single constraint
$rangeSet = $parser->parseConstraints('>=1.2.3');
var_dump($rangeSet->isSatisfiedBy(new Version('1.2.3'))); // true
var_dump($rangeSet->isSatisfiedBy(new Version('1.2.2'))); // false

// Multiple (AND) constraints
$rangeSet = $parser->parseConstraints('>=1.0.0 <2.0.0');
// This means: version >= 1.0.0 AND version < 2.0.0
var_dump($rangeSet->isSatisfiedBy(new Version('1.5.0'))); // true
var_dump($rangeSet->isSatisfiedBy(new Version('2.1.0'))); // false

// OR constraints
$rangeSet = $parser->parseConstraints('^1.0.0 || ~2.0.0');
// This means: (version in ^1.0.0) OR (version in ~2.0.0)
// ^1.0.0 = >=1.0.0 <2.0.0
// ~2.0.0 = >=2.0.0 <2.1.0
var_dump($rangeSet->isSatisfiedBy(new Version('1.9.9'))); // true
var_dump($rangeSet->isSatisfiedBy(new Version('2.0.5'))); // true
var_dump($rangeSet->isSatisfiedBy(new Version('2.1.0'))); // false

// Negation examples
$rangeSet = $parser->parseConstraints('!=1.0.0');
var_dump($rangeSet->isSatisfiedBy(new Version('1.0.0'))); // false
var_dump($rangeSet->isSatisfiedBy(new Version('1.0.1'))); // true
```

---

## Testing

To run the test suite, clone this repository (or have it locally) and install dev dependencies:

```bash
composer install
```

Then run:

```bash
vendor/bin/phpunit
```

This runs the PHPUnit tests under `tests/`.

### Code Formatting

A [Laravel Pint](https://github.com/laravel/pint) configuration is included for formatting. To format the code:

```bash
composer format
```

---

## License

This project is open source under the [MIT license](LICENSE).
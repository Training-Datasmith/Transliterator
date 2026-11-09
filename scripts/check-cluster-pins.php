<?php

declare(strict_types=1);

/**
 * Verify cluster pins (empty expected map = no org forks pinned for this repo).
 * Self-tests wrong SHA / alias detection on installed.php and installed.json.
 * Fail if any package is installed from Training-Datasmith without being expected.
 */

$expected = [];

$installedPhp = __DIR__ . '/../vendor/composer/installed.php';
if (!is_readable($installedPhp)) {
    fwrite(STDERR, "check-cluster-pins: vendor/composer/installed.php not found; run composer update first.\n");
    exit(1);
}

$installedJson = __DIR__ . '/../vendor/composer/installed.json';
if (!is_readable($installedJson)) {
    fwrite(STDERR, "check-cluster-pins: vendor/composer/installed.json not found; run composer update first.\n");
    exit(1);
}

/** @var array{versions: array<string, array{reference?: string, aliases?: list<string>}>} $installed */
$installed = require $installedPhp;
$versions = $installed['versions'] ?? [];

$jsonRaw = file_get_contents($installedJson);
if ($jsonRaw === false) {
    fwrite(STDERR, "check-cluster-pins: could not read vendor/composer/installed.json.\n");
    exit(1);
}

/** @var array{packages?: list<array<string, mixed>>, 'packages-dev'?: list<array<string, mixed>>}|list<array<string, mixed>>} $jsonData */
$jsonData = json_decode($jsonRaw, true, 512);
if (!is_array($jsonData)) {
    fwrite(STDERR, "check-cluster-pins: invalid vendor/composer/installed.json.\n");
    exit(1);
}

/** @var array<string, string> $jsonReferences */
$jsonReferences = [];
/** @var list<array<string, mixed>> $allPackages */
$allPackages = [];
$packageLists = [];
if (isset($jsonData['packages']) && is_array($jsonData['packages'])) {
    $packageLists[] = $jsonData['packages'];
}
if (isset($jsonData['packages-dev']) && is_array($jsonData['packages-dev'])) {
    $packageLists[] = $jsonData['packages-dev'];
}
if ($packageLists === [] && is_array($jsonData) && array_keys($jsonData) === range(0, count($jsonData) - 1)) {
    $packageLists[] = $jsonData;
}

foreach ($packageLists as $packages) {
    foreach ($packages as $package) {
        if (!is_array($package) || !isset($package['name']) || !is_string($package['name'])) {
            continue;
        }
        $allPackages[] = $package;
        $source = $package['source'] ?? null;
        if (!is_array($source)) {
            continue;
        }
        $reference = $source['reference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            continue;
        }
        $jsonReferences[$package['name']] = strtolower($reference);
    }
}

/**
 * @param array<string, array{alias: string, reference: string}> $map
 *
 * @return list<string>
 */
function checkClusterPinsInstalledPhp(array $map, array $versions): array
{
    $errors = [];

    foreach ($map as $name => $want) {
        if (!isset($versions[$name])) {
            $errors[] = sprintf('%s is not installed.', $name);
            continue;
        }

        $pkg = $versions[$name];
        $aliases = $pkg['aliases'] ?? [];
        if (!in_array($want['alias'], $aliases, true)) {
            $errors[] = sprintf(
                '%s alias mismatch (expected %s in aliases %s).',
                $name,
                $want['alias'],
                json_encode($aliases)
            );
        }

        $reference = $pkg['reference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            $errors[] = sprintf('%s has no reference.', $name);
            continue;
        }

        if (!hash_equals($want['reference'], $reference)) {
            $errors[] = sprintf(
                '%s reference mismatch (expected %s, got %s).',
                $name,
                $want['reference'],
                $reference
            );
        }
    }

    return $errors;
}

/**
 * @param array<string, array{alias: string, reference: string}> $map
 * @param array<string, string> $jsonReferences
 *
 * @return list<string>
 */
function checkClusterPinsInstalledJson(array $map, array $jsonReferences): array
{
    $errors = [];

    foreach ($map as $name => $want) {
        if (!isset($jsonReferences[$name])) {
            $errors[] = sprintf('%s is missing source.reference in installed.json.', $name);
            continue;
        }

        $reference = $jsonReferences[$name];
        if (!hash_equals(strtolower($want['reference']), $reference)) {
            $errors[] = sprintf(
                '%s installed.json source.reference mismatch (expected %s, got %s).',
                $name,
                $want['reference'],
                $reference
            );
        }
    }

    return $errors;
}

/**
 * @param list<array<string, mixed>> $allPackages
 * @param array<string, array{alias: string, reference: string}> $expected
 *
 * @return list<string>
 */
function findUnexpectedTrainingDatasmithPackages(array $allPackages, array $expected): array
{
    $errors = [];
    foreach ($allPackages as $package) {
        if (!isset($package['name']) || !is_string($package['name'])) {
            continue;
        }
        $name = $package['name'];
        if (isset($expected[$name])) {
            continue;
        }
        $source = $package['source'] ?? null;
        if (!is_array($source)) {
            continue;
        }
        $url = $source['url'] ?? '';
        if (!is_string($url) || stripos($url, 'Training-Datasmith') === false) {
            continue;
        }
        $reference = $source['reference'] ?? '(unknown)';
        $errors[] = sprintf(
            '%s is installed from Training-Datasmith (%s @ %s) but is not in the expected pin map.',
            $name,
            $url,
            is_string($reference) ? $reference : '(unknown)'
        );
    }

    return $errors;
}

/**
 * @param list<string> $errors
 */
function hasPackageReferenceMismatch(array $errors, string $package, string $suffix): bool
{
    foreach ($errors as $error) {
        if (strpos($error, $package) !== false && strpos($error, $suffix) !== false) {
            return true;
        }
    }

    return false;
}

$probePackage = 'phpunit/phpunit';
if (!isset($versions[$probePackage])) {
    fwrite(STDERR, "check-cluster-pins: {$probePackage} is not installed; cannot run wrong-SHA self-test.\n");
    exit(1);
}

$realReference = $versions[$probePackage]['reference'] ?? null;
if (!is_string($realReference) || strlen($realReference) !== 40) {
    fwrite(STDERR, "check-cluster-pins: {$probePackage} has no 40-char reference for self-test.\n");
    exit(1);
}

if (!isset($jsonReferences[$probePackage])) {
    fwrite(STDERR, "check-cluster-pins: {$probePackage} is missing from installed.json references.\n");
    exit(1);
}

// Wrong SHA: installed.php (in-memory expected map only; vendor files untouched).
$wrongPhpDemo = [
    $probePackage => [
        'alias' => '9.9.9',
        'reference' => '0000000000000000000000000000000000000000',
    ],
];
$demoPhpErrors = checkClusterPinsInstalledPhp($wrongPhpDemo, $versions);
if ($demoPhpErrors === [] || !hasPackageReferenceMismatch($demoPhpErrors, $probePackage, 'reference mismatch')) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA self-test failed to detect bad reference in installed.php.\n");
    foreach ($demoPhpErrors as $error) {
        fwrite(STDERR, $error . "\n");
    }
    exit(1);
}

// Wrong SHA: installed.json (real map vs wrong expected).
$wrongJsonDemo = [
    $probePackage => [
        'alias' => '9.9.9',
        'reference' => '0000000000000000000000000000000000000000',
    ],
];
$demoJsonErrors = checkClusterPinsInstalledJson($wrongJsonDemo, $jsonReferences);
if ($demoJsonErrors === [] || !hasPackageReferenceMismatch($demoJsonErrors, $probePackage, 'source.reference mismatch')) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA self-test failed to detect bad reference in installed.json.\n");
    foreach ($demoJsonErrors as $error) {
        fwrite(STDERR, $error . "\n");
    }
    exit(1);
}

// Control: correct reference must not trip reference-mismatch checks.
$refControl = [
    $probePackage => [
        'alias' => '9.9.9',
        'reference' => $realReference,
    ],
];
$refControlPhpErrors = checkClusterPinsInstalledPhp($refControl, $versions);
if (hasPackageReferenceMismatch($refControlPhpErrors, $probePackage, 'reference mismatch')) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA control failed for installed.php (reference mismatch with correct SHA).\n");
    exit(1);
}
$refControlJsonErrors = checkClusterPinsInstalledJson($refControl, $jsonReferences);
if (hasPackageReferenceMismatch($refControlJsonErrors, $probePackage, 'source.reference mismatch')) {
    fwrite(STDERR, "check-cluster-pins: wrong-SHA control failed for installed.json.\n");
    exit(1);
}

// Alias mismatch control (wrong alias, correct reference).
$aliasDemo = [
    $probePackage => [
        'alias' => '0.0.0',
        'reference' => $realReference,
    ],
];
$aliasErrors = checkClusterPinsInstalledPhp($aliasDemo, $versions);
if ($aliasErrors === [] || !hasPackageReferenceMismatch($aliasErrors, $probePackage, 'alias mismatch')) {
    fwrite(STDERR, "check-cluster-pins: alias self-test failed to detect alias mismatch.\n");
    exit(1);
}
if (hasPackageReferenceMismatch($aliasErrors, $probePackage, 'reference mismatch')) {
    fwrite(STDERR, "check-cluster-pins: alias self-test incorrectly reported reference mismatch.\n");
    exit(1);
}

fwrite(STDOUT, "check-cluster-pins: wrong-SHA self-test detected installed.php reference mismatch and installed.json source.reference mismatch.\n");

$unexpected = findUnexpectedTrainingDatasmithPackages($allPackages, $expected);
$phpErrors = checkClusterPinsInstalledPhp($expected, $versions);
$jsonErrors = checkClusterPinsInstalledJson($expected, $jsonReferences);
$errors = array_merge($unexpected, $phpErrors, $jsonErrors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, 'check-cluster-pins: ' . $error . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "check-cluster-pins: all cluster packages match expected SHAs and aliases.\n");
exit(0);

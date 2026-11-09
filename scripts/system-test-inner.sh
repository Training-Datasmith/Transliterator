#!/usr/bin/env bash
set -euo pipefail

export COMPOSER_ALLOW_SUPERUSER=1
export LANG=C

git config --global --add safe.directory /app 2>/dev/null || true

strip_ansi() {
    local infile="$1"
    local outfile="$2"
    sed -E 's/\x1b\[[0-9;]*[[:alpha:]]//g' "${infile}" >"${outfile}"
}

fail_deprecation_output() {
    local label="$1"
    local file="$2"
    local stripped
    stripped="$(mktemp)"
    strip_ansi "${file}" "${stripped}"
    if grep -E '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}" >/dev/null; then
        echo "${label}: output contains Warning, Deprecated, or Notice lines" >&2
        grep -E '^(PHP )?(Warning|Deprecated|Notice):' "${stripped}" >&2 || true
        rm -f "${stripped}"
        exit 1
    fi
    rm -f "${stripped}"
}

php -v | tee /tmp/php-version.txt
php -r '
$v = PHP_VERSION;
if (version_compare($v, "7.2.0", "<") || version_compare($v, "7.3.0", ">=")) {
    fwrite(STDERR, "PHP {$v} is outside required >=7.2.0 <7.3.0 (digest-pinned 7.2 floor)\n");
    exit(1);
}
'

for ext in ctype curl dom filter json libxml mbstring phar tokenizer xml xmlwriter; do
    if ! php -m | grep -qi "^${ext}$"; then
        echo "Missing required PHP extension: ${ext}" >&2
        exit 1
    fi
done

COMPOSER_URL="https://getcomposer.org/download/2.8.10/composer.phar"
COMPOSER_SHA256="28dbb6bd8bef31479c7985b774c130a8bda37dbe63c35b56f6cb6bc377427573"
curl -fsSL -o /tmp/composer.phar "${COMPOSER_URL}"
echo "${COMPOSER_SHA256}  /tmp/composer.phar" | sha256sum -c -

validate_out="$(mktemp)"
set +e
php /tmp/composer.phar validate --strict --no-check-lock >"${validate_out}" 2>&1
validate_status=$?
set -e
cat "${validate_out}"
fail_deprecation_output "composer validate" "${validate_out}"
if [[ ${validate_status} -ne 0 ]]; then
    rm -f "${validate_out}"
    exit "${validate_status}"
fi
validate_stripped="$(mktemp)"
strip_ansi "${validate_out}" "${validate_stripped}"
if ! grep -E '^\./composer\.json is valid$' "${validate_stripped}" >/dev/null; then
    echo 'composer validate: missing strict success line ./composer.json is valid' >&2
    rm -f "${validate_stripped}" "${validate_out}"
    exit 1
fi
rm -f "${validate_stripped}" "${validate_out}"

composer_out="$(mktemp)"
set +e
php /tmp/composer.phar update --no-interaction --prefer-dist --no-progress >"${composer_out}" 2>&1
composer_status=$?
set -e
cat "${composer_out}"
fail_deprecation_output "composer update" "${composer_out}"
if [[ ${composer_status} -ne 0 ]]; then
    rm -f "${composer_out}"
    exit "${composer_status}"
fi
rm -f "${composer_out}"

pins_out="$(mktemp)"
set +e
php scripts/check-cluster-pins.php >"${pins_out}" 2>&1
pins_status=$?
set -e
cat "${pins_out}"
fail_deprecation_output "check-cluster-pins" "${pins_out}"
if [[ ${pins_status} -ne 0 ]]; then
    rm -f "${pins_out}"
    exit "${pins_status}"
fi
pins_stripped="$(mktemp)"
strip_ansi "${pins_out}" "${pins_stripped}"
if ! grep -Fxq 'check-cluster-pins: wrong-SHA self-test detected installed.php reference mismatch and installed.json source.reference mismatch.' "${pins_stripped}"; then
    echo 'check-cluster-pins: missing wrong-SHA self-test success line' >&2
    rm -f "${pins_stripped}" "${pins_out}"
    exit 1
fi
if ! grep -Fxq 'check-cluster-pins: all cluster packages match expected SHAs and aliases.' "${pins_stripped}"; then
    echo 'check-cluster-pins: missing final success line' >&2
    rm -f "${pins_stripped}" "${pins_out}"
    exit 1
fi
rm -f "${pins_stripped}" "${pins_out}"

audit_out="$(mktemp)"
set +e
php /tmp/composer.phar audit >"${audit_out}" 2>&1
audit_status=$?
set -e
cat "${audit_out}"
fail_deprecation_output "composer audit" "${audit_out}"
if [[ ${audit_status} -ne 0 ]]; then
    rm -f "${audit_out}"
    exit "${audit_status}"
fi
audit_stripped="$(mktemp)"
strip_ansi "${audit_out}" "${audit_stripped}"
if ! grep -E '^No security vulnerability advisories found\.$' "${audit_stripped}" >/dev/null; then
    echo 'composer audit: missing exact success line No security vulnerability advisories found.' >&2
    rm -f "${audit_stripped}" "${audit_out}"
    exit 1
fi
rm -f "${audit_stripped}" "${audit_out}"

phpunit_out="$(mktemp)"
set +e
vendor/bin/phpunit --colors=never --no-coverage >"${phpunit_out}" 2>&1
phpunit_status=$?
set -e
cat "${phpunit_out}"
fail_deprecation_output "phpunit" "${phpunit_out}"
if [[ ${phpunit_status} -ne 0 ]]; then
    rm -f "${phpunit_out}"
    exit "${phpunit_status}"
fi
phpunit_stripped="$(mktemp)"
strip_ansi "${phpunit_out}" "${phpunit_stripped}"
if ! grep -E '^OK \(204 tests, 554 assertions\)$' "${phpunit_stripped}" >/dev/null; then
    echo 'phpunit: missing strict OK (204 tests, 554 assertions) success line' >&2
    grep -E '^OK \(' "${phpunit_stripped}" >&2 || true
    rm -f "${phpunit_stripped}" "${phpunit_out}"
    exit 1
fi
rm -f "${phpunit_stripped}" "${phpunit_out}"

echo 'system-test: all steps passed.'

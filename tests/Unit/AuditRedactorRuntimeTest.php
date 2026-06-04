<?php

declare(strict_types=1);

use Larena\Audit\Runtime\DefaultAuditRedactor;

require_once __DIR__ . '/../../vendor/autoload.php';

function assert_redactor_true(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$redactor = new DefaultAuditRedactor();
$payload = [
    'reason' => 'mfa_required',
    'token' => 'secret-token',
    'ip' => '127.0.0.1',
];

$redacted = $redactor->redact($payload, ['token'], ['password']);

assert_redactor_true($redacted['reason'] === 'mfa_required', 'Non-sensitive fields must remain intact.');
assert_redactor_true($redacted['token'] === DefaultAuditRedactor::REDACTED_VALUE, 'Sensitive fields must be redacted.');
assert_redactor_true($redacted['ip'] === '127.0.0.1', 'Searchable metadata must remain available.');

$failed = false;

try {
    $redactor->redact(['private_key' => 'raw'], [], ['private_key']);
} catch (InvalidArgumentException) {
    $failed = true;
}

assert_redactor_true($failed, 'Forbidden fields must fail closed.');

echo "AuditRedactorRuntimeTest passed.\n";

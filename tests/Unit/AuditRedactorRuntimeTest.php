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

$nestedRedacted = $redactor->redact([
    'context' => [
        'attempts' => [
            ['token' => 'nested-secret', 'reason' => 'mfa_required'],
        ],
    ],
], ['token'], ['password']);
assert_redactor_true(
    $nestedRedacted['context']['attempts'][0]['token'] === DefaultAuditRedactor::REDACTED_VALUE,
    'Sensitive payload keys must be redacted recursively inside associative and list arrays.',
);
assert_redactor_true(
    $nestedRedacted['context']['attempts'][0]['reason'] === 'mfa_required',
    'Recursive redaction must preserve non-sensitive sibling metadata.',
);

$failed = false;

try {
    $redactor->redact(['private_key' => 'raw'], [], ['private_key']);
} catch (InvalidArgumentException) {
    $failed = true;
}

assert_redactor_true($failed, 'Forbidden fields must fail closed.');

$nestedFailed = false;
try {
    $redactor->redact(['context' => ['proofs' => [['password' => 'raw']]]], [], ['password']);
} catch (InvalidArgumentException) {
    $nestedFailed = true;
}
assert_redactor_true($nestedFailed, 'Forbidden payload keys must fail closed at any nesting depth.');

$redactedParentFailed = false;
try {
    $redactor->redact(
        ['token' => ['password' => 'raw']],
        ['token'],
        ['password'],
    );
} catch (InvalidArgumentException) {
    $redactedParentFailed = true;
}
assert_redactor_true(
    $redactedParentFailed,
    'A redacted parent must not hide a forbidden descendant from fail-closed validation.',
);

echo "AuditRedactorRuntimeTest passed.\n";

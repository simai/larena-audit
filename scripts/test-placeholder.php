<?php

declare(strict_types=1);

$testFiles = [
    'tests/Unit/AuditEventContractTest.php',
    'tests/Unit/AuditFailsClosedTest.php',
];

foreach ($testFiles as $testFile) {
    if (!is_file($testFile)) {
        fwrite(STDERR, "Missing test file: {$testFile}" . PHP_EOL);
        exit(1);
    }

    passthru('php ' . escapeshellarg($testFile), $exitCode);
    if ($exitCode !== 0) {
        exit($exitCode);
    }
}

echo "Larena Audit contract tests passed.\n";

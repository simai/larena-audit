<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;

function auditPipelineMySqlExpect(bool $condition, string $reason): void
{
    if (!$condition) {
        throw new RuntimeException($reason);
    }
}

/** @return array<string, string> */
function auditPipelineMySqlParseEnv(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    auditPipelineMySqlExpect(is_array($lines), 'audit_pipeline_mysql_env_unreadable');

    $values = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        if (str_starts_with($trimmed, 'export ')) {
            $trimmed = ltrim(substr($trimmed, 7));
        }
        if (preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $trimmed, $matches) !== 1) {
            continue;
        }

        $value = trim($matches[2]);
        if (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
            $value = substr($value, 1, -1);
        } elseif (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
            $value = stripcslashes(substr($value, 1, -1));
        } else {
            $value = preg_replace('/\s+#.*$/', '', $value) ?? $value;
        }
        $values[$matches[1]] = $value;
    }

    return $values;
}

/**
 * @param list<string> $command
 *
 * @return array{status: int, output: string}
 */
function auditPipelineMySqlCommand(array $command, string $cwd): array
{
    $pipes = [];
    $process = proc_open($command, [
        0 => ['file', '/dev/null', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $cwd);
    auditPipelineMySqlExpect(is_resource($process), 'audit_pipeline_mysql_command_start_failed');

    $output = '';
    foreach ([1, 2] as $index) {
        if (isset($pipes[$index]) && is_resource($pipes[$index])) {
            $output .= stream_get_contents($pipes[$index]);
            fclose($pipes[$index]);
        }
    }

    return ['status' => proc_close($process), 'output' => trim($output)];
}

/** @return array{host: string, port: int, username: string, password: string} */
function auditPipelineMySqlCredentials(): array
{
    $declaredPath = getenv('LARENA_AUDIT_MYSQL_ENV_FILE');
    auditPipelineMySqlExpect(
        is_string($declaredPath) && $declaredPath !== '' && str_starts_with($declaredPath, '/'),
        'audit_pipeline_mysql_env_file_not_explicit',
    );
    auditPipelineMySqlExpect(is_file($declaredPath), 'audit_pipeline_mysql_env_file_missing');

    $declaredDirectory = dirname($declaredPath);
    $gitRootResult = auditPipelineMySqlCommand(
        ['git', 'rev-parse', '--show-toplevel'],
        $declaredDirectory,
    );
    auditPipelineMySqlExpect($gitRootResult['status'] === 0, 'audit_pipeline_mysql_env_not_in_git_worktree');
    $gitRoot = realpath($gitRootResult['output']);
    auditPipelineMySqlExpect(is_string($gitRoot), 'audit_pipeline_mysql_env_git_root_invalid');
    auditPipelineMySqlExpect(
        str_starts_with($declaredPath, $gitRoot . '/'),
        'audit_pipeline_mysql_env_outside_git_worktree',
    );

    $declaredRelativePath = substr($declaredPath, strlen($gitRoot) + 1);
    auditPipelineMySqlExpect($declaredRelativePath !== '', 'audit_pipeline_mysql_env_relative_path_invalid');
    auditPipelineMySqlExpect(
        auditPipelineMySqlCommand(['git', 'check-ignore', '--quiet', '--', $declaredRelativePath], $gitRoot)['status'] === 0,
        'audit_pipeline_mysql_env_not_ignored',
    );
    auditPipelineMySqlExpect(
        auditPipelineMySqlCommand(['git', 'ls-files', '--error-unmatch', '--', $declaredRelativePath], $gitRoot)['status'] !== 0,
        'audit_pipeline_mysql_env_tracked',
    );

    $realPath = realpath($declaredPath);
    auditPipelineMySqlExpect(is_string($realPath) && is_file($realPath), 'audit_pipeline_mysql_env_target_invalid');
    auditPipelineMySqlExpect(
        str_starts_with($realPath, $gitRoot . '/'),
        'audit_pipeline_mysql_env_target_outside_git_worktree',
    );
    $targetRelativePath = substr($realPath, strlen($gitRoot) + 1);
    auditPipelineMySqlExpect(
        auditPipelineMySqlCommand(['git', 'check-ignore', '--quiet', '--', $targetRelativePath], $gitRoot)['status'] === 0,
        'audit_pipeline_mysql_env_target_not_ignored',
    );
    auditPipelineMySqlExpect(
        auditPipelineMySqlCommand(['git', 'ls-files', '--error-unmatch', '--', $targetRelativePath], $gitRoot)['status'] !== 0,
        'audit_pipeline_mysql_env_target_tracked',
    );

    $permissions = fileperms($realPath);
    auditPipelineMySqlExpect(
        is_int($permissions) && ($permissions & 0o077) === 0,
        'audit_pipeline_mysql_env_permissions_unsafe',
    );

    $values = auditPipelineMySqlParseEnv($realPath);
    foreach (['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'] as $required) {
        auditPipelineMySqlExpect(array_key_exists($required, $values), 'audit_pipeline_mysql_env_incomplete');
    }
    auditPipelineMySqlExpect(
        array_diff(array_keys($values), ['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD']) === [],
        'audit_pipeline_mysql_env_contains_unexpected_keys',
    );

    $host = trim($values['DB_HOST']);
    auditPipelineMySqlExpect(
        in_array(strtolower($host), ['127.0.0.1', 'localhost', '::1'], true),
        'audit_pipeline_mysql_host_not_local',
    );
    auditPipelineMySqlExpect(ctype_digit($values['DB_PORT']), 'audit_pipeline_mysql_port_invalid');
    $port = (int) $values['DB_PORT'];
    auditPipelineMySqlExpect($port >= 1 && $port <= 65535, 'audit_pipeline_mysql_port_invalid');
    auditPipelineMySqlExpect($values['DB_USERNAME'] !== '', 'audit_pipeline_mysql_username_invalid');

    return [
        'host' => $host,
        'port' => $port,
        'username' => $values['DB_USERNAME'],
        'password' => $values['DB_PASSWORD'],
    ];
}

/** @param array{host: string, port: int, username: string, password: string} $credentials */
function auditPipelineMySqlServer(array $credentials): PDO
{
    return new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $credentials['host'], $credentials['port']),
        $credentials['username'],
        $credentials['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false],
    );
}

/**
 * @param array{
 *     driver: string,
 *     host: string,
 *     port: int,
 *     database: string,
 *     username: string,
 *     password: string,
 *     charset: string,
 *     collation: string,
 *     prefix: string,
 *     strict: bool
 * } $config
 */
function auditPipelineMySqlConnection(array $config): Connection
{
    $container = new Application();
    $capsule = new Capsule($container);
    $capsule->addConnection($config, 'audit_mysql');
    $capsule->getDatabaseManager()->setDefaultConnection('audit_mysql');
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    $connection = $capsule->getConnection('audit_mysql');
    $container->instance('db', $capsule->getDatabaseManager());
    $container->instance('db.connection', $connection);
    $container->instance('db.schema', $connection->getSchemaBuilder());
    Facade::setFacadeApplication($container);
    Facade::clearResolvedInstances();
    Schema::swap($connection->getSchemaBuilder());

    return $connection;
}

function auditPipelineMySqlMigration(Connection $connection, bool $up): void
{
    Schema::swap($connection->getSchemaBuilder());
    $migration = require __DIR__ . '/../../database/migrations/2026_07_09_000001_create_larena_audit_events_table.php';
    $up ? $migration->up() : $migration->down();
}

/**
 * @param array{host: string, port: int, username: string, password: string} $credentials
 */
function auditPipelineMySqlRegisterCleanup(
    bool &$cleanupPending,
    string $database,
    string $databaseAllowlist,
    array $credentials,
): void {
    register_shutdown_function(static function () use (
        &$cleanupPending,
        $database,
        $databaseAllowlist,
        $credentials,
    ): void {
        if (!$cleanupPending || preg_match($databaseAllowlist, $database) !== 1) {
            return;
        }

        try {
            auditPipelineMySqlServer($credentials)->exec('DROP DATABASE IF EXISTS `' . $database . '`');
        } catch (Throwable) {
            // The synchronous finally block is authoritative; this is a last-resort cleanup.
        }
    });
}

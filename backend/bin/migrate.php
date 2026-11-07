<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config.php';

use App\Database;

$pdo = Database::connect();
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    )'
);

$migrationFiles = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
sort($migrationFiles, SORT_STRING);

foreach ($migrationFiles as $migrationFile) {
    $version = basename($migrationFile);
    $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
    $check->execute(['version' => $version]);

    if ($check->fetchColumn() !== false) {
        continue;
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec((string) file_get_contents($migrationFile));
        $record = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (:version)');
        $record->execute(['version' => $version]);
        $pdo->commit();
        fwrite(STDOUT, "Applied $version" . PHP_EOL);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        fwrite(STDERR, "Migration failed: $version" . PHP_EOL);
        exit(1);
    }
}
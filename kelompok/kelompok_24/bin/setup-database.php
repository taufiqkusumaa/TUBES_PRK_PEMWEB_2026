<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

try {
    $db = database();
    $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Skema database tidak dapat dibaca.');
    }
    $db->exec($schema);

    $migrations = glob(APP_ROOT . '/database/migrations/*.sql') ?: [];
    sort($migrations, SORT_STRING);
    foreach ($migrations as $migrationFile) {
        $migration = file_get_contents($migrationFile);
        if ($migration === false) {
            throw new RuntimeException('Migration tidak dapat dibaca: ' . basename($migrationFile));
        }
        $db->exec($migration);
    }

    fwrite(STDOUT, "Skema dan migration database ADVOKITA berhasil diterapkan.\n");
} catch (Throwable $error) {
    fwrite(STDERR, 'Gagal menyiapkan database: ' . $error->getMessage() . "\n");
    exit(1);
}

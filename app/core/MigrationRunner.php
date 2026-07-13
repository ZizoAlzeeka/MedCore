<?php
/**
 * MigrationRunner — runs SQL migration files in database/migrations/.
 *
 * Tracks applied migrations in a `migrations` table.
 * Safe to call on every request — short-circuits if no new migrations.
 */
class MigrationRunner
{
    /**
     * Run all pending migrations. Returns array of applied migration filenames.
     */
    public static function run(): array
    {
        $applied = [];

        // Only run on MySQL (SQLite is for dev only and migrations are MySQL-specific)
        if (Database::isSqlite()) {
            return $applied;
        }

        try {
            $pdo = Database::getInstance()->pdo();

            // Ensure migrations tracking table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `filename` VARCHAR(255) NOT NULL,
                `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_migrations_filename` (`filename`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Get already-applied migrations
            $appliedRows = $pdo->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
            $appliedSet = array_flip($appliedRows);

            // Scan migration files
            $dir = dirname(__DIR__, 2) . '/database/migrations';
            if (!is_dir($dir)) {
                return $applied;
            }
            $files = glob($dir . '/*.sql');
            sort($files);

            foreach ($files as $file) {
                $filename = basename($file);
                if (isset($appliedSet[$filename])) {
                    continue; // already applied
                }

                $sql = file_get_contents($file);
                if ($sql === false) continue;

                try {
                    // Run the migration — multi-statement
                    $pdo->exec($sql);

                    // Mark as applied
                    $stmt = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
                    $stmt->execute([$filename]);

                    $applied[] = $filename;
                    Logger::info("Migration applied: $filename");
                } catch (Throwable $e) {
                    Logger::error("Migration failed: $filename — " . $e->getMessage());
                    // Stop running further migrations on error
                    break;
                }
            }
        } catch (Throwable $e) {
            Logger::error("MigrationRunner setup failed: " . $e->getMessage());
        }

        return $applied;
    }
}

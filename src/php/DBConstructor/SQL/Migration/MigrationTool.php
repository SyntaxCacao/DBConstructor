<?php

declare(strict_types=1);

namespace DBConstructor\SQL\Migration;

use PDOException;

class MigrationTool
{
    const SQL_DIR = "../sql";

    /**
     * @throws MigrationException
     */
    public static function run()
    {
        try {
            // Creates migration table if not exists
            Migration::createTable();

            // Loads last migration
            $last = Migration::loadLast();
            $lastId = $last === null ? 0 : intval($last->id);

            // Check whether database scheme is broken
            if ($last !== null && ! $last->success) {
                echo "<p>Last migration failed; not going to continue. The database scheme requires manual fixing.</p>";
                exit;
            }

            // Search for newer migration files
            $files = scandir(MigrationTool::SQL_DIR);

            if ($files === false) {
                throw new MigrationException("Could not scan src/sql directory (scandir() returned false)");
            }

            $sqlFiles = [];

            foreach ($files as $file) {
                if ($file === "." || $file === "..") {
                    continue;
                }

                $matches = [];

                // Check whether file has a valid migration file name and has not been applied already
                if (! preg_match("/^V(\d{4})__.+\.sql$/", $file, $matches) || intval($matches[1]) === 0 || intval($matches[1]) <= $lastId) {
                    continue;
                }

                $sqlFiles[intval($matches[1])] = $file;
            }

            if (count($sqlFiles) === 0) {
                // There are no new sql files to migrate
                echo "<p>No new sql files found. Last migration was #$lastId.</p>";
                exit;
            }

            // Sort list by ids and apply new migration
            ksort($sqlFiles);

            echo "<p>Discovered ".count($sqlFiles)." new migration".(count($sqlFiles) === 1 ? "" : "s").".</p>";

            foreach ($sqlFiles as $id => $sqlFile) {
                // Get migration description from file name
                $description = substr($sqlFile, 7);
                $description = substr($description, 0, strlen($description) - 4);
                $description = str_replace("_", " ", $description);

                // Get file contents
                $sql = file_get_contents(MigrationTool::SQL_DIR."/".$sqlFile);

                if ($sql === false) {
                    throw new MigrationException("Could not read file $sqlFile");
                }

                // Apply migration
                echo "<p>Applying migration #".htmlentities((string) $id)." – ".htmlentities($description)."</p>";
                Migration::apply((string) $id, $sql);
            }

            // Success!
            echo "<p>Migration process completed successfully.</p>";
        } catch (PDOException $exception) {
            throw new MigrationException("Something went wrong during migration process", $exception);
        }
    }
}

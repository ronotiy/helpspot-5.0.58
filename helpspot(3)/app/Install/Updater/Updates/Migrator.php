<?php

namespace HS\Install\Updater\Updates;

use Illuminate\Database\Migrations\Migrator as BaseMigrator;

/**
 * Currently not used, this let us specify which
 * migration files to run (specific ones, not just
 * any not already run).
 */
class Migrator extends BaseMigrator
{
    public function runUpdates($migrations, $path, $pretend = false)
    {
        $this->notes = [];

        $files = $this->getMigrationFiles($path);

        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        $ran = $this->repository->getRan();

        // Grab migrations as defined in $migrations
        // array that were not already run.
        $diff = array_diff($files, $ran);
        $migrationsForThisUpdate = array_filter($diff, function ($file) use ($migrations) {
            return in_array($file, $migrations);
        });

        $this->requireFiles($path, $migrationsForThisUpdate);

        $this->runMigrationList($migrationsForThisUpdate, $pretend);
    }
}

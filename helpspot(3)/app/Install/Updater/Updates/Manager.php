<?php

namespace HS\Install\Updater\Updates;

use Illuminate\Container\Container;

class Manager
{
    /**
     * @var \Illuminate\Container\Container
     */
    private $app;

    /**
     * @var array
     */
    protected $updates = [];

    /**
     * @var VersionSorterInterface
     */
    protected $sorter;

    /**
     * @var array
     */
    protected $sorted;

    public function __construct(Container $app, VersionSorterInterface $sorter)
    {
        $this->app = $app;
        $this->sorter = $sorter;
    }

    /**
     * Run updates needed to go from
     * given $fromVersion to given $toVersion.
     * @param $fromVersion
     * @param $toVersion
     */
    public function runUpdates($fromVersion, $toVersion)
    {
        // Run any Migrations
        $migrator = $this->app->make('Illuminate\Database\Migrations\Migrator', [
            'repository' => $this->app['migration.repository'],
            'resolver' => $this->app['db'],
            'files' => $this->app['files'],
        ]);
        $migrator->run(app()->databasePath('migrations'));

        // Run any HelpSpot Updates
        $updates = $this->getUpdates($fromVersion, $toVersion);

        foreach ($updates as $updateCollection) {

            foreach($updateCollection as $update) {
                if (is_string($update)) {
                    $update = $this->app->make($update);
                }

                $this->runUpdate($update);
            }
        }
    }

    /**
     * Register a possible update.
     * @param UpdateInterface|string $update
     * @param $version
     */
    public function registerUpdate($update, $version = null)
    {
        $version = ($update instanceof UpdateInterface) ? $update->getVersion() : $version;

        if (! isset($this->updates[$version])) {
            $this->updates[$version] = [];
        }

        $this->updates[$version][] = $update;
    }

    /**
     * Get sorted updates needed to upgrade
     * from given version to given version.
     * @param $fromVersion
     * @param $toVersion
     * @return array
     */
    public function getUpdates($fromVersion, $toVersion)
    {
        // Sort Updates
        $sortedUpdates = $this->getSortedUpdates();

        // Get updates from...to (May need more comparisons available in $sorter)
        return $this->sorter->slice($sortedUpdates, $fromVersion, $toVersion);
    }

    /**
     * Retrieve all updates,
     * sorted by version number.
     * @return mixed
     */
    protected function getSortedUpdates()
    {
        return $this->sorter->sort($this->updates);
    }

    /**
     * Run a single update.
     * @param UpdateInterface $update
     * @return mixed
     */
    protected function runUpdate(UpdateInterface $update)
    {
        return $update->run();
    }
}

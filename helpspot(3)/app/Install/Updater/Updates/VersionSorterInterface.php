<?php

namespace HS\Install\Updater\Updates;

interface VersionSorterInterface
{
    /**
     * Array of VersionInterface objects.
     * @param array $versions
     * @return mixed
     */
    public function sort(array $versions);

    /**
     * Return a slice of versions which fall within versions
     * (VersionInterface > $fromVersion || VersionInterface <= $toVersion).
     * @param $versions
     * @param $fromVersion
     * @param $toVersion
     * @return mixed
     */
    public function slice($versions, $fromVersion, $toVersion);
}

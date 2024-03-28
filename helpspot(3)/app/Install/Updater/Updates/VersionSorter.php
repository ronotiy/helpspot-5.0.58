<?php

namespace HS\Install\Updater\Updates;

use Naneau\SemVer\Sort;
use Naneau\SemVer\Parser;
use Naneau\SemVer\Compare;

class VersionSorter implements VersionSorterInterface
{
    /**
     * @var \Naneau\SemVer\Sort
     */
    protected $sorter;

    public function __construct(Sort $sorter)
    {
        $this->sorter = $sorter;
    }

    /**
     * Array of VersionInterface objects.
     * @param array $versions
     * @return mixed
     */
    public function sort(array $versions)
    {
        $versionStrings = array_keys($versions);

        // Sorts semver version strings
        $sortedStrings = $this->sorter->sort($versionStrings);

        // Note that this assumes $versions array was in format:
        // [ '1.2.3' => VersionInterface, '4.5.6.' => VersionInterface ]
        $sorted = [];
        foreach ($sortedStrings as $versionString) {
            $sorted[(string) $versionString] = $versions[(string) $versionString];
        }

        return $sorted;
    }

    /**
     * Return a slice of versions which fall within versions
     * (VersionInterface > $fromVersion && VersionInterface <= $toVersion).
     * @param $versions
     * @param $fromVersion
     * @param $toVersion
     * @return mixed
     */
    public function slice($versions, $fromVersion, $toVersion)
    {
        $sliced = [];
        $fromVersion = Parser::parse($fromVersion);
        $toVersion = Parser::parse($toVersion);

        foreach ($versions as $versionString => $versionClass) {
            $currentVersion = Parser::parse($versionString);

            // (VersionInterface > $fromVersion && VersionInterface <= $toVersion)
            if (Compare::greaterThan($currentVersion, $fromVersion) &&
                (Compare::smallerThan($currentVersion, $toVersion) || Compare::equals($currentVersion, $toVersion))) {
                $sliced[$versionString] = $versionClass;
            }
        }

        return $sliced;
    }
}

<?php

namespace HS\Portals;

use Exception;
use HS\MultiPortal;

class Generate
{
    /**
     * Loop through all the secondary portals
     * and create them on the file system.
     */
    public function portals()
    {
        MultiPortal::active()->get()->each(function(MultiPortal $portal) {
            $this->makePortal($portal);
        });
    }

    /**
     * Move a portal to new location.
     *
     * @param MultiPortal $portal
     * @param $oldPath
     * @throws Exception
     * @return bool
     */
    public function movePortal(MultiPortal $portal, $oldPath)
    {
        if ($portal->fIsPrimaryPortal) {
            if (
                // 1. Was a primary portal before, and still is -> Don't do anything
                ($oldPath == public_path() && $portal->sPortalPath == $oldPath)
                ||
                // 2. Was not a primary, but now is -> Don't do anything (keep old files, do not delete them)
                ($oldPath != public_path() && $portal->sPortalPath == public_path())
            ) {
                return false;
            }
        }

        // 3. Was a primary portal before, and now is not -> Create portal (no old files to move)
        if ($oldPath == public_path() && $portal->sPortalPath != $oldPath) {
            return $this->makePortal($portal);
        }

        // Don't perform a move if old portal files don't exist
        if (! file_exists($oldPath)) {
            return false;
        }

        // Otherwise we move the portal to the new path
        if (file_exists($portal->sPortalPath)) {
            throw new Exception(sprintf(
                'A file or directory already exists at "%s"', $portal->sPortalPath
            ));
        } elseif (! file_exists(dirname($portal->sPortalPath))) {
            // Potentially the portal lives in nested directories, and we need
            // to make sure the full directory path exists in the new path
            // We use dirname() so for a sPortalPath such as /var/www/helpspot/public/foo/bar,
            // we create directory /var/www/helpspot/public/foo
            @mkdir(dirname($portal->sPortalPath), 0775, $recursive = true);
        }

        $move = rename($oldPath, $portal->sPortalPath);

        if (! $move) {
            throw new Exception(sprintf(
                'Could not move the portal to location "%s"', $portal->sPortalPath
            ));
        }

        // Create (overwrite if exist!) index.php
        $writeIndex = file_put_contents($portal->sPortalPath.'/index.php', $this->getIndexTemplate($portal->xPortal, $portal->sHost));

        if (! $writeIndex) {
            throw new Exception(sprintf(
                'Could not write to portal index file "%s"', $portal->sPortalPath.'/index.php'
            ));
        }

        return true;
    }

    /**
     * Make a portal.
     *
     * @param MultiPortal $portal
     * @throws Exception
     * @return bool
     */
    public function makePortal(MultiPortal $portal)
    {
        // Don't create any new files if being used as primary portal
        if ($portal->fIsPrimaryPortal) {
            return false;
        }

        // Create secondary portal directory
        if (file_exists($portal->sPortalPath)) {
            throw new Exception(sprintf(
                'Portal at location "%s" cannot be created because a file or directory already exists there', $portal->sPortalPath
            ));
        }

        mkdir($portal->sPortalPath, 0775, $recursive = true);

        // Create (overwrite if exist!) index.php
        $writeIndex = file_put_contents($portal->sPortalPath.'/index.php', $this->getIndexTemplate($portal->xPortal, $portal->sHost));

        if (! $writeIndex) {
            throw new Exception(sprintf(
                'Could not write to portal index file "%s"', $portal->sPortalPath.'/index.php'
            ));
        }

        // Set file permissions (We don't set ownership, preferring to run command as correct user)
        hs_chmod($portal->sPortalPath.'/index.php', 0664);

        // Create empty custom_templates dir if not exists already
        if (! file_exists($portal->sPortalPath.'/custom_templates')) {
            mkdir($portal->sPortalPath.'/custom_templates', 0775);
        }

        return true;
    }

    /**
     * Generate a portal index template.
     *
     * @param int $portalId
     * @param string $portalHost
     * @return mixed
     */
    public function getIndexTemplate($portalId, $portalHost)
    {
        $file = file_get_contents(base_path('app/Portals/stubs/index.stub'));

        $file = str_replace('{{portal}}', $portalId, $file);
        $file = str_replace('{{chost}}', $portalHost, $file);
        return str_replace('{{indexpath}}', public_path('index.php'), $file);
    }
}

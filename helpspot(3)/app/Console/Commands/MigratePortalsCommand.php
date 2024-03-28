<?php

namespace HS\Console\Commands;

use HS\MultiPortal;
use HS\Portals\Generate;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MigratePortalsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portal:migrate {--dry-run : Output portals that will be moved, but do not move them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move portals in base path to public path, if able';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $portals = MultiPortal::active()
            ->get();

        if (! $portals->count()) {
            return $this->getOutput()->getErrorStyle()->writeln('<info>No active portals found</info>');
        }

        if ($this->option('dry-run')) {
            return $this->dryRun($portals);
        }

        return $this->movePortals($portals);
    }

    protected function dryRun($portals)
    {
        $portals->each(function(MultiPortal $portal) {
            $currentPath = $this->normalizePath($portal->sPortalPath);

            if ($this->shouldMovePortal($currentPath)) {
                $this->info(vsprintf('✅ Will move portal %s : %s (current path: %s)', [
                    $portal->xPortal,
                    $portal->sPortalName,
                    $portal->sPortalPath,
                ]));
            } else {
                $this->info(vsprintf('🔴 Will not move portal %s : %s (current path: %s)', [
                    $portal->xPortal,
                    $portal->sPortalName,
                    $portal->sPortalPath,
                ]));
            }
        });
    }

    protected function movePortals($portals)
    {
        $portals->each(function(MultiPortal $portal) {
            $currentPath = $this->normalizePath($portal->sPortalPath);

            if ($this->shouldMovePortal($currentPath)) {
                // Swap base_path() with public_path() to move the portal to web-server accessible directory
                $portal->sPortalPath = str_replace(base_path(), public_path(), $currentPath);

                // If this is likely a primary portal, we'll set that flag to true
                if ($portal->sPortalPath == public_path()) {
                    $portal->fIsPrimaryPortal = true;
                }

                $portal->save();

                // Move the portal into the new location
                // This also re-generates the portal index.php file
                try {
                    $moved = (new Generate)->movePortal($portal, $currentPath);
                    if (! $moved) {
                        Log::info(sprintf('[Portal Setup]: Portal files not moved, the files may not exist at "'.$currentPath.'"'), $portal->toLogArray());
                    }
                } catch(\Exception $e) {
                    Log::error($e); // Log the error but don't stop the update
                }
            }
        });
    }

    /**
     * Normalize file path
     * @param $path
     * @return string
     */
    protected function normalizePath($path) {
        return rtrim(trim($path), '/\\');
    }

    /**
     * Determine if should move portal
     * @param $path
     * @return bool
     */
    protected function shouldMovePortal($path) {
        // If
        //  portal path is *not* somewhere within the public path
        // and
        //  portal path *is* somewhere within the HelpSpot base path
        return (strpos($path, public_path()) === false && strpos($path, base_path()) !== false);
    }
}

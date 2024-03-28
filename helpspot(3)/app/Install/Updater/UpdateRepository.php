<?php

namespace HS\Install\Updater;

use Illuminate\Support\Arr;
use Illuminate\Log\LogManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use HS\Install\Tables\Copier\CopierFactory;

use Illuminate\Database\Migrations\Migrator;

class UpdateRepository
{
    /**
     * @var \Illuminate\Log\LogManager
     */
    protected $logger;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * @var string
     */
    protected $migrationPath;

    /**
     * @var \HS\Install\Tables\Copier\CopierFactory
     */
    protected $tableCopier;

    /**
     * @var \Illuminate\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * UpdateRepository constructor.
     * @param LogManager $logger
     * @param DatabaseManager $db
     * @param Migrator $migrator
     * @param $migrationPath
     * @param CopierFactory $tableCopier
     * @param Dispatcher $dispatcher
     */
    public function __construct(
        LogManager $logger, DatabaseManager $db, Migrator $migrator, $migrationPath,
        CopierFactory $tableCopier,
        Dispatcher $dispatcher)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->migrator = $migrator;
        $this->migrationPath = $migrationPath;
        $this->tableCopier = $tableCopier;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Run migrations
     * NOTE: THIS IS ONLY CALLED ON V3 TO V4 UPDATE  <-------
     *  - Point release updates *must* make use of
     *    Updates\BaseUpdater::migrate() method.
     * @param string $connection
     * @return $this
     */
    public function migrate($connection = null)
    {
        $db = $this->getConnection($connection);

        // Create Migrations table if does not exist
        if (! $db->getSchemaBuilder()->hasTable('HS_Migrations')) {
            $migRepo = $this->migrator->getRepository();
            $migRepo->createRepository();
        }

        $this->migrator->run($this->migrationPath);

        return $this;
    }

    /**
     * Return global value, if its defined
     * or from database HS_Settings if not.
     * @param string $setting
     * @param string $connection
     * @return mixed
     */
    public function getGlobal($setting, $connection = null)
    {
        if (! defined($setting)) {
            $db = $this->getConnection($connection);

            $attachmentLocation = $db->table('HS_Settings')
                ->where('sSetting', $setting)
                ->first();

            define($setting, $attachmentLocation->tValue);
        }

        return constant($setting);
    }

    /**
     * @param array $tables
     * @param string $sourceConnectionName
     * @param string $destConnectionName
     * @param $encodeFrom
     * @param $encodeTo
     */
    public function copyTables($tables, $sourceConnectionName, $destConnectionName, $encodeFrom, $encodeTo)
    {
        $totalTables = count($tables);
        $processed = 0;
        $source = $this->getConnection($sourceConnectionName);
        $destination = $this->getConnection($destConnectionName);

        $tableCopier = $this->tableCopier->paginated($source, $destination, $encodeFrom, $encodeTo);

        foreach ($tables as $table) {
            $tableCopier->copy($table)->run();
            $processed++;

            // Event
            $this->dispatcher->fire('update.table.copied', ['count' => $processed, 'total' => $totalTables]);

            // Logging and Debug
            $this->logger->info('Copied table '.$table->name);
            //if( config('app.debug') ) echo "\nMemory Usage: ".round(memory_get_usage(true)/1048576,2) . 'MB - '.$table->name;
        }
    }

    /**
     * Get requests from newest to oldest
     *  by numbers of days old.
     * @param $srcConnectionName
     * @param $destConnectionName
     * @param int $daysOld
     * @return array|static[]
     */
    public function getRequests($srcConnectionName, $destConnectionName, $daysOld = 90)
    {
        $srcConnection = $this->getConnection($srcConnectionName);
        $destConnection = $this->getConnection($destConnectionName);

        $query = $srcConnection->table('HS_Request')
            ->select('xRequest')
            ->orderBy('dtGMTOpened', 'desc'); // Important to note it's DESC order (newest first)

        // If it's not numeric, we'll assume we want
        // to convert all requests
        if (is_numeric($daysOld)) {
            // Yep, 'today midnight' works:
            // http://stackoverflow.com/questions/13129817/getting-a-timestamp-for-today-at-midnight
            $daysAgoTimestamp = strtotime('today midnight') - ($daysOld * 86400); // 86400 is one day in seconds

            $query->where('dtGMTOpened', '>', $daysAgoTimestamp);
        }

        /*
         * Check to see if we have already converted some requests
         * and start from that xRequest, so we don't convert duplicates
         */
        $lastRequestId = $destConnection->table('HS_Settings')
            ->where('sSetting', 'cHD_CONVERT_LAST_REQUEST_ID')
            ->first();

        if ($lastRequestId && $lastRequestId->tValue > 0) {
            // Only get older requests, which will be ones with a
            // smaller xRequest ID
            $query->where('xRequest', '<', $lastRequestId->tValue);
        }

        return $query->get();
    }

    /**
     * If a requests:convert command misses requests but not others, we
     * get "holes" of missing requests. This will attempt to find them
     * for a re-attempt. This requires some potentially heavy memory-usage.
     *
     * @param $srcConnectionName
     * @param $destConnectionName
     * @return array|static[]
     */
    public function getMissingRequests($srcConnectionName, $destConnectionName)
    {
        $srcConnection = $this->getConnection($srcConnectionName);
        $destConnection = $this->getConnection($destConnectionName);

        // Some DBs will have close to a million xRequest values per query hereM

        $allSourceRequestIds = $srcConnection->table('HS_Request')
            ->select('xRequest')
            ->get();

        $allDestRequestIds = $destConnection->table('HS_Request')
            ->select('xRequest')
            ->get();

        // Get xRequest inside of source database (v3)
        // that are not in destination database (v4)
        $requestIds = array_diff(Arr::pluck($allSourceRequestIds, 'xRequest'), Arr::pluck($allDestRequestIds, 'xRequest'));

        // Clear some memory (one hopes)
        unset($allSourceRequestIds);
        unset($allDestRequestIds);

        // SqlServer has bind limit of 2,100
        // MySQL has bind limit of 65,535 (?), based on blob max length
        // PGSql has bind limit of 34,464
        return $srcConnection->table('HS_Request')
            ->select('xRequest')
            ->whereIn('xRequest', $requestIds)
            ->orderBy('dtGMTOpened', 'desc')
            ->get();
    }

    /**
     * Add custom field columns to HS_Request table
     * based on custom fields in HS_CustomFields.
     * @param $srcConnectionName string Source DB Connection Name
     * @param $destConnectionName string Destination DB Connection Name
     */
    public function addRequestCustomFieldColumns($srcConnectionName, $destConnectionName)
    {
        $srcConnection = $this->getConnection($srcConnectionName);
        $connection = $this->getConnection($destConnectionName);
        $schema = $connection->getSchemaBuilder();

        $customFields = \HS\Domain\CustomFields\CustomField::all();

        foreach ($customFields as $customField) {
            if (! $schema->hasColumn('HS_Request', $fieldName = 'Custom'.$customField->xCustomField)) {
                try {
                    // Over-ride the text size on string fields so we can set the proper length
                    // based on the actual data in the columns.
                    if ($this->isStringType($customField->fieldType)) {
                        $customField->sTxtSize = $this->getColumnSize($srcConnection, $fieldName);
                    }
                    $schema->table('HS_Request', function ($table) use ($customField) {
                        $customField->addColumn($table);
                    });
                } catch (\Exception $e) {
                    $this->logger->warning('Caught exception while building custom fields: '.$e->getMessage());
                }
            }
        }
    }

    /**
     * Need a class which builds a request, using tables needed and knowing relationships. Probably not
     * something for the repository to do?
     * 1. Copy HS_Request
     * 2. Copy HS_Assignment Chain where xRequest = ?
     * 3. Copy HS_Request_ReportingTags where xRequest = ?
     * 4. Copy HS_Subscriptions where xRequest = ?
     * 5. Copy HS_Stats_Responses where xRequest = ?
     * 6. Copy HS_Request_Pushed where xRequest = ?
     * 7. Copy HS_Request_Note_Drafts where xRequest = ?
     * 8. Copy HS_Time_Tracker where xRequest = ?
     * 9. Copy HS_Request_Merged where xRequest = ?
     * 10. TABLES WITH DERIVATIVES
     *  -- Copy HS_Reminder where xRequest = ?
     *  -- -- Copy HS_Reminder_Person where xReminder = ?
     *  -- Copy HS_Request_History where xRequest = ?
     *  -- -- Copy HS_Documents where xDocumentId = ? (This is the weird spot).
     *
     * TRANSACTION!
     */
    public function buildRequests($requests, $tables, $sourceConnectionName, $destConnectionName, $encodeFrom, $encodeTo)
    {
        $source = $this->getConnection($sourceConnectionName);
        $destination = $this->getConnection($destConnectionName);

        $this->initConvertLastRequestIdSetting($destination);

        $tableCopier = $this->tableCopier->paginated($source, $destination, $encodeFrom, $encodeTo);

        $totalRequests = count($requests);
        $totalProcessed = 0;

        foreach ($requests as $request) {
            $xRequest = $request->xRequest;

            $destination->beginTransaction();

            try {
                // Record this attempt
                $destination->table('HS_Settings')
                    ->where('sSetting', 'cHD_CONVERT_LAST_REQUEST_ATTEMPT_ID')
                    ->update(['tValue' => $xRequest]);

                // Copy HS_Request
                $tableCopier->copy($tables['HS_Request'])
                    ->where('xRequest', $xRequest)
                    ->run();

                // Copy HS_Assignment_Chain
                $tableCopier->copy($tables['HS_Assignment_Chain'])
                    ->where('xRequest', $xRequest)
                    ->orderBy('HS_Assignment_Chain.xAssignmentChainId')
                    ->run();

                // HS_Request_ReportingTags
                $tableCopier->copy($tables['HS_Request_ReportingTags'])
                    ->where('xRequest', $xRequest)
                    ->orderBy('HS_Request_ReportingTags.xReportingTag')
                    ->run();

                // HS_Subscriptions
                $tableCopier->copy($tables['HS_Subscriptions'])
                    ->where('xRequest', $xRequest)
                    ->orderBy('xSubscriptions')
                    ->run();

                // HS_Stats_Responses
                $tableCopier->copy($tables['HS_Stats_Responses'])
                    ->where('xRequest', $xRequest)
                    ->orderBy('xEvent')
                    ->run();

                // HS_Request_Pushed
                $tableCopier->copy($tables['HS_Request_Pushed'])
                    ->where('xRequest', $xRequest)
                    ->orderBy('xPushed')
                    ->run();

                // HS_Request_Note_Drafts
                $tableCopier->copy($tables['HS_Request_Note_Drafts'])
                    ->where('xRequest', $xRequest)
                    ->run();

                // HS_Time_Tracker
                $tableCopier->copy($tables['HS_Time_Tracker'])
                    ->where('xRequest', $xRequest)
                    ->orderBy('xTimeId')
                    ->run();

                // HS_Request_Merged
                // Capture all merges (WHERE ... OR WHERE)
                $tableCopier->copy($tables['HS_Request_Merged'])
                    ->where('xRequest', $xRequest)
                    ->orWhere('xMergedRequest', $xRequest)
                    ->orderBy('xMergedRequest')
                    ->run();

                // HS_Reminder -- Has Derivative
                // Foreach HS_Reminder, copy HS_Reminder_Person where xReminder = ?
                $tableCopier->copy($tables['HS_Reminder'])
                    ->where('xRequest', $xRequest)
                    ->orderBy('xReminder')
                    ->run();

                // HS_Reminder_Person Derivative
                // Join statement only copies per request already added above
                $tableCopier->copy($tables['HS_Reminder_Person'])
                    ->select('HS_Reminder_Person.*')
                    ->join('HS_Reminder', 'HS_Reminder.xReminder', '=', 'HS_Reminder_Person.xReminder')
                    ->where('HS_Reminder.xRequest', $xRequest)
                    ->orderBy('HS_Reminder_Person.xReminder')
                    ->run();

                // HS_Request_History -- THE BIG ONE
                // Foreach HS_Request_History, if xDocumentId != 0, copy HS_Documents where xDocumentId = ?
                // Foreach HS_Documents, copy HS_Document_Location where xDocumentId = ?
                $tableCopier->copy($tables['HS_Request_History'])
                    ->where('xRequest', $xRequest)
                    ->orderBy('HS_Request_History.xRequestHistory')
                    ->run();

                // HS_Documents Derivative
                // Join statement only copies documents if there's an assigned document ID per request already added above
                // This references HS_Documents_Location which only exists in HelpSpot < 4.0.0
                $tableCopier->copy($tables['HS_Documents'])
                    ->select(['HS_Documents.*', 'HS_Documents_Location.sFileLocation'])
                    ->join('HS_Request_History', 'HS_Request_History.xDocumentId', '=', 'HS_Documents.xDocumentId')
                    ->join('HS_Documents_Location', 'HS_Documents.xDocumentId', '=', 'HS_Documents_Location.xDocumentId', 'left outer')
                    ->where('HS_Request_History.xDocumentId', '<>', 0)
                    ->where('HS_Request_History.xRequest', $xRequest)
                    ->orderBy('HS_Documents.xDocumentId')
                    ->run();

                // Record last saved
                $destination->table('HS_Settings')
                    ->where('sSetting', 'cHD_CONVERT_LAST_REQUEST_ID')
                    ->update(['tValue' => $xRequest]);

                $destination->commit();

                $totalProcessed++;

                // Event
                $this->dispatcher->fire('update.request.copied', ['count' => $totalProcessed, 'total' => $totalRequests]);
            } catch (\Exception $e) {
                $destination->rollBack();

                if (config('app.debug')) {
                    throw $e;
                } else {
                    // Just log it and move on if config('app.debug') is off
                    $this->logger->error($e);
                }
            }
        }

        //if( config('app.debug') ) echo "\nRequests Copied: ".$totalProcessed;
    }

    /**
     * Check if cHD_CONVERT_LAST_REQUEST_ID setting,
     * exists, and create/set it to zero if not.
     * @param \Illuminate\Database\ConnectionInterface $connection
     */
    protected function initConvertLastRequestIdSetting($connection)
    {
        $lastRequestId = $connection->table('HS_Settings')
            ->where('sSetting', 'cHD_CONVERT_LAST_REQUEST_ID')
            ->first();

        if (! $lastRequestId) {
            $connection->table('HS_Settings')
                ->insert(['sSetting' => 'cHD_CONVERT_LAST_REQUEST_ID', 'tValue' => '0']);
        }

        $lastAttemptedRequestId = $connection->table('HS_Settings')
            ->where('sSetting', 'cHD_CONVERT_LAST_REQUEST_ATTEMPT_ID')
            ->first();

        if (! $lastAttemptedRequestId) {
            $connection->table('HS_Settings')
                ->insert(['sSetting' => 'cHD_CONVERT_LAST_REQUEST_ATTEMPT_ID', 'tValue' => '0']);
        }
    }

    /**
     * Get defined database connection.
     * @param string $connection
     * @return \Illuminate\Database\Connection
     */
    public function getConnection($connection = null)
    {
        return $this->db->connection($connection);
    }

    /**
     * @param $connection
     * @param $column
     * @return int
     */
    public function getColumnSize($connection, $column)
    {
        switch ($connection->getDriverName()) {
            case 'mysql':
                $result = $connection->table('HS_Request')
                    ->select($connection->raw('max(LENGTH('.$column.')) as length'))
                    ->first();

                $size = $result->length;

                break;
            case 'sqlsrv':
                $result = $connection->table('HS_Request')
                    ->select($connection->raw('max(len('.$column.')) as length'))
                    ->first();

                $size = $result->length;

                break;
            default:
                // For all other databases like postgres.
                return 255;
        }

        // Return a max of 255 anything else should be the current
        // max size with an extra ten character buffer.
        return ($size > 0 and $size < 245) ? $size + 10 : 255;
    }

    /**
     * @param $fieldType
     * @return bool
     */
    protected function isStringType($fieldType)
    {
        return in_array($fieldType, [
           'ajax', 'drilldown', 'regex', 'select', 'text',
        ]);
    }

    /**
     * Get database size.
     * @throws \InvalidArgumentException
     */
    public function getDatabaseSize($connection)
    {
        $connection = $this->getConnection($connection);

        $database = $connection->getDatabaseName();

        switch ($connection->getDriverName()) {
            case 'mysql':
                $result = $connection->select($connection->raw("SELECT table_schema 'database',
                    sum( data_length + index_length ) / 1024 / 1024 'size_mb'
                    FROM information_schema.TABLES
                    WHERE table_schema = :database
                    GROUP BY table_schema;"),
                ['database' => $database]);

                $size = $result[0]->size_mb;

                break;
            case 'sqlsrv':
                // todo: Windows Azure customers may not have access to sys.master_files
                $result = $connection->select($connection->raw("SELECT database_name = DB_NAME(database_id)
                    , log_size_mb = CAST(SUM(CASE WHEN type_desc = 'LOG' THEN size END) * 8. / 1024 AS DECIMAL(8,2))
                    , row_size_mb = CAST(SUM(CASE WHEN type_desc = 'ROWS' THEN size END) * 8. / 1024 AS DECIMAL(8,2))
                    , total_size_mb = CAST(SUM(size) * 8. / 1024 AS DECIMAL(8,2))
                    FROM sys.master_files WITH(NOWAIT)
                    WHERE database_id = DB_ID(:database)
                    GROUP BY database_id;"),
                ['database' => $database]);

                if (! $result) {
                    // TODO: Use sys.database_files as per http://stackoverflow.com/questions/4174520/sql-server-sys-master-files-vs-sys-database-files
                    $size = '[ Unable to retrieve database size. Grant connecting user the "View any definition" securable to get database size. ]';

                    break;
                }

                $size = $result[0]->total_size_mb;

                break;
            default:
                throw new \InvalidArgumentException('Illegal database connection type given');
        }

        return $size;
    }
}

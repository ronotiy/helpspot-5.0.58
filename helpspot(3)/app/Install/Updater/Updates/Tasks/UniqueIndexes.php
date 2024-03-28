<?php

namespace HS\Install\Updater\Updates\Tasks;

use DB;
use Schema;
use Illuminate\Database\Schema\Blueprint;
use HS\Install\Updater\Updates\BaseUpdate;

class UniqueIndexes extends BaseUpdate
{
    protected $version = '4.0.5';

    protected $uniqueIndexes = [
        'HS_Bayesian_Corpus'            => [
            'index'     => 'hs_bayesian_corpus_sword_xcategory_unique',
            'columns'   => ['sWord', 'xCategory'],
        ],
        'HS_Portal_Bayesian_Corpus'     => [
            'index'     => 'hs_portal_bayesian_corpus_sword_xcategory_unique',
            'columns'   => ['sWord', 'xCategory'],
        ],
        'HS_Person_Status'              => [
            'index'     => 'hs_person_status_xpersonstatus_unique',
            'columns'   => 'xPersonStatus',
        ],
        'HS_Portal_Login'               => [
            'index'     => 'hs_portal_login_semail_unique',
            'columns'   => 'sEmail',
        ],
        'HS_Request_Merged'             => [
            'index'     => 'hs_request_merged_xmergedrequest_unique',
            'columns'   => 'xMergedRequest',
        ],
        'HS_Settings'                   => [
            'index'     => 'hs_settings_ssetting_unique',
            'columns'   => 'sSetting',
        ],
    ];

    /**
     * Add Unique indexes if not exists
     *   for beta customers who may have installed HelpSpot
     *   before the migrations created the unique indexes.
     */
    public function run()
    {
        foreach ($this->uniqueIndexes as $table => $indexData) {
            // If a unique index does NOT exist, create it
            if (! $this->indexExists($table, $indexData['index'])) {
                // Remove Bayesian duplicates
                $this->deDup();

                Schema::table($table, function (Blueprint $table) use ($indexData) {
                    $table->unique($indexData['columns']);
                });
            }
        }
    }

    protected function indexExists($table, $index)
    {
        $indexes = DB::select(DB::raw($this->indexQuery($table, $index)));

        if (count($indexes) > 0) {
            return true;
        }

        return false;
    }

    protected function deDup()
    {
        switch (config('database.default')) {
            case 'mssql':
            case 'sqlsrv':
                DB::statement(';WITH x AS
(
  SELECT sWord, xCategory, rn = ROW_NUMBER() OVER
      (PARTITION BY sWord, xCategory ORDER BY sWord)
  FROM dbo.HS_Bayesian_Corpus
)
DELETE x WHERE rn > 1;
');
                DB::statement(';WITH x AS
(
  SELECT sWord, xCategory, rn = ROW_NUMBER() OVER
      (PARTITION BY sWord, xCategory ORDER BY sWord)
  FROM dbo.HS_Portal_Bayesian_Corpus
)
DELETE x WHERE rn > 1;
');

                break;
            case 'mysql':
            case 'mysqli':
            default:
            DB::statement('delete HS_Bayesian_Corpus from HS_Bayesian_Corpus inner join
    (
      select sWord, xCategory
      from HS_Bayesian_Corpus
      group by sWord, xCategory
      having count(1) > 1
    ) as duplicates
on
    (
      duplicates.sWord =   HS_Bayesian_Corpus.sWord and
      duplicates.xCategory =   HS_Bayesian_Corpus.xCategory
    )');

            DB::statement('delete HS_Portal_Bayesian_Corpus from HS_Portal_Bayesian_Corpus inner join
    (
      select sWord, xCategory
      from HS_Portal_Bayesian_Corpus
      group by sWord, xCategory
      having count(1) > 1
    ) as duplicates
on
    (
      duplicates.sWord =   HS_Portal_Bayesian_Corpus.sWord and
      duplicates.xCategory =   HS_Portal_Bayesian_Corpus.xCategory
    )');

            DB::statement('delete HS_Portal_Login from HS_Portal_Login inner join
    (
      select sEmail
      from HS_Portal_Login
      group by sEmail
      having count(1) > 1
    ) as duplicates
on
    (
      duplicates.sEmail = HS_Portal_Login.sEmail
    )');

                break;
        }
    }

    protected function indexQuery($table, $index)
    {
        switch (config('database.default')) {
            case 'mssql':
            case 'sqlsrv':
                return "SELECT name FROM sys.indexes WHERE name = '".$index."' and object_id = OBJECT_ID('[".defaultConnectionDetail('database').'].[dbo].['.$table."]')";

                break;
            case 'mysql':
            case 'mysqli':
            default:
                return 'SHOW INDEX FROM '.$table." WHERE Key_name = '".$index."'";

                break;
        }
    }
}

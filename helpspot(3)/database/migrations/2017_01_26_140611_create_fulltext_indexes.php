<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFulltextIndexes extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('database.default') == 'mysql') {
            DB::statement('ALTER TABLE HS_Forums_Posts ADD FULLTEXT INDEX ft_forum_post (tPost)');
            DB::statement('ALTER TABLE HS_KB_Pages ADD FULLTEXT INDEX ft_kb_page (sPageName, tPage)');
            DB::statement('ALTER TABLE HS_Request_History ADD FULLTEXT INDEX ft_request_history (tNote)');
            DB::statement('ALTER TABLE HS_Responses ADD FULLTEXT INDEX ft_responses (sResponseTitle, tResponse)');

            $indexCount = DB::select(DB::raw("SELECT count(*) as NUM_INDEX FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '".defaultConnectionDetail('database')."' and TABLE_NAME = 'HS_Request'"))[0]->NUM_INDEX;
            if ($indexCount < 64) {
                DB::statement('ALTER TABLE HS_Request ADD FULLTEXT INDEX ft_request (sTitle)');
            } else {
                $indexName = DB::select(DB::raw("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '".defaultConnectionDetail('database')."' and TABLE_NAME = 'HS_Request' and INDEX_NAME like '%custom%' order by Cardinality asc limit 1"))[0]->INDEX_NAME;
                DB::statement('DROP INDEX '.$indexName.' on HS_Request');
                DB::statement('ALTER TABLE HS_Request ADD FULLTEXT INDEX ft_request (sTitle)');
            }
        }

        if (config('database.default') == 'sqlsrv') {
            // Create FT index catalog
            DB::statement('CREATE FULLTEXT CATALOG HelpSpot_FT');

            // Find PK index names
            $forumIndex = DB::select(DB::raw("sp_pkeys @table_name='HS_Forums_Posts'"))[0]->PK_NAME;
            $kbIndex = DB::select(DB::raw("sp_pkeys @table_name='HS_KB_Pages'"))[0]->PK_NAME;
            $requestIndex = DB::select(DB::raw("sp_pkeys @table_name='HS_Request'"))[0]->PK_NAME;
            $historyIndex = DB::select(DB::raw("sp_pkeys @table_name='HS_Request_History'"))[0]->PK_NAME;
            $responseIndex = DB::select(DB::raw("sp_pkeys @table_name='HS_Responses'"))[0]->PK_NAME;

            // Add Fulltext index to catalog for each table
            DB::statement('CREATE FULLTEXT INDEX ON HS_Forums_Posts(tPost) KEY INDEX '.$forumIndex.' ON HelpSpot_FT WITH CHANGE_TRACKING AUTO;');
            DB::statement('CREATE FULLTEXT INDEX ON HS_KB_Pages(sPageName, tPage) KEY INDEX '.$kbIndex.' ON HelpSpot_FT WITH CHANGE_TRACKING AUTO;');
            DB::statement('CREATE FULLTEXT INDEX ON HS_Request(sTitle) KEY INDEX '.$requestIndex.' ON HelpSpot_FT WITH CHANGE_TRACKING AUTO;');
            DB::statement('CREATE FULLTEXT INDEX ON HS_Request_History(tNote) KEY INDEX '.$historyIndex.' ON HelpSpot_FT WITH CHANGE_TRACKING AUTO;');
            DB::statement('CREATE FULLTEXT INDEX ON HS_Responses(sResponseTitle, tResponse) KEY INDEX '.$responseIndex.' ON HelpSpot_FT WITH CHANGE_TRACKING AUTO;');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (config('database.default') == 'mysql') {
            DB::statement('ALTER TABLE HS_Forums_Posts DROP INDEX ft_forum_post;');
            DB::statement('ALTER TABLE HS_KB_Pages DROP INDEX ft_kb_page');
            DB::statement('ALTER TABLE HS_Request_History DROP INDEX ft_request_history');
            DB::statement('ALTER TABLE HS_Responses DROP INDEX ft_responses');
        }

        if (config('database.default') == 'sqlsrv') {
            DB::statement('DROP FULLTEXT INDEX ON HS_Forums_Posts');
            DB::statement('DROP FULLTEXT INDEX ON HS_KB_Pages');
            DB::statement('DROP FULLTEXT INDEX ON HS_Request_History');
            DB::statement('DROP FULLTEXT INDEX ON HS_Responses');
        }
    }
}

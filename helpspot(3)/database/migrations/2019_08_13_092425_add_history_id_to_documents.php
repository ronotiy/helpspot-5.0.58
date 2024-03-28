<?php

use HS\Domain\Workspace\Document;
use HS\Domain\Workspace\History;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHistoryIdToDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HS_Documents', function (Blueprint $table) {
            $table->integer('xRequestHistory')->nullable();
            $table->integer('xResponse')->nullable();
            $table->index('xRequestHistory');
        });

        $this->migrateHistoryDocuments();
        $this->migrateResponses();
        $this->dropDocumentIdColumnFromHistory();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HS_Documents', function (Blueprint $table) {
            $table->dropColumn('xRequestHistory');
            $table->dropColumn('xResponse');
        });
    }

    /**
     * Loop through all the request history and
     * update the Document with the parent history id.
     */
    protected function migrateHistoryDocuments()
    {
        // Reassign document request history rows to their related document
        DB::table('HS_Request_History')->where('xDocumentId', '>', 0)
            ->orderBy('xRequestHistory', 'desc')
            ->chunk(1000, function($historyDocuments){
                $orphans = [];
                foreach ($historyDocuments as $historyDocumentRow) {
                    $parent = History::where('xDocumentId', '=', 0)
                        ->where('dtGMTChange', '=', $historyDocumentRow->dtGMTChange)
                        ->where('xRequest', $historyDocumentRow->xRequest)
                        ->whereRaw("((tLog = '' OR tLog LIKE '%ccstaff%') OR tEmailHeaders <> '')")
                        ->orderBy('dtGMTChange', 'asc')
                        ->first();

                    if ($parent) {
                        Document::where('xDocumentId', $historyDocumentRow->xDocumentId)
                            ->update(['xRequestHistory' => $parent->getKey()]);
                    } else {
                        $orphans[] = $historyDocumentRow->xRequestHistory;
                    }
                }

                if (count($orphans)) {
                    Log::debug("Orphaned request history rows", ['orphans' => $orphans]);
                }
            });

        // Delete all documents request history rows in chunks
        $requestHistoryDocCount = DB::table('HS_Request_History')
            ->where('xDocumentId', '>', 0)
            ->count();

        if (is_numeric($requestHistoryDocCount) && $requestHistoryDocCount > 0) {
            $chunks = ceil($requestHistoryDocCount / 1000);
            while($chunks >= 0) {
                DB::table('HS_Request_History')
                    ->where('xDocumentId', '>', 0)
                    ->orderBy('xRequestHistory', 'desc')
                    ->limit(1000)
                    ->delete();
                $chunks--;
                usleep(250000); // .25 seconds to allow the binlog to catch up
            }
        }
    }

    /**
     * Loop through all the responses and
     * create the relationship with documents
     */
    protected function migrateResponses()
    {
        $responses = DB::table('HS_Responses')->get();
        foreach ($responses as $response) {
            $options = json_decode($response->tResponseOptions, true);
            if (isset($options['attachment']) && is_array($options['attachment'])) {
                foreach ($options['attachment'] as $attachId) {
                    $doc = Document::find($attachId);

                    if ($doc) {
                        $doc->xResponse = $response->xResponse;
                        $doc->save();
                    }
                }
            }
        }
    }

    /**
     * Drop the unused HS_Request_History.xDocumentId column
     */
    protected function dropDocumentIdColumnFromHistory()
    {
        if (config('database.default') == 'sqlsrv') {
            \HS\Database\SqlSrv::drop('HS_Request_History', 'xDocumentId');
        } else {
            Schema::table('HS_Request_History', function (Blueprint $table) {
                $table->dropColumn('xDocumentId');
            });
        }
    }
}

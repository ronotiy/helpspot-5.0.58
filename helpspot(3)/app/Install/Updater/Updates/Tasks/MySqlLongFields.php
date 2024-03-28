<?php

namespace HS\Install\Updater\Updates\Tasks;

use DB;
use HS\Install\Updater\Updates\BaseUpdate;

class MySqlLongFields extends BaseUpdate
{
    protected $version = '4.0.3';

    /**
     * IF ( MySQL )
     *  - Check if HS_Settings.tValue = "longtext" over just "text"
     *  -- Maria/Percona too?
     *  - Check if HS_Documents.blobFile is "longblob" over "blob".
     *
     *   IF( not 'long' )
     *    - ALTER table statements for longblob and longtext
     *
     * This should, in theory, *only* get run for Beta testers who
     * installed HelpSpot before version 4.0.3.
     */
    public function run()
    {
        if (config('database.default') === 'mysql') {
            $settingsValueType = $this->getValueColumnType();
            $documentsBlobType = $this->getBlobColumnType();

            if ($settingsValueType !== 'longtext' || $documentsBlobType !== 'longblob') {
                $this->alterTables();
            }
        }
    }

    /**
     * Get column type of HS_Settings.tValue.
     * @return string|bool
     */
    protected function getValueColumnType()
    {
        $settingsTable = DB::select(DB::raw('DESCRIBE HS_Settings'));

        foreach ($settingsTable as $table) {
            if ($table->Field === 'tValue') {
                return $table->Type;
            }
        }

        return false;
    }

    /**
     * Get column type of HS_Documents.blobFile.
     * @return string|bool
     */
    protected function getBlobColumnType()
    {
        $documentsTable = DB::select(DB::raw('DESCRIBE HS_Documents'));

        foreach ($documentsTable as $table) {
            if ($table->Field === 'blobFile') {
                return $table->Type;
            }
        }

        return false;
    }

    protected function alterTables()
    {
        // Blob Fields
        DB::statement('ALTER TABLE `HS_Documents`           CHANGE `blobFile`            `blobFile`            LONGBLOB  NULL;');
        DB::statement('ALTER TABLE `HS_KB_Documents`        CHANGE `blobFile`            `blobFile`            LONGBLOB  NULL;');
        DB::statement('ALTER TABLE `HS_Person_Photos`       CHANGE `blobPhoto`           `blobPhoto`           LONGBLOB  NULL;');

        // Text Fields
        DB::statement('ALTER TABLE `HS_Automation_Rules`    CHANGE `tRuleDef`            `tRuleDef`            LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Category`            CHANGE `sPersonList`         `sPersonList`         LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Category`            CHANGE `sCustomFieldList`    `sCustomFieldList`    LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_CustomFields`        CHANGE `listItems`           `listItems`           LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Filters`             CHANGE `tFilterDef`          `tFilterDef`          LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Forums`              CHANGE `tModerators`         `tModerators`         LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Forums_Posts`        CHANGE `tPost`               `tPost`               LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_KB_Books`            CHANGE `tDescription`        `tDescription`        LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_KB_Books`            CHANGE `tEditors`            `tEditors`            LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_KB_Pages`            CHANGE `tPage`               `tPage`               LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Mail_Rules`          CHANGE `tRuleDef`            `tRuleDef`            LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Mailboxes`           CHANGE `tAutoResponse`       `tAutoResponse`       LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Mailboxes`           CHANGE `tAutoResponse_html`  `tAutoResponse_html`  LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Mailboxes`           CHANGE `sSMTPSettings`       `sSMTPSettings`       LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Multi_Portal`        CHANGE `tPortalMsg`          `tPortalMsg`          LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Multi_Portal`        CHANGE `tDisplayKBs`         `tDisplayKBs`         LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Multi_Portal`        CHANGE `tDisplayForums`      `tDisplayForums`      LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Multi_Portal`        CHANGE `tDisplayCategories`  `tDisplayCategories`  LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Multi_Portal`        CHANGE `tDisplayCfs`         `tDisplayCfs`         LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Multi_Portal`        CHANGE `tHistoryMailboxes`   `tHistoryMailboxes`   LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Person`              CHANGE `tSignature`          `tSignature`          LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Person`              CHANGE `tSignature_HTML`     `tSignature_HTML`     LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Person`              CHANGE `tWorkspace`          `tWorkspace`          LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Reminder`            CHANGE `tReminder`           `tReminder`           LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Request_History`     CHANGE `tLog`                `tLog`                LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Request_History`     CHANGE `tNote`               `tNote`               LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Request_History`     CHANGE `tEmailHeaders`       `tEmailHeaders`       LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Request_Note_Drafts` CHANGE `tNote`               `tNote`               LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Request_Pushed`      CHANGE `tComment`            `tComment`            LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Responses`           CHANGE `tResponse`           `tResponse`           LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Responses`           CHANGE `tResponseOptions`    `tResponseOptions`    LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Saved_Reports`       CHANGE `tData`               `tData`               LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Sessions2`           CHANGE `sessdata`            `sessdata`            LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Settings`            CHANGE `tValue`              `tValue`              LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Time_Tracker`        CHANGE `tDescription`        `tDescription`        LONGTEXT  NULL;');
        DB::statement('ALTER TABLE `HS_Triggers`            CHANGE `tTriggerDef`         `tTriggerDef`         LONGTEXT  NULL;');
    }
}

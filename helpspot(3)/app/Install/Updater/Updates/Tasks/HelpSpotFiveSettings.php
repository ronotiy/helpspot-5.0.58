<?php

namespace HS\Install\Updater\Updates\Tasks;

use HS\Install\Updater\Updates\BaseUpdate;

class HelpSpotFiveSettings extends BaseUpdate
{
    protected $version = '5.0.0';

    public function run()
    {
        // Delete any unused settings
        $this->db->table('HS_Settings')->where('sSetting', 'cHD_SESSION_TIMEOUT')->delete();
        $this->db->table('HS_Settings')->where('sSetting', 'cHD_HTMLEMAILS_WYSIWYG')->delete();
        $this->db->table('HS_Settings')->where('sSetting', 'cHD_HTMLEMAILS_WYSIWYG_CSS')->delete();

        $samlOptions = $this->db->table('HS_Settings')
            ->where('sSetting', 'cAUTHTYPE_SAML_OPTIONS')
            ->first();

        if (! $samlOptions) {
            $this->db->table('HS_Settings')
                ->insert([
                    'sSetting' => 'cAUTHTYPE_SAML_OPTIONS',
                    'tValue' => '',
                ]);
        }

        $adminJsCss = $this->db->table('HS_Settings')
            ->where('sSetting', 'cHD_ADMIN_JS')
            ->first();

        if (! $adminJsCss) {
            $this->db->table('HS_Settings')
                ->insert([
                    'sSetting' => 'cHD_ADMIN_JS',
                    'tValue' => '',
                ]);
            $this->db->table('HS_Settings')
                ->insert([
                    'sSetting' => 'cHD_ADMIN_CSS',
                    'tValue' => '',
                ]);
        }

        // Update default attachment storage path
        $attachments = $this->db->table('HS_Settings')
            ->where('sSetting', 'cHD_ATTACHMENT_LOCATION_PATH')
            ->first();

        if ($attachments) {
            if(strpos($attachments->tValue, 'data/documents') !== false || strpos($attachments->tValue, 'data\\documents') !== false) {
                $this->db->table('HS_Settings')
                    ->where('sSetting', 'cHD_ATTACHMENT_LOCATION_PATH')
                    ->update(['tValue' => storage_path('documents')]);
            }
        }

        // Finally clean out any orphaned Tags
        // https://github.com/UserScape/helpspot5/issues/54
        $this->clearOrphanedTags();
    }

    public function clearOrphanedTags()
    {
        $tags = \DB::table('HS_Tags_Map')->whereRaw('xPage <> 0 AND xPage NOT IN( SELECT pg.xPage FROM HS_KB_Pages pg)')->get();
        foreach ($tags as $tag) {
            \DB::table('HS_Tags_Map')->where('xTagMap', $tag->xTagMap)->delete();
        }
    }
}

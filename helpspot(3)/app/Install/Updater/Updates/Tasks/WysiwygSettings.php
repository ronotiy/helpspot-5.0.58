<?php

namespace HS\Install\Updater\Updates\Tasks;

use Facades\HS\Cache\Manager;

use HS\Install\Updater\Updates\BaseUpdate;

class WysiwygSettings extends BaseUpdate
{
    protected $version = '4.1.0';

    public function run()
    {
        $this->db->table('HS_Settings')
            ->where('sSetting', 'cHD_HTMLEMAILS_WYSIWYG')
            ->update(['tValue' => serialize(['"undo", "insert", "style", "emphasis", "align", "listindent", "format", "tools"'])]);
        Manager::forget(Manager::key('CACHE_SETTINGS_KEY'));
    }
}

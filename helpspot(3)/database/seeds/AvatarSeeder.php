<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AvatarSeeder extends Seeder
{
    public function run()
    {
        $user_series = [
            'animals' => 'static/img/avatars/animals/',
            'robots' => 'static/img/avatars/robots/',
            'generic' => 'static/img/avatars/generic/',
            'monsters' => 'static/img/avatars/monsters/',
            'nature' => 'static/img/avatars/nature/',
            'classic' => 'static/img/avatars/classic/', //not used in install version
        ];

        foreach ($user_series as $key => $dir) {
            if (is_dir($dir) && $dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file[0] != '.') {
                        $mimeType = ($key == 'classic') ? 'image/gif' : 'image/png';

                        DB::table('HS_Person_Photos')->insert([
                            'xPerson' => 0,
                            'sFilename' => $file,
                            'sFileMimeType' => $mimeType,
                            'sSeries' => $key,
                        ]);
                    }
                }
                closedir($dh);
            }
        }
    }
}

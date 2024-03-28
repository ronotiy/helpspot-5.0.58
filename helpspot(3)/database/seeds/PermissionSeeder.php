<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissionGroups = [
            'Administrator'             =>  [1, 1, 1, 1, 1, 1, 0, 0, 1, 1, 1, 1, 1],
            'Help Desk Staff'           =>  [0, 1, 1, 1, 1, 1, 0, 0, 1, 1, 0, 0, 0],
            'Level 2'                   =>  [0, 1, 1, 0, 0, 1, 0, 0, 1, 0, 0, 0, 0],
            'Guest'                     =>  [0, 1, 1, 0, 0, 1, 1, 0, 1, 0, 0, 0, 0],
            'Level 2 (Limited Access)'  =>  [0, 0, 1, 0, 0, 1, 0, 1, 0, 0, 0, 0, 0],
            'Guest (Limited Access)'    =>  [0, 0, 1, 0, 0, 1, 1, 1, 0, 0, 0, 0, 0],
        ];

        // count($columns) must === count($permissionGroups[0...n])
        // Ensure this is not an associated array, as function below
        // depends on $numericalKey in the `foreach $numericalKey => $value`
        // loop to be an integer
        $columns = [
            //'sGroup', // This column taken care of below foreach loop
            'fModuleReports',
            'fModuleKbPriv',
            'fModuleForumsPriv',
            'fViewInbox',
            'fCanBatchRespond',
            'fCanMerge',
            'fCanViewOwnReqsOnly',
            'fLimitedToAssignedCats',
            'fCanAdvancedSearch',
            'fCanManageSpam',
            'fCanManageTrash',
            'fCanManageKB',
            'fCanManageForum',
        ];

        // For each permission group,
        // build the insert array( columnName => columnValue, ... )
        foreach ($permissionGroups as $groupName => $groups) {
            $insertArray = [];
            $insertArray['sGroup'] = $groupName;

            foreach ($columns as $numericalKey => $column) {
                // $numericalKey corresponds to the key of each
                // $permissionsGroup array
                $insertArray[$column] = $groups[$numericalKey];
            }

            // Then insert it
            DB::table('HS_Permission_Groups')->insert($insertArray);
        }
    }
}

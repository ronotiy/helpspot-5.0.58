<?php

namespace HS\Domain\CustomFields;

use DB;
use Log;
use HS\Domain\Workspace\Category;
use HS\Vendor\Illuminate\Database\Blueprint;

class CreateDeleteCustomField
{
    public function __construct()
    {
        $this->schema = DB::getSchemaBuilder();
        $this->schema->blueprintResolver(function ($table, $callback) {
            return new Blueprint($table, $callback);
        });
    }

    public function create(array $fieldData, $type, $categoryList = [])
    {
        $customFieldClassname = '\\HS\Domain\\CustomFields\\'.ucfirst($type).'Field';
        $customField = new $customFieldClassname;
        $customField->fieldName = $fieldData['fieldName'];
        $customField->isRequired = $fieldData['isRequired'];
        $customField->isPublic = $fieldData['isPublic'];
        $customField->isAlwaysVisible = $fieldData['isAlwaysVisible'];
        $customField->listItems = $fieldData['sListItems'];
        $customField->listItemsColors = $fieldData['sListItemsColors'];
        $customField->sTxtSize = $fieldData['sTxtSize'];
        $customField->lrgTextRows = $fieldData['lrgTextRows'];
        $customField->iDecimalPlaces = $fieldData['iDecimalPlaces'];
        $customField->sRegex = $fieldData['sRegex'];
        $customField->sAjaxUrl = $fieldData['sAjaxUrl'];
        $customField->fieldType = $type;
        $customField->iOrder = 0;

        DB::beginTransaction();

        try {
            // Create a new custom field
            $customField->save();

            // Update categories with custom fields
            // Does the field list need ordering?
            $categories = Category::where('fDeleted', 0)->get();

            foreach ($categories as $category) {
                $sCustomFieldList = $category->sCustomFieldList;

                if (in_array($category->xCategory, $categoryList)
                    && ! in_array($customField->xCustomField, $sCustomFieldList)) {
                    $sCustomFieldList[] = $customField->xCustomField;
                    $category->sCustomFieldList = $sCustomFieldList;

                    $category->save();
                }
            }
        } catch (\Exception $e) {
            DB::rollback();

            Log::error($e);
            errorLog('Error saving to HS_CustomFields: '.$e->getMessage(), 'Custom Field', __FILE__, __LINE__);

            // This should get caught upstream
            // during CommandBus processing
            throw($e);
        }

        DB::commit();

        /* Create a new custom field column and index it
         * NOTE: ALTER statements trigger an implicit commit, so we'll handle this
         * a bit manually if altering the column fails
         */
        try {
            $this->schema->table('HS_Request', function ($table) use ($customField) {
                // addColumn added via traits
                $customField->addColumn($table);
            });
        } catch (\Exception $e) {
            Log::error($e);
            errorLog('Error adding custom field column: '.$e->getMessage(), 'Custom Field', __FILE__, __LINE__);

            if (strpos(utf8_strtolower($e->getMessage()), 'too many keys') !== false) {
                // Too many keys error will be ignored
                // For customers with out 64 keys using MySQL ¯\_(ツ)_/¯
                return;
            }

            // Remove custom field from category fieldlists
            foreach ($categories as $category) {
                $sCustomFieldList = $category->sCustomFieldList;
                $arrayKey = array_search($customField->xCustomField, $sCustomFieldList);

                if ($arrayKey !== false) {
                    unset($sCustomFieldList[$arrayKey]); // Remove from custom field list
                    $category->sCustomFieldList = $sCustomFieldList;
                    $category->save(); // We don't have code to cover this throwing an exception
                }
            }

            // Delete custom field
            $customField->delete();

            // This should get caught upstream
            // during CommandBus processing
            throw($e);
        }
    }

    public function delete($id)
    {
        // Find custom field & categories
        /** @var CustomField $customField */
        $customField = CustomField::find($id);
        $categories = Category::where('fDeleted', 0)->get();

        /*
         * Start by dropping the column
         * We'll let this throw an exception if it fails, thus
         * halting execution (the error will be logged to file)
         * Note: All alter statements implicitly auto-commit
         *       so we do not run them in a transaction explicitly
         */
        // Delete relations if sqlsrv
        if (config('database.default') == 'sqlsrv') {
            $this->sqlsrvDropRelations($id);
        }

        $this->schema->table('HS_Request', function ($table) use ($customField) {
            $customField->dropColumn($table);
        });

        /*
         * Then we can delete the custom field and handle categories
         * We'll do this in a transaction.
         */
        DB::transaction(function () use ($customField, $categories) {
            $customField->delete();

            // Remove custom field from category fieldlists
            foreach ($categories as $category) {
                $sCustomFieldList = $category->sCustomFieldList;
                $arrayKey = array_search($customField->xCustomField, $sCustomFieldList);

                if ($arrayKey !== false) {
                    unset($sCustomFieldList[$arrayKey]); // Remove from custom field list
                    $category->sCustomFieldList = $sCustomFieldList;
                    $category->save();
                }
            }
        });
    }

    protected function sqlsrvDropRelations($id)
    {
        //INDEX AND CONSTRAINT MUST BE DROPPED BEFORE COLUMN
        //Get constraint name
        $constraint = $GLOBALS['DB']->GetOne("
            SELECT name
            FROM sys.default_constraints
            WHERE parent_object_id = object_id('HS_Request')
            AND type = 'D'
            AND parent_column_id = (
                SELECT column_id
                FROM sys.columns
                WHERE object_id = object_id('HS_Request')
                AND name = 'Custom".$id."'
            )");

        //Delete constraint
        if ($constraint) {
            $GLOBALS['DB']->Execute('ALTER TABLE HS_Request DROP CONSTRAINT '.$constraint);
        }

        //Drop index, if exists
        $cfIndex = $GLOBALS['DB']->GetOne("SELECT i.name AS ind_name, C.name AS col_name, USER_NAME(O.uid) AS Owner, c.colid, k.Keyno,
            CASE WHEN I.indid BETWEEN 1 AND 254 AND (I.status & 2048 = 2048 OR I.Status = 16402 AND O.XType = 'V') THEN 1 ELSE 0 END AS IsPK,
            CASE WHEN I.status & 2 = 2 THEN 1 ELSE 0 END AS IsUnique
            FROM dbo.sysobjects o INNER JOIN dbo.sysindexes I ON o.id = i.id
            INNER JOIN dbo.sysindexkeys K ON I.id = K.id AND I.Indid = K.Indid
            INNER JOIN dbo.syscolumns c ON K.id = C.id AND K.colid = C.Colid
            WHERE LEFT(i.name, 8) <> '_WA_Sys_' AND o.status >= 0 AND O.Name LIKE 'HS_Request' AND C.name = 'Custom".$id."'
            ORDER BY O.name, I.Name, K.keyno");

        if ($cfIndex) {
            $GLOBALS['DB']->Execute('DROP INDEX HS_Request.'.$cfIndex);
        }
    }
}

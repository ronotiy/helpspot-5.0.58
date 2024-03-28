<?php

namespace HS\Database;

use Illuminate\Support\Facades\DB;

class SqlSrv
{
    /**
     * @param $table
     * @param $column
     * @return SqlSrv
     */
    public static function drop($table, $column)
    {
        return (new static)->dropRelations($table, $column)
            ->dropColumn($table, $column);
    }

    /**
     * @param $table
     * @param $column
     * @return $this
     */
    public function dropColumn($table, $column)
    {
        DB::getSchemaBuilder()->table($table, function($table) use($column) {
            $table->dropColumn($column);
        });

        return $this;
    }

    /**
     * Indexes and constraints must be dropped before dropping a column
     * @param $table
     * @param $column
     * @return $this
     */
    public function dropRelations($table, $column)
    {
        // Get constraint name
        $constraint = DB::select(DB::raw("
            SELECT name
            FROM sys.default_constraints
            WHERE parent_object_id = object_id('${table}')
            AND type = 'D'
            AND parent_column_id = (
                SELECT column_id
                FROM sys.columns
                WHERE object_id = object_id('${table}')
                AND name = '${column}'
            )"));

        // Delete constraint
        if (count($constraint)) {
            DB::statement(DB::raw("ALTER TABLE ${table} DROP CONSTRAINT ".$constraint[0]->name));
        }

        // Drop index, if exists
        $index = DB::select(DB::raw("SELECT i.name AS ind_name, C.name AS col_name, USER_NAME(O.uid) AS Owner, c.colid, k.Keyno,
            CASE WHEN I.indid BETWEEN 1 AND 254 AND (I.status & 2048 = 2048 OR I.Status = 16402 AND O.XType = 'V') THEN 1 ELSE 0 END AS IsPK,
            CASE WHEN I.status & 2 = 2 THEN 1 ELSE 0 END AS IsUnique
            FROM dbo.sysobjects o INNER JOIN dbo.sysindexes I ON o.id = i.id
            INNER JOIN dbo.sysindexkeys K ON I.id = K.id AND I.Indid = K.Indid
            INNER JOIN dbo.syscolumns c ON K.id = C.id AND K.colid = C.Colid
            WHERE LEFT(i.name, 8) <> '_WA_Sys_' AND o.status >= 0 AND O.Name LIKE '${table}' AND C.name = '${column}'
            ORDER BY O.name, I.Name, K.keyno"));

        if (count($index)) {
            DB::statement(DB::raw("DROP INDEX ${table}.".$index[0]->name));
        }

        return $this;
    }
}

<?php

namespace HS\Install\Tables;

class TableCollection
{
    protected $requestTablesLookup = [
        'HS_Assignment_Chain',  //
        'HS_Documents',  //
        'HS_Reminder',  //
        'HS_Reminder_Person',  //
        'HS_Request',  //
        'HS_Request_History',  //
        'HS_Request_Merged',  //
        'HS_Request_Note_Drafts',  //
        'HS_Request_Pushed',  //
        'HS_Request_ReportingTags',  //
        'HS_Stats_Responses',  //
        'HS_Subscriptions',  //
        'HS_Time_Tracker', //15
    ];

    protected $tables = [];

    protected $requestTables = [];

    protected $supportTables = [];

    public function addTables(array $tables)
    {
        foreach ($tables as $table) {
            $this->addTable($table);
        }
    }

    /**
     * Add table to collection
     *  as a request table
     *  or as a support table.
     * @param Table $table
     * @return $this
     */
    public function addTable(Table $table)
    {
        if (in_array($table->name, $this->requestTablesLookup)) {
            $this->requestTables[$table->name] = $table;
        } else {
            // Ensure HS_Settings is first, and therefore
            // gets copied first
            if ($table->name == 'HS_Settings') {
                array_unshift($this->supportTables, $table);
            } else {
                $this->supportTables[$table->name] = $table;
            }
        }

        $this->tables[] = $table;

        return $this;
    }

    /**
     * Get all tables.
     * @return array
     */
    public function tables()
    {
        return $this->tables;
    }

    /**
     * Get support tables.
     * @return array
     */
    public function support()
    {
        return $this->supportTables;
    }

    /**
     * Get table which are not
     * request tables.
     * @return array
     */
    public function request()
    {
        return $this->requestTables;
    }
}

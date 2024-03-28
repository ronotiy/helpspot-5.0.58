<?php

namespace HS\Console\Commands;

use DB;
use Illuminate\Console\Command;

class NullCustomFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:customFieldsToNull';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert all empty strings in the custom fields columns to null';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // first find otu what all columns we might have. It should be a one to one mapping to custom fields ID
        $fields = DB::table('HS_CustomFields')
            ->orderBy('xCustomField', 'asc')
            ->get();

        foreach ($fields as $field) {
            $customField = 'Custom'.$field->xCustomField;
            $affected = DB::table('HS_Request')
                ->where($customField, '')
                ->update([$customField => null]);
            $this->info('Updated '. $customField .' and affected '. $affected .' rows');
        }
        $this->info('Done');
    }
}

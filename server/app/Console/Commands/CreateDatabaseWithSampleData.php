<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Illuminate\Support\Facades\DB;
use PDO;

class CreateDatabaseWithSampleData extends AppCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:database-with-sample-data {dbname}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will create database with sample data for testing';

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
    public function handle(ConsoleOutput $consoleOutput, OutputFormatterStyle $outputFormatterStyle)
    {

        try {

            $dbname = $this->argument('dbname');
            $connection = $this->hasArgument('connection') && $this->argument('connection') ? $this->argument('connection'): DB::connection()->getPDO()->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            //check whether given database is already exsist
            $hasDb = DB::connection()->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "."'".$dbname."'");

            if(empty($hasDb)) {
                DB::connection()->select('CREATE DATABASE '. $dbname);
                $this->info("Database '$dbname' created");
            } else {
                $this->info("Database '$dbname' is already exsist");

                //drop already exsist database
                DB::connection()->select('DROP DATABASE '. $dbname);

                //create that database as new database
                DB::connection()->select('CREATE DATABASE '. $dbname);
            }
            

            $this->setConnection($dbname);
            $filePath = base_path().'/tests/dumpDbs/sampleDatabase.sql';
            
            // Temporary variable, used to store current query
            $templine = '';

            // Read in entire file
            $lines = file($filePath);

            $error = '';
            $this->info("Data restore start for '$dbname' Database");

            // Loop through each line
            foreach ($lines as $line){
                // Skip it if it's a comment
                if(substr($line, 0, 2) == '--' || $line == ''){
                    continue;
                }
                
                // Add this line to the current segment
                $templine .= $line;
            
                // If it has a semicolon at the end, it's the end of the query
                if (substr(trim($line), -1, 1) == ';'){
                    // Perform the query
                    if(!DB::unprepared($templine)){
                        $error .= 'Error performing query "<b>' . $templine . '</b>": ' . $db->error . '<br /><br />';
                    }
                    
                    // Reset temp variable to empty
                    $templine = '';
                }
            }

            $this->info("Data restoring successfully completed");

            $consoleOutput->writeln("Migration is running to restored db");
            Artisan::call('migrate', [], $consoleOutput);
            
        } catch (Exception $e) {
            $outputFormatterStyle->setForeground('red');
            $error = $outputFormatterStyle->apply("Error : " . $e->getMessage());
            $consoleOutput->writeln($error);
            $consoleOutput->writeln("");
        }

    }
}

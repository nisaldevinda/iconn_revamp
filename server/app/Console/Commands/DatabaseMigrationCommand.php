<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

class DatabaseMigrationCommand extends AppCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrations:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run tenant wise database migrations';

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
            foreach ($this->getTenants() as $tenant) {
                $this->setConnection($tenant);
                $consoleOutput->writeln("========================================================================");
                $consoleOutput->writeln("");
                $consoleOutput->writeln("Migration is running to tenant : $tenant");
                Artisan::call('migrate', [], $consoleOutput);
                $consoleOutput->getErrorOutput();
                $consoleOutput->writeln("");
            }
        } catch (Exception $e) {
            $outputFormatterStyle->setForeground('red');
            $error = $outputFormatterStyle->apply("Error : " . $e->getMessage());
            $consoleOutput->writeln($error);
            $consoleOutput->writeln("");
        }
    }
}

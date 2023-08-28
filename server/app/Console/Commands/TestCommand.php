<?php

namespace App\Console\Commands;

use App\Traits\TestTenant;
use Exception;
use Illuminate\Support\Facades\Log;


class TestCommand extends AppCommand
{
    use TestTenant;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testtenant:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Example Command';

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
        try {
            Log::info(">>> start testtenant");

            foreach($this->getTenants() as $tenant) {

                //TODO:: need to implement conditional based execution
                $this->setConnection($tenant);
                $this->process();

            }

            Log::info(">>> end testtenant");
        } catch (Exception $e) {
            // trigger fail event
            Log::error("Command Error : " .$e->getMessage());
        }
    }

}
<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set db connection
     */
    public function setConnection($tenantId)
    {
        try {
            Config::set("database.connections.app.database", $tenantId);
            DB::purge('app');
            Log::info(">> connection changed $tenantId <<");
        } catch (Exception $e) {
            Log::error("Error occured while changing $tenantId connection : " . $e->getMessage());
        }
    }

    /**
     * Get tenants
     * @return Illuminate\Support\Collection
     */
    public function getTenants()
    {
        return DB::connection('portal')->table('tenant')->pluck('id');
    }
}

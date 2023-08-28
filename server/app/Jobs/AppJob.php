<?php

namespace App\Jobs;

use App\Library\Session;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class AppJob extends Job
{
    /**
     * Set db connection
     */
    public function setConnection($tenantId)
    {
        try {
            Config::set("database.connections.app.database", $tenantId);
            DB::purge('app');
            // initiate session object
            $session = app(Session::class);
            $company = new stdClass();
            $company->tenantId = $tenantId;
            $session->setCompany($company);
        } catch (Exception $e) {
            Log::error("Error occured while changing $tenantId connection : " . $e->getMessage());
        }
    }
}

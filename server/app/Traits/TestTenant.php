<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait TestTenant
{
    public function process()
    {
        try {
            $users = DB::table('user')->pluck('email');
            Log::debug("usrs >>> " . json_encode($users));
        } catch (Exception $e) {
            Log::error("TestTenant error >>> " . $e->getMessage());
        }
        
    }
}

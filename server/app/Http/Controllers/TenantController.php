<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TODO:: This api request should move to portal
 */
class TenantController extends Controller
{
    public function verification(Request $request)
    {
        $tenant = $request->input('tenant', null);

        $result = DB::connection('portal')->table('tenant')->where('subdomain', "=", $tenant)->first();

        if (empty($result)) {
            return response()->json(['message' => 'Invalid Tenant', 'data' => null], 404);
        }

        return response()->json(['message' => 'success', 'data' => $result], 200);
    }
}

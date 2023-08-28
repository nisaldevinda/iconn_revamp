<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateTenantTable extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'portal';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::connection('portal')->hasTable('tenant')) {
            Schema::connection('portal')->create('tenant', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('subdomain');
                $table->boolean('isDelete')->default(false);
                $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            });

            $deaultTenant = [
                'id' => 'abc28a74-11fb-475d-aff5-cbe95fe60cd3', //Str::uuid()->toString(),
                'name' => 'ABC Company',
                'subdomain' => 'abc'
            ];

            DB::connection('portal')->table('tenant')->insert($deaultTenant);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('portal')->dropIfExists('tenant');
    }
}

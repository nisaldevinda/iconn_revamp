<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorAzureImportTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('azureSyncJob')) {
            Schema::rename('azureSyncJob', 'employeeImportJob');
        }

        if (Schema::hasTable('employeeImportJob')) {
            Schema::table('employeeImportJob', function (Blueprint $table) {
                $table->string('source')->after('id');
            });
        }

        if (Schema::hasTable('azureUser')) {
            Schema::rename('azureUser', 'stagingEmployee');
        }

        if (Schema::hasTable('stagingEmployee')) {
            Schema::table('stagingEmployee', function (Blueprint $table) {
                $table->renameColumn('azureSyncJobId', 'employeeImportJobId');
                $table->renameColumn('azureObjectId', 'sourceObjectId');

                $table->dropColumn('azureObject');

                $table->json('sourceObject')->after('status');
                $table->json('transformedObject')->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('employeeImportJob')) {
            Schema::rename('employeeImportJob', 'azureSyncJob');
        }

        if (Schema::hasTable('azureSyncJob')) {
            Schema::table('azureSyncJob', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }

        if (Schema::hasTable('stagingEmployee')) {
            Schema::rename('stagingEmployee', 'azureUser');
        }

        if (Schema::hasTable('azureUser')) {
            Schema::table('azureUser', function (Blueprint $table) {
                $table->renameColumn('employeeImportJobId', 'azureSyncJobId');
                $table->renameColumn('sourceObjectId', 'azureObjectId');

                $table->dropColumn('sourceObject');
                $table->json('azureObject')->after('status');

                $table->dropColumn('transformedObject');
            });
        }
    }
}

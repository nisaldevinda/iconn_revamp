<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortalCommandInfoLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::connection('portal')->hasTable('command_info_log')) {
            Schema::connection('portal')->create('command_info_log', function (Blueprint $table) {
                $table->id();
                $table->string('tenantId');
                $table->integer('cronJobId');
                $table->longText('description')->nullable()->default(null);
                $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            });
        }

        if (Schema::connection('portal')->hasTable('cron_job')) {
            $isRecordExist = !DB::connection('portal')->table('cron_job')->where('id', 3)->get()->isEmpty();

            if (!$isRecordExist) {
                $cronJob = [
                    'id' => 3,
                    'name' => 'Employment Status Email Notification',
                    'description' => 'Employment Status Email Notification'
                ];

                DB::connection('portal')->table('cron_job')->insert($cronJob);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('portal')->dropIfExists('command_info_log');
    }
}

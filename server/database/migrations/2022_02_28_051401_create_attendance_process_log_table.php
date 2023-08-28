<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceProcessLogTable extends Migration
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
        if (!Schema::connection('portal')->hasTable('attendance_process_log')) {
            Schema::connection('portal')->create('attendance_process_log', function (Blueprint $table) {
                $table->id();
                $table->uuid('tenantId');
                $table->boolean('hasFailed')->default(false);
                $table->longText('exception')->nullable()->default(null);
                $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
            });

            $attendanceProcessLog = [
                'id' => 1,
                'name' => 'Attendance Process',
                'description' => 'Attendence process log'
            ];

            DB::connection('portal')->table('cron_job')->insert($attendanceProcessLog);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('portal')->dropIfExists('attendance_process_log');
    }
}

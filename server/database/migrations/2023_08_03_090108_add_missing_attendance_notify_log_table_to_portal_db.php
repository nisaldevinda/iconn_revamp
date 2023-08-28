<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingAttendanceNotifyLogTableToPortalDb extends Migration
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
        if (!Schema::connection('portal')->hasTable('missing_attendance_email_notify_log')) {
            Schema::connection('portal')->create('missing_attendance_email_notify_log', function (Blueprint $table) {
                $table->id();
                $table->uuid('tenantId');
                $table->boolean('hasFailed')->default(false);
                $table->longText('exception')->nullable()->default(null);
                $table->timestamp('createdAt')->default(DB::raw('CURRENT_TIMESTAMP'));
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
        Schema::connection('portal')->dropIfExists('missing_attendance_email_notify_log');
    }
}

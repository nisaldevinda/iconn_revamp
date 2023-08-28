<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmployeeJobTableChangesRelatedToRehiringFeature extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE employeeJob MODIFY COLUMN employeeJourneyType ENUM('JOINED', 'PROMOTIONS', 'CONFIRMATION_CONTRACTS', 'TRANSFERS', 'RESIGNATIONS', 'REJOINED', 'REACTIVATED')");

        Schema::table('employeeJob', function (Blueprint $table) {
            $table->string('rejoinComment')->nullable()->default(null);
            $table->string('reactiveComment')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE employeeJob MODIFY COLUMN employeeJourneyType ENUM('JOINED', 'PROMOTIONS', 'CONFIRMATION_CONTRACTS', 'TRANSFERS', 'RESIGNATIONS')");

        Schema::table('employeeJob', function (Blueprint $table) {
            $table->dropColumn('rejoinComment');
            $table->dropColumn('reactiveComment');
        });
    }
}

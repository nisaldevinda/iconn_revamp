<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSchemaForEmployeeJourneyUpdateFeature extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeJob', function (Blueprint $table) {
            $table->enum('employeeJourneyType', ['JOINED', 'PROMOTIONS', 'CONFIRMATION_CONTRACTS', 'TRANSFERS', 'RESIGNATIONS'])->after('calendarId');
            $table->integer('employmentStatusId')->nullable()->default(null)->after('calendarId');
            $table->integer('payGradeId')->nullable()->default(null)->after('calendarId');
            $table->integer('attachmentId')->nullable()->default(null)->after('calendarId');

            $table->integer('promotionTypeId')->nullable()->default(null)->after('calendarId');
            $table->integer('confirmationReasonId')->nullable()->default(null)->after('calendarId');
            $table->integer('transferTypeId')->nullable()->default(null)->after('calendarId');
            $table->integer('resignationTypeId')->nullable()->default(null)->after('calendarId');

            $table->string('promotionReason')->nullable()->default(null)->after('calendarId');
            $table->string('confirmationRemark')->nullable()->default(null)->after('calendarId');
            $table->string('transferReason')->nullable()->default(null)->after('calendarId');
            $table->string('resignationReason')->nullable()->default(null)->after('calendarId');

            $table->enum('confirmationAction', ['ABSORB_TO_PERMANENT_CARDER', 'EXTEND_THE_PROBATION', 'CONTRACT_RENEWAL'])->nullable()->default(null)->after('calendarId');
            $table->date('resignationHandoverDate')->nullable()->default(null)->after('calendarId');
            $table->date('lastWorkingDate')->nullable()->default(null)->after('calendarId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeJob', function (Blueprint $table) {
            $table->dropColumn('employeeJourneyType');
            $table->dropColumn('employmentStatusId');
            $table->dropColumn('payGradeId');
            $table->dropColumn('attachmentId');

            $table->dropColumn('promotionTypeId');
            $table->dropColumn('confirmationReasonId');
            $table->dropColumn('transferTypeId');
            $table->dropColumn('resignationTypeId');

            $table->dropColumn('promotionReason');
            $table->dropColumn('confirmationRemark');
            $table->dropColumn('transferReason');
            $table->dropColumn('resignationReason');

            $table->dropColumn('confirmationAction');
            $table->dropColumn('resignationHandoverDate');
            $table->dropColumn('lastWorkingDate');
        });
    }
}

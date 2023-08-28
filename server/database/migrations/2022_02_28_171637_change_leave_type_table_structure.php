<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLeaveTypeTableStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaveType', function($table) {
            $table->dropColumn('country');
            $table->dropColumn('isEntitlementSituational');
            $table->dropColumn('leavePeriod');
            $table->dropColumn('whoCanApply');
            $table->dropColumn('applyAssignRestrictions');
        });

        Schema::table('leaveType', function($table) {
            $table->enum('leavePeriod', ['STANDARD', 'HIRE_DATE_BASED'])->after('name');
            $table->integer('applicableCountryId')->after('leavePeriod');
            $table->boolean('isSituational')->default(false)->after('applicableCountryId');
            $table->boolean('employeesCanApply')->default(false)->after('isSituational');
            $table->boolean('adminsCanAssign')->default(false)->after('employeesCanApply');
            $table->boolean('managersCanAssign')->default(false)->after('adminsCanAssign');
            $table->boolean('fullDayAllowed')->default(false)->after('managersCanAssign');
            $table->boolean('halfDayAllowed')->default(false)->after('fullDayAllowed');
            $table->boolean('timeDurationAllowed')->default(false)->after('halfDayAllowed');
            $table->boolean('adminCanAdjustEntitlements')->default(false)->after('timeDurationAllowed');
            $table->boolean('allowExceedingBalance')->default(false)->after('adminCanAdjustEntitlements');
            $table->boolean('allowAttachment')->default(false)->after('allowExceedingBalance');
            $table->boolean('attachmentManadatory')->default(false)->after('allowAttachment');
            $table->integer('maximumConsecutiveLeaveDays')->nullable()->default(null)->after('attachmentManadatory');
            $table->json('whoCanApply')->nullable()->default(null)->after('maximumConsecutiveLeaveDays');
            $table->json('whoCanAssign')->nullable()->default(null)->after('whoCanApply');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaveType', function($table) {
            $table->dropColumn('leavePeriod');
            $table->dropColumn('applicableCountryId');
            $table->dropColumn('isSituational');
            $table->dropColumn('employeesCanApply');
            $table->dropColumn('adminsCanAssign');
            $table->dropColumn('managersCanAssign');
            $table->dropColumn('fullDayAllowed');
            $table->dropColumn('halfDayAllowed');
            $table->dropColumn('timeDurationAllowed');
            $table->dropColumn('adminCanAdjustEntitlements');
            $table->dropColumn('allowExceedingBalance');
            $table->dropColumn('allowAttachment');
            $table->dropColumn('attachmentManadatory');
            $table->dropColumn('maximumConsecutiveLeaveDays');
            $table->dropColumn('whoCanApply');
            $table->dropColumn('whoCanAssign');
        });

        Schema::table('leaveType', function($table) {
            $table->string('country')->nullable()->default(null);
            $table->boolean('isEntitlementSituational')->default(false);
            $table->json('leavePeriod')->default("{}");
            $table->json('whoCanApply')->default("{}");
            $table->json('applyAssignRestrictions')->default("{}");
        });
    }
}

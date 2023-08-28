<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeQualificationsInstitutionsColumnsInEmployeeEducationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employeeEducation', function (Blueprint $table) {
            if (Schema::hasColumn('employeeEducation', 'qualification')) {
                $table->dropColumn('qualification');
            }

            if (Schema::hasColumn('employeeEducation', 'institution')) {
                $table->dropColumn('institution');
            }

            if (!Schema::hasColumn('employeeEducation', 'institutionId')) {
                $table->integer('institutionId')->nullable()->default(null)->after('level');
            }

            if (!Schema::hasColumn('employeeEducation', 'qualificationId')) {
                $table->integer('qualificationId')->nullable()->default(null)->after('level');
            }

            if (!Schema::hasColumn('employeeEducation', 'status')) {
                $table->enum('status', ['PENDING', 'COMPLETED'])->default('PENDING')->after('year');
            }

            if (!Schema::hasColumn('employeeEducation', 'isHighestQualification')) {
                $table->boolean('isHighestQualification')->default(false)->after('gpaScore');
            }

            if (Schema::hasColumn('employeeEducation', 'status')) {
                DB::table('employeeEducation')
                    ->whereNotNull('gpaScore')
                    ->update(['status' => 'COMPLETED']);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employeeEducation', function (Blueprint $table) {
            if (Schema::hasColumn('employeeEducation', 'qualificationId')) {
                $table->dropColumn('qualificationId');
            }

            if (Schema::hasColumn('employeeEducation', 'institutionId')) {
                $table->dropColumn('institutionId');
            }

            if (!Schema::hasColumn('employeeEducation', 'institution')) {
                $table->string('institution')->nullable()->default(null)->after('level');
            }

            if (!Schema::hasColumn('employeeEducation', 'qualification')) {
                $table->string('qualification')->nullable()->default(null)->after('level');
            }

            if (Schema::hasColumn('employeeEducation', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('employeeEducation', 'isHighestQualification')) {
                $table->dropColumn('isHighestQualification');
            }
        });
    }
}

<?php

use Doctrine\DBAL\Types\Types;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmploymentStatusTableSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('employmentStatus')->truncate();

        if (!Schema::hasColumn('employmentStatus', 'title')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->string('title')->after('id');
            });
        }

        if (Schema::hasColumn('employmentStatus', 'name')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                DB::statement("ALTER TABLE employmentStatus MODIFY COLUMN name ENUM('PERMANENT', 'PROBATION', 'CONTRACT', 'TRAINEE') DEFAULT 'PERMANENT'");
            });
        } else {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->enum('name', ['PERMANENT', 'PROBATION', 'CONTRACT', 'TRAINEE'])->default('PERMANENT')->after('id');
            });
        }

        if (!Schema::hasColumn('employmentStatus', 'periodUnit')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->enum('periodUnit', ['YEARS', 'MONTHS', 'DAYS'])->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('employmentStatus', 'period')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->integer('period')->nullable()->after('name');
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
        if (Schema::hasColumn('employmentStatus', 'title')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }

        if (Schema::hasColumn('employmentStatus', 'name')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->string('name')->change();
            });
        } else {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->string('name')->after('id');
            });
        }

        if (Schema::hasColumn('employmentStatus', 'periodUnit')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->dropColumn('periodUnit');
            });
        }

        if (Schema::hasColumn('employmentStatus', 'period')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->dropColumn('period');
            });
        }
    }
}

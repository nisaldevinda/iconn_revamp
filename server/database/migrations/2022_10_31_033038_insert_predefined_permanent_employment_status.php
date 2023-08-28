<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertPredefinedPermanentEmploymentStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('employmentStatus', 'isInvisible')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->boolean('isInvisible')->default(false)->after('isUneditable');
            });
        }

        $configuration = array(
            array(
                "id" => 1,
                "title" => "Permanent",
                "name" => "PERMANENT",
                "isInvisible" => true
            )
        );

        if (Schema::hasTable('employmentStatus')) {
            foreach ($configuration as $value) {
                DB::table('employmentStatus')->insert($value);
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
        if (Schema::hasColumn('employmentStatus', 'isInvisible')) {
            Schema::table('employmentStatus', function (Blueprint $table) {
                $table->dropColumn('isInvisible');
            });
        }

        $record = DB::table('employmentStatus')->where('id', 1)->get();
        if (!is_null($record)) {
            $delete = DB::table('employmentStatus')->where('id', 1)->delete();
        }
    }
}

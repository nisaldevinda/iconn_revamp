<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenamePrefixCodeColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('prefixCode', function(Blueprint $table) {
            $table->renameColumn('controlName', 'modelType'); // model Name
            $table->dropColumn('nextNo');
            $table->dropColumn('isDelete');
            $table->dropColumn('createdBy');
            $table->dropColumn('updatedBy');
        });

        
        try {
            $record = DB::table('prefixCode')->where('id', 1)->first();

            if ($record) {
                DB::table('prefixCode')->where('id', 1)->update([ 'modelType' => 'employee']);
            }  
        } catch (\Throwable $th) {
            throw $th;
        }


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prefixCode', function(Blueprint $table) {
            $table->renameColumn('modelType' , 'controlName'); // model Name
            $table->integer('nextNo')->default(0);
            $table->boolean('isDelete')->default(false);
            $table->integer('createdBy')->nullable()->default(null);
            $table->integer('updatedBy')->nullable()->default(null);
        });

        
        try {
            $record = DB::table('prefixCode')->where('id', 1)->first();

            if (empty($record)) {
                return ('Prefix code does not exists');
            }

            DB::table('prefixCode')->where('id', 1)->update(['controlName' => 'EmployeeController']);
        } catch (\Throwable $th) {
            throw $th;
        }
        
    }
} 

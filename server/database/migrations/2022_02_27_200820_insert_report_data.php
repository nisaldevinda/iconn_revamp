<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertReportData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $reportData =  array(
            [
                "reportName"=> "Gender Count By Department",
                "outputMethod"=> "pdf",
                "isChartAvailable"=> true,
                "chartType"=> "pieChart",
                "aggregateType"=> "count",
                "aggregateField"=> "gendername",
                "showSummeryTable"=> true,
                "groupBy"=> json_encode(["gendername","departmentName"]),
                "pageSize"=> 20,
                "targetKeys"=>json_encode( [
                    "firstName",
                    "gender"
                ]),
                "selectedTables"=>json_encode([

                    [
                        "isDerived"=> false,
                        "tableName"=> "genderTable",
                        "originalTableName"=> "employee",
                        "tableAlias"=> "genderTable",
                        "columnName"=> "name",
                        "displayName"=> "Name",
                        "columnIndex"=> 1,
                        "parentColumnName"=>"genderId",
                        "valueType"=> "text",
                        "dataIndex"=> "gendername"
                            
                        
                    ],
                    [
                        "isDerived"=> false,
                        "tableName"=> "departmentTable",
                        "originalTableName"=> "employee",
                        "tableAlias"=> "departmentTable",
                        "columnName"=> "name",
                        "displayName"=> "Department Name",
                        "parentColumnName"=>"departmentId",
                        "columnIndex"=> 2,
                        "valueType"=> "text",
                        "dataIndex"=> "departmentName"
                            
                        
                    ]
                ]),
                "joinCriterias"=>json_encode([
                    [
                        "tableOneName"=> "employee",
                        "tableOneAlias"=> "employee",
                        "tableOneOperandOne"=> "id",
                        "tableOneOperandTwo"=> "currentJobsId",
                        "operator"=> "=",
                        "tableTwoName"=> "employeeJob",
                        "tableTwoOperandOne"=> "employeeId",
                        "tableTwoAlias"=> "employeeJob1",
                        "tableTwoOperandTwo"=>"id"
            
                    ],
                     [
                        "tableOneName"=> "employee",
                        "tableOneAlias"=> "employee",
                        "tableOneOperandOne"=> "genderId",
                        "operator"=> "=",
                        "tableTwoName"=> "gender",
                        "tableTwoOperandOne"=> "id",
                        "tableTwoAlias"=> "genderTable"
                    ],
                     [
                        "tableOneName"=> "employeeJob1",
                        "tableOneAlias"=> "employeeJob1",
                        "tableOneOperandOne"=> "departmentId",
                        "operator"=> "=",
                        "tableTwoName"=> "department",
                        "tableTwoOperandOne"=> "id",
                        "tableTwoAlias"=> "departmentTable"
                    ]
                ]),
                "hideDetailedData"=>false,
                "isSystemReport"=>true,
                'createdBy' => null,
                'updatedBy' => null,
                'createdAt' => DB::raw('CURRENT_TIMESTAMP'),
                'updatedAt' => DB::raw('CURRENT_TIMESTAMP'),
                "isSystemReport"=>true,
                "id"=>1
            ]
        );

        try {
            $record = DB::table('reportData')->where('id', 1)->first();
            if ($record) {
                DB::table('reportData')->insert($record);
                DB::table('reportData')->where('id', 1)->update($reportData);

            }

            DB::table('reportData')->insert($reportData);
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
        try {
            $record = DB::table('reportData')->where('id', 1)->first();

            if (empty($record)) {
                return ('report does not exist');
            }

            $affectedRows = DB::table('reportData')->where('id', 1)->delete();

            return ($affectedRows) ? true : false;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}

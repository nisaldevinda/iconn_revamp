<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Traits\JsonModelReader;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Library\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ExcelExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExcelExport;



/**
 * Name: ReportDataService
 * Purpose: Performs tasks related to the ReportData model.
 * Description: ReportData Service class is called by the ReportDataController where the requests related
 * to ReportData Model (basic operations and others). Table that is being modified is reportData.
 * Module Creator: Chalaka
 */
class ReportDataService extends BaseService
{
    use JsonModelReader;

    private $reportDataModel;
    private $session;

    public function __construct(Store $store, Session $session)
    {
        $this->store = $store;
        $this->reportDataModel = $this->getModel('reportData', true);
        $this->session = $session;
    }


    /**
     * Following function retrives all reportDatas.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "reportData created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getAllReportData($permittedFields, $options)
    {
        try {
            $reportData = $this->store->getAll(
                $this->reportDataModel,
                $permittedFields,
                $options,
                [],
                [['isDelete', '=', false]]
            );

            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_ALL_RETRIVE'), $reportData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /**
     * Following function retrives report names and ids to generate dropdown.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "reportData created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getReportNamesWithId()
    {
        try {
            $reportData = $this->store->getFacade()::table('reportData')->select('id', 'displayName')->get();
            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_ALL_RETRIVE'), $reportData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /**
     * Following function retrives all reportDatas for an employee.
     *
     * @return int | String | array
     *
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "reportData created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function getReportDataById($id)
    {
        try {
            $reportData = (array) $this->store->getById($this->reportDataModel, $id);
            $readableReportData = $this->generateReadableReportData($reportData);
            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_ALL_RETRIVE'), $readableReportData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /**
     * Following function stores all reportData.
     *
     * @return int | String | array
     *
     * Usage:
     * $reportData => {
     * "reportName": "abcd",
     * "displayName": "abcd",
     * "selectedTables": [
     * {
     *     "tableName": "user",
     *     "selectedFields": ["id","email", "username"]
     * },
     * {
     *     "tableName": "employee",
     *     "selectedFields": ["bloodGroup", "gender", "qualifications", "joinedDate"]
     * }
     * ],
     * "filterCriterias": [
     *  {
     * 	    "criteria":"user.email=user.chalaka@gmail.com",
     * 	    "followedBy": ""
     *  }
     * ],
     * "joinCriterias": [
     *  {"tableOneName": "user",
     *  	"operandOne": "id",
     *  	"operator": "=",
     *  	"tableTwoName": "employee",
     *  	"operandTwo": "id"
     *  }
     * ]
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "reportData created Successuflly",
     *      $data => {{"id": 1, name": "Male"}, {"id": 1, name": "Male"}}
     * ]
     */
    public function storeReportData($reportData)
    {
        try {
            $validationResponse = ModelValidator::validate($this->reportDataModel, $reportData);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('reportDataMessages.basic.VALIDATOIN_ERR'), $validationResponse);
            }

            if (!$this->isReportDataValid($reportData)) {
                return $this->error(400, Lang::get('reportDataMessages.basic.ERR_INVALID_REPORT_DATA'), null);
            }

            $storableReportData = $this->generateStorableReportData($reportData);
            if (empty($storableReportData)) {
                return $this->error($e->getCode(), Lang::get('reportDataMessages.basic.ERR_CREATE'), null);
            }
            $newReportData = $this->store->insert($this->reportDataModel, $storableReportData, true);

            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_CREATE'), $newReportData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('reportDataMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function generates a report based on report template data.
     *
     * @param $id report data ID
     * @return array
     *
     * usage:
     * $id => 1
     *
     * Sample Output:
     * [
     *      "Name": "John",
     *      "Age": 25,
     *      "Marital Status": "Married"
     * ]
     */
    public function generateReport($id, $type)
    {
        try {

            $reportData = (array) $this->store->getById($this->reportDataModel, $id);
            if (empty($reportData)) {
                return $this->error(404, Lang::get('reportDataMessages.basic.ERR_INEXIST_REPORT'), $reportData);
            }

            $readableReportData = $this->generateReadableReportData($reportData);
            if (is_null($readableReportData)) {
                return $this->error(404, Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), $readableReportData);
            }

            $reportQuery = $this->generateQuery($readableReportData, $type);
            if (empty($reportQuery)) {
                return $this->error(400, Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), null);
            }
            $report["data"] = DB::select($reportQuery);

            $columns = array();

            foreach ($readableReportData["selectedTables"] as $selectedField) {
                if (isset($selectedField["columnName"]) && !empty($selectedField["columnName"])) {
                    $columns[$selectedField["columnIndex"]]["dataIndex"] = $selectedField["dataIndex"];
                    $columns[$selectedField["columnIndex"]]["title"] = $selectedField["displayName"];
                    $columns[$selectedField["columnIndex"]]["hideInTable"] = empty($selectedField["hideInTable"]) ? false : $selectedField["hideInTable"];
                    $columns[$selectedField["columnIndex"]]["valueType"] = $selectedField["valueType"];

                    if ($selectedField["columnName"] == 'salaryDetails') {
                        $columns[$selectedField["columnIndex"]]["valueType"] = "render:salaryDetails";
                    }
                }
            }

            foreach ($readableReportData["derivedFields"] as $derivedField) {
                $columns[$derivedField["columnIndex"]]["dataIndex"] = $derivedField["dataIndex"];
                $columns[$derivedField["columnIndex"]]["title"] = $derivedField["displayName"];
                $columns[$selectedField["columnIndex"]]["hideInTable"] = empty($selectedField["hideInTable"]) ? false : $selectedField["hideInTable"];
                $columns[$derivedField["columnIndex"]]["valueType"] = $selectedField["valueType"];
            }
            if ($reportData["isChartAvailable"] && $type == "chart") {
                $columns["value"]["dataIndex"] = "value";
                $columns["value"]["title"] = "Value";
                $columns["value"]["hideInTable"] = false;
                $columns["value"]["valueType"] = $selectedField["valueType"];
            }
            // ksort($columns);
            $report["columnData"] = array();
            foreach ($columns as $key => $val) {
                array_push($report["columnData"], $val);
            }

            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_ALL_RETRIVE'), $report);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    public function queryReportWithDynamicFilters($id, $filters)
    {
        try {
            $reportData = (array) $this->store->getById($this->reportDataModel, $id);
            if (empty($reportData)) {
                return $this->error(404, Lang::get('reportDataMessages.basic.ERR_INEXIST_REPORT'), $reportData);
            }

            $readableReportData = $this->generateReadableReportData($reportData);
            if (is_null($readableReportData)) {
                return $this->error(404, Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), $readableReportData);
            }

            $reportQuery = $this->generateQuery($readableReportData);

            if (!empty($filters)) {
                $reportQuery = "SELECT * FROM (" . $reportQuery . ") AS dynamicTable WHERE ";
                foreach ($filters as $dataIndex => $value) {
                    $reportQuery .= "CONCAT ( dynamicTable." . $dataIndex . " , '') = '" . $value . "' ";
                }
            }
            $filteredReport = $this->store->getFacade()::select($reportQuery);

            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_ALL_RETRIVE'), $filteredReport);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }


    /**
     * Following function validates report data based on the conditions and requirements.
     *
     * @return boolean
     *
     * Usage:
     * $reportData => {
     * "reportName": "abcd",
     * "displayName": "abcd",
     * "selectedTables": [
     * {
     *     "tableName": "user",
     *     "selectedFields": ["id","email", "username"]
     * },
     * {
     *     "tableName": "employee",
     *     "selectedFields": ["bloodGroup", "gender", "qualifications", "joinedDate"]
     * }
     * ]}
     *
     * Sample output:
     * true
     */
    public function isReportDataValid($reportData)
    {
        try {

            if (empty($reportData["reportName"])  || empty($reportData["selectedTables"])) {
                return false;
            }
            if (preg_match('(csv|xls|pdf)', $reportData["outputMethod"]) != 1) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }


    /**
     * Following function sets "dataIndex" for each column by concatinating tableName and column name
     *  in order to prepare a unique Id to identify the coulumns through frontend.
     *
     * @param $selectedTables selected tables data JSON from frontend
     * @return array
     *
     * usage:
     * "selectedTables": [
     * {
     *     "tableName": "employee",
     *     "selectedFields": [
     *          {"columnName":"bloodGroup", "displayName":"Blood Group", "columnLocation": 1},
     *          {"columnName":"email", "displayName":"Email Address", "columnLocation": 2}
     *      ]
     * }
     * ]
     *
     * Sample Output:
     * [
     * {
     *     "tableName": "employee",
     *     "selectedFields": [
     *          {"columnName":"bloodGroup", "displayName":"Blood Group", "columnLocation": 1, "dataIndex": "employeebloodGroup"},
     *          {"columnName":"email", "displayName":"Email Address", "columnLocation": 2, "dataIndex": "employeeemail"}
     *      ]
     * }
     * ]
     */
    public function setDataIndexForColumns($selectedTables)
    {
        $tableCount = 0;


        foreach ($selectedTables as $selectedTable) {
            if (isset($selectedTable["columnName"]) && !empty($selectedTable["columnName"])) {
                if ($selectedTable["isDerived"] == false) {
                    $dataIndex = ($selectedTable["tableName"] . $selectedTable["columnName"]);
                    $selectedTables[$tableCount]["dataIndex"] = $dataIndex;
                } else {

                    $dataIndex = "derivedField" . $tableCount;
                    $selectedTables[$tableCount]["dataIndex"] = $dataIndex;
                }
            }
            $tableCount++;
        }


        return $selectedTables;
    }


    /**
     * Following function generates an array which is storable in the database from the frontend received json,
     *
     * @param $reportData report data JSON from frontend
     * @return array
     *
     * usage:
     * $reportData => {
     * "reportName": "abcd",
     * "displayName": "abcd",
     * "selectedTables": [
     * {
     *     "tableName": "user",
     *     "selectedFields": ["id","email", "username"]
     * },
     * {
     *     "tableName": "employee",
     *     "selectedFields": [
     *          {"columnName":"bloodGroup", "displayName":"Blood Group", "columnLocation": 1},
     *          {"columnName":"email", "displayName":"Email Address", "columnLocation": 2}
     *      ]
     * }
     * ]
     * }
     *
     * Sample Output:
     * {
     * "reportName": "abcd",
     * "displayName": "abcd",
     * "selectedTables": "[ { "tableName": "user", "selectedFields": ["id","email", "username"] }, { "tableName": "employee", "selectedFields": ["bloodGroup", "gender", "qualifications", "joinedDate"] } ]",
     * }
     */
    public function generateStorableReportData($reportData)
    {


        try {
            $storableReportData["reportName"] = $reportData["reportName"];

            //$newSelectedTables = $this->setDataIndexForColumns($reportData["selectedTables"]);

            $storableReportData["selectedTables"] = json_encode($reportData["selectedTables"]);
            $storableReportData["outputMethod"] = json_encode($reportData["outputMethod"]);
            //      $storableReportData["showSummeryTable"] = $reportData["showSummeryTable"];
            //   $storableReportData["hideDetailedData"] = $reportData["hideDetailedData"];


            if (!empty($reportData["isChartAvailable"])) {
                $storableReportData["isChartAvailable"] = $reportData["isChartAvailable"];
            }
            if (!empty($reportData["chartType"])) {
                $storableReportData["chartType"] = $reportData["chartType"];
            }
            if (!empty($reportData["aggregateType"])) {
                $storableReportData["aggregateType"] = $reportData["aggregateType"];
            }
            if (!empty($reportData["aggregateField"])) {
                $storableReportData["aggregateField"] = $reportData["aggregateField"];
            }
            if (isset($reportData["showSummeryTable"])) {
                $storableReportData["showSummeryTable"] = $reportData["showSummeryTable"];
            }
            if (isset($reportData["hideDetailedData"])) {
                $storableReportData["hideDetailedData"] = $reportData["hideDetailedData"];
            }


            if (!empty($reportData["derivedFields"])) {
                $newDerivedFields = $this->setDataIndexForColumns($reportData["derivedFields"]);
                $storableReportData["derivedFields"] = json_encode($newDerivedFields);
            }

            if (!empty($reportData["joinCriterias"])) {
                $storableReportData["joinCriterias"] = json_encode($reportData["joinCriterias"]);
            }

            if (!empty($reportData["filterCriterias"])) {
                $storableReportData["filterCriterias"] = json_encode($reportData["filterCriterias"]);
            }

            if (!empty($reportData["groupBy"])) {
                $storableReportData["groupBy"] = json_encode($reportData["groupBy"]);
            }

            if (!empty($reportData["orderBy"])) {
                $storableReportData["orderBy"] = json_encode($reportData["orderBy"]);
            }

            if (!empty($reportData["pageSize"])) {
                $storableReportData["pageSize"] = json_encode($reportData["pageSize"]);
            }
            if (!empty($reportData["targetKeys"])) {
                $storableReportData["targetKeys"] = json_encode($reportData["targetKeys"]);
            }
            if (!empty($reportData["filterValues"])) {
                $storableReportData["filterValues"] = json_encode($reportData["filterValues"]);
            }
            if (!empty($reportData["sortByValues"])) {
                $storableReportData["sortByValues"] = json_encode($reportData["sortByValues"]);
            }
            if (!empty($reportData["filterCondition"])) {
                $storableReportData["filterCondition"] = $reportData["filterCondition"];
            }

            return $storableReportData;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    /**
     * Following function generates a json which is readable to the frontend.
     *
     * @param $reportData report data object from the database
     * @return array
     *
     * usage:
     * $reportData => {
     * "reportName": "abcd",
     * "displayName": "abcd",
     * "selectedTables": "[ { "tableName": "user", "selectedFields": ["id","email", "username"] }, { "tableName": "employee", "selectedFields": ["bloodGroup", "gender", "qualifications", "joinedDate"] } ]",
     * }
     *
     *
     * Sample Output:
     * {
     * "reportName": "abcd",
     * "displayName": "abcd",
     * "selectedTables": [
     * {
     *     "tableName": "user",
     *     "selectedFields": [
     *          {"columnName":"id", "displayName":"User ID", "columnLocation": 1},
     *          {"columnName":"email", "displayName":"Email Address", "columnLocation": 2}
     *      ]
     * },
     * {
     *     "tableName": "employee",
     *     "selectedFields": [
     *          {"columnName":"bloodGrpou", "displayName":"Blood GROUP", "columnLocation": 1},
     *          {"columnName":"email", "displayName":"Email Address", "columnLocation": 2}
     *      ]
     * }
     * ]
     * }
     */
    public function generateReadableReportData($reportData)
    {
        try {
            $readableReportData["reportName"] = $reportData["reportName"];
            $readableReportData["selectedTables"] = json_decode($reportData["selectedTables"], true);
            $readableReportData["outputMethod"] = json_decode($reportData["outputMethod"], true);
            $readableReportData["isSystemReport"] = $reportData["isSystemReport"];

            if (!empty($reportData["isChartAvailable"])) {
                $readableReportData["isChartAvailable"] = $reportData["isChartAvailable"];
            }
            if (!empty($reportData["isSystemReport"])) {
                $readableReportData["isSystemReport"] = $reportData["isSystemReport"];
            }
            if (!empty($reportData["chartType"])) {
                $readableReportData["chartType"] = $reportData["chartType"];
            }
            if (!empty($reportData["aggregateType"])) {
                $readableReportData["aggregateType"] = $reportData["aggregateType"];
            }
            if (!empty($reportData["aggregateField"])) {
                $readableReportData["aggregateField"] = $reportData["aggregateField"];
            }
            if (!empty($reportData["showSummeryTable"])) {
                $readableReportData["showSummeryTable"] = $reportData["showSummeryTable"];
            }
            if (!empty($reportData["hideDetailedData"])) {
                $readableReportData["hideDetailedData"] = $reportData["hideDetailedData"];
            }


            if (!empty($reportData["joinCriterias"])) {
                $readableReportData["joinCriterias"] = json_decode($reportData["joinCriterias"], true);
            }

            if (!empty($reportData["derivedFields"])) {
                $readableReportData["derivedFields"] = json_decode($reportData["derivedFields"], true);
            }

            if (!empty($reportData["filterCriterias"])) {
                $readableReportData["filterCriterias"] = json_decode($reportData["filterCriterias"], true);
            }

            if (!empty($reportData["orderBy"])) {
                $readableReportData["orderBy"] = json_decode($reportData["orderBy"], true);
            }

            if (!empty($reportData["groupBy"])) {
                $readableReportData["groupBy"] = json_decode($reportData["groupBy"], true);
            }

            if (!empty($reportData["pageSize"])) {
                $readableReportData["pageSize"] = json_decode($reportData["pageSize"], true);
            }
            if (!empty($reportData["targetKeys"])) {
                $readableReportData["targetKeys"] = json_decode($reportData["targetKeys"], true);
            }
            if (!empty($reportData["filterValues"])) {
                $readableReportData["filterValues"] = json_decode($reportData["filterValues"]);
            }
            if (!empty($reportData["sortByValues"])) {
                $readableReportData["sortByValues"] = json_decode($reportData["sortByValues"]);
            }
            if (!empty($reportData["filterCondition"])) {
                $readableReportData["filterCondition"] = $reportData["filterCondition"];
            }

            if (empty($readableReportData)) {
                return $this->error(400, Lang::get('reportDataMessages.basic.ERR_INVALID_REPORT_DATA'), null);
            }
            return $readableReportData;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    /**
     * Following function generates a query based on the report data
     *
     * @param $id report data ID
     * @return string
     *
     * usage:
     * $reportData => {
     * "reportName": "abcd",
     * "displayName": "abcd",
     * "selectedTables": [
     * {
     *     "tableName": "user",
     *     "selectedFields": [
     *          {"columnName":"id", "displayName":"User ID", "columnLocation": 1},
     *          {"columnName":"email", "displayName":"Email Address", "columnLocation": 2}
     *      ]
     * },
     * {
     *     "tableName": "employee",
     *     "selectedFields": [
     *          {"columnName":"bloodGroup", "displayName":"Blood GROUP", "columnLocation": 1},
     *          {"columnName":"email", "displayName":"Email Address", "columnLocation": 2}
     *      ]
     * }
     * ],
     * "filterCriterias": [
     *  {
     * 	    "criteria":"user.email=user.chalaka@gmail.com",
     * 	    "followedBy": ""
     *  }
     * ],
     * "joinCriterias": [
     *  {"tableOneName": "user",
     *  	"operandOne": "id",
     *  	"operator": "=",
     *  	"tableTwoName": "employee",
     *  	"operandTwo": "id"
     *  }
     * ]
     *
     * Sample Output:
     * "SELECT user.id, user.email, user.username, employee.bloodGroup, employee.gender, employee.qualifications, employee.joinedDate, country.name FROM user LEFT JOIN user ON user.id=employee.id  WHERE user.email=john@gmail.com"
     */
    public function generateQuery($reportData, $type)
    {
        try {
            $permittedEmployeeIds = $this->session->getContext()->getPermittedEmployeeIds();
            $ids = join("','", $permittedEmployeeIds);

            if (empty($reportData["selectedTables"])) {
                return null;
            }

            $reportQuery = $this->getSelectedColumnsQueryClause($reportData["selectedTables"], !empty($reportData["derivedFields"]) ? $reportData["derivedFields"] : null, $reportData, $type);

            error_log($reportQuery);
            if (!empty($reportData["joinCriterias"])) {
                $reportQuery .= $this->getJoinCriteriasQueryClause($reportData["joinCriterias"]);
            }

            if (!empty($reportData["filterCriterias"])) {
                $reportQuery .= $this->getFilterCriteriasQueryClause($reportData["filterCriterias"]);
            }

            if (!empty((array)$permittedEmployeeIds)) {
                if (!empty($reportData["filterCriterias"])) {
                    $reportQuery .= "AND employee.id IN ('" . implode("', '", $permittedEmployeeIds) . "')";
                } else {
                    $reportQuery .= "WHERE employee.id IN ('" . implode("', '", $permittedEmployeeIds) . "')";
                }
            }

            if (!empty($reportData["groupBy"]) &&  $type == "chart") {
                $reportQuery .= $this->getGroupByQueryClause($reportData["groupBy"]);
            }


            if (!empty($reportData["orderBy"])) {
                $reportQuery .= $this->getOrderByQueryClause($reportData["orderBy"]);
            }

            return $reportQuery;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    /**
     * Following function generates a the select clause based on the report data
     *
     * Conditions: The display order of the columns will be decided based on the order how the data is provided.
     *
     * @param $id report data ID
     * @return string
     *
     * usage:
     * $selectedTables =>
     * "selectedTables": [
     * {
     *     "tableName": "user",
     *     "selectedFields":
     * [
     *      {"columnName":"id", "displayName":"User ID", "columnIndex": 1},
     *      {"columnName":"email", "displayName":"Email Address", "columnIndex": 2}
     * ]
     * }
     * ]
     *
     * Sample Output:
     * "SELECT user.id, user.email, user.username, employee.bloodGroup, employee.gender, employee.qualifications, employee.joinedDate, country.name FROM user"
     */
    public function getSelectedColumnsQueryClause($selectedTables, $derivedFields, $reportData, $type)
    {
        try {
            $masterData = [
                'salaryComponents' => DB::table('salaryComponents')->select(['id AS value', 'name AS label'])->get()->toArray()
            ];

            $crypterKey = config("app.crypter_key");

            $fieldPermissions = $this->session->getPermission()->getFieldPermissions();
            $query = "SELECT ";
            $indexedColumnsSelectClause = array();

            $selectClauseNIndex = array();
            foreach ($selectedTables as $selectedField) {
                if (isset($fieldPermissions[$selectedField["originalTableName"]])) {
                    $key1 = array_search($selectedField["parentColumnName"], $fieldPermissions[$selectedField["originalTableName"]]['viewOnly']);
                    $key2 = array_search($selectedField["parentColumnName"], $fieldPermissions[$selectedField["originalTableName"]]['canEdit']);
                    if ($key1 > -1 || $key2 > -1) {
                        if ($selectedField["isDerived"]) {
                            if ($selectedField["columnName"] == "employeeName") {
                                $queryClause = "CONCAT( " . $selectedField["tableName"] . ".firstName " . $selectedField["tableName"] . "lastName )";
                                $queryClause .= " AS '" . $selectedField["dataIndex"] . "'";
                                $queryClause .= ", ";
                                $indexedColumnsSelectClause[$selectedField["columnIndex"]] = $queryClause;
                            } else if ($selectedField["columnName"] == "salaryDetails") {
                                $queryClause = $selectedField["tableName"] . "." . $selectedField["columnName"];

                                foreach ($masterData['salaryComponents'] as $salaryComponent) {
                                    $_value = $salaryComponent->value;
                                    $_label = $salaryComponent->label;
                                    $queryClause = 'REPLACE(' . $queryClause . ', \'{"salaryComponentId":' . $_value . ',"value"\', \'' . $_label . '\')';
                                }

                                $queryClause = "REPLACE(REPLACE(REPLACE($queryClause, '[', ''), '}]', ''), '},', '\n')";
                                // $queryClause = "JSON_UNQUOTE($queryClause)";
                                $queryClause .= " AS '" . $selectedField["dataIndex"] . "', ";
                                $indexedColumnsSelectClause[$selectedField["columnIndex"]] = $queryClause;
                            }
                        } else {
                            $queryClause = "";
                            if (isset($selectedField["isEncripted"]) && $selectedField["isEncripted"]) {
                                $queryClause .= " AES_DECRYPT(" . $selectedField["tableName"] . "." . $selectedField["columnName"] . ", '$crypterKey' ) ";
                            } else {
                                $queryClause .= $selectedField["tableName"] . "." . $selectedField["columnName"];
                            }
                            $queryClause .= " AS '" . $selectedField["dataIndex"] . "'";
                            $queryClause .= ", ";
                            $indexedColumnsSelectClause[$selectedField["columnIndex"]] = $queryClause;
                        }
                    } else {
                        $queryClause = " '***' ";

                        $queryClause .= " AS '" . $selectedField["dataIndex"] . "'";

                        $queryClause .= ", ";
                        $indexedColumnsSelectClause[$selectedField["columnIndex"]] = $queryClause;
                    }
                } else if ($fieldPermissions[0] == '*') {
                    if ($selectedField["isDerived"]) {
                        if ($selectedField["columnName"] == "employeeName") {
                            $queryClause = "CONCAT( ";
                            foreach ($selectedField["concatFields"] as $value) {
                                if ($value != 'middleName') {
                                    $queryClause .= $selectedField["tableName"] . "." . $value . ",' '" . " ,";
                                }
                            }
                            $queryClause = rtrim($queryClause, ", ");
                            $queryClause .= " )";

                            $queryClause .= " AS '" . $selectedField["dataIndex"] . "'";
                            $queryClause .= ", ";
                            $indexedColumnsSelectClause[$selectedField["columnIndex"]] = $queryClause;
                        } else if ($selectedField["columnName"] == "salaryDetails") {
                            $queryClause = $selectedField["tableName"] . "." . $selectedField["columnName"];

                            foreach ($masterData['salaryComponents'] as $salaryComponent) {
                                $_value = $salaryComponent->value;
                                $_label = $salaryComponent->label;
                                $queryClause = 'REPLACE(' . $queryClause . ', \'{"salaryComponentId":' . $_value . ',"value"\', \'' . $_label . '\')';
                            }

                            $queryClause = "REPLACE(REPLACE(REPLACE($queryClause, '[', ''), '}]', ''), '},', '\n')";
                            // $queryClause = "JSON_UNQUOTE($queryClause)";
                            $queryClause .= " AS '" . $selectedField["dataIndex"] . "', ";
                            $indexedColumnsSelectClause[$selectedField["columnIndex"]] = $queryClause;
                        }
                    } else {
                        $queryClause = "";
                        if (isset($selectedField["isEncripted"]) && $selectedField["isEncripted"]) {
                            $queryClause .= " AES_DECRYPT(" . $selectedField["tableName"] . "." . $selectedField["columnName"] . ", '$crypterKey' ) ";
                        } else {
                            $queryClause .= $selectedField["tableName"] . "." . $selectedField["columnName"];
                        }
                        $queryClause .= " AS '" . $selectedField["dataIndex"] . "'";
                        $queryClause .= ", ";
                        $indexedColumnsSelectClause[$selectedField["columnIndex"]] = $queryClause;
                    }
                } else {
                    $queryClause = " '***' ";
                    $queryClause .= " AS '" . $selectedField["dataIndex"] . "'";
                    $queryClause .= ", ";
                    $indexedColumnsSelectClause[$selectedField["columnIndex"]] = $queryClause;
                }
            }

            // if (!empty($derivedFields)) {
            //     $selectClauseNIndex = array();
            //     foreach ($derivedFields as $derivedField) {
            //             $queryClause = $derivedField["criteria"];

            //             $queryClause .= " AS '" . $derivedField["dataIndex"] . "'";

            //             $queryClause .= ", ";
            //             $indexedColumnsSelectClause[$derivedField["columnIndex"]] = $queryClause;
            //     }
            // }

            ksort($indexedColumnsSelectClause);
            foreach ($indexedColumnsSelectClause as $key => $val) {
                $query .= $val;
            }
            $query = substr($query, 0, -2); // remove ", " from the string.

            if (!empty($reportData["aggregateType"]) && $type == "chart") {

                $query .= $this->getAggregateQueryClause($reportData);
            }
            $query .= " FROM employee ";



            return $query;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    /**
     * Following function generates a join clause based on the report data
     *
     * Conditions: Always the table two has to be the table that has to be the newly joined table. So, make sure to send the newly joined table under "tableTwo".
     *
     * @param $id report data ID
     * @return string
     *
     * usage:
     * $filterCriterias =>
     * "filterCriterias": [
     *  {
     * 	    "criteria":"user.email=user.chalaka@gmail.com",
     * 	    "followedBy": ""
     *  }
     * ]
     *
     * Sample Output:
     * " LEFT JOIN user ON user.id=employee.id"
     */
    public function getJoinCriteriasQueryClause($joinCriterias)
    {
        try {
            $query = " ";

            foreach ($joinCriterias as $joinCriteria) {
                $query .= "LEFT JOIN " . $joinCriteria["tableTwoName"] . " AS " . $joinCriteria["tableTwoAlias"] . " ON " . $joinCriteria["tableOneName"] . "." . $joinCriteria["tableOneOperandOne"] . $joinCriteria["operator"] . $joinCriteria["tableTwoAlias"] . "." . $joinCriteria["tableTwoOperandOne"] . " ";
                if (isset($joinCriteria["tableOneOperandTwo"]) && isset($joinCriteria["tableTwoOperandOne"])) {
                    $query .= "AND "  . $joinCriteria["tableOneName"] . "." . $joinCriteria["tableOneOperandTwo"] . $joinCriteria["operator"] . $joinCriteria["tableTwoAlias"] . "." . $joinCriteria["tableTwoOperandTwo"] . " ";
                }
            }

            return $query;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    /**
     * Following function generates a where clause based on the report data
     *
     * @param $id report data ID
     * @return string
     *
     * usage:
     * $selectedTables =>
     * "selectedTables": [
     * {
     *     "tableName": "user",
     *     "selectedFields": ["id","email", "username"]
     * },
     * {
     *     "tableName": "employee",
     *     "selectedFields": ["bloodGroup", "gender", "qualifications", "joinedDate"]
     * }
     * ]
     *
     * Sample Output:
     * " WHERE user.email=john@gmail.com"
     */
    public function getFilterCriteriasQueryClause($filterCriterias)
    {
        try {

            $query = " WHERE ";
            $length = count($filterCriterias);
            $index = 0;

            foreach ($filterCriterias as $filterCriteria) {
                if ($filterCriteria["type"] == 'string') {
                    switch ($filterCriteria["condition"]) {
                        case 0:
                            $query .= $filterCriteria["criteria"] . " LIKE " . " '%" . $filterCriteria["value"] . "%' ";
                            break;
                        case 1:
                            $query .= $filterCriteria["criteria"] . " NOT LIKE " . " '%" . $filterCriteria["value"] . "%' ";
                            break;

                        case 2:
                            $query .= $filterCriteria["criteria"] . " = " . " '' ";
                            break;

                        case 3:
                            $query .= $filterCriteria["criteria"] . " != " . " '' ";
                            break;
                    }
                }
                if ($filterCriteria["type"] == 'model' || $filterCriteria["type"] == 'enum') {
                    switch ($filterCriteria["condition"]) {
                        case 0:
                            $query .= $filterCriteria["criteria"] . " LIKE " . " '%" . $filterCriteria["value"] . "%' ";
                            break;
                        case 1:
                            $query .= $filterCriteria["criteria"] . " NOT LIKE " . " '%" . $filterCriteria["value"] . "%' ";
                            break;

                        case 2:
                            $query .= $filterCriteria["criteria"] . " = " . " '' ";
                            break;

                        case 3:
                            $query .= $filterCriteria["criteria"] . " != " . " '' ";
                            break;
                    }
                }
                if ($filterCriteria["type"] == 'number') {
                    switch ($filterCriteria["condition"]) {
                        case 0:
                            $query .= $filterCriteria["criteria"] . " = " . " '%" . $filterCriteria["value"] . "%' ";
                            break;
                        case 1:
                            $query .= $filterCriteria["criteria"] . " != " . " '%" . $filterCriteria["value"] . "%' ";
                            break;

                        case 2:
                            $query .= $filterCriteria["criteria"] . " >= " .  $filterCriteria["value"];
                            break;

                        case 3:
                            $query .= $filterCriteria["criteria"] . " <= " .  $filterCriteria["value"];
                            break;

                        case 4:
                            $query .= $filterCriteria["criteria"] . " = " . " '' ";
                            break;

                        case 5:
                            $query .= $filterCriteria["criteria"] . " != " . " '' ";
                            break;
                    }
                }

                if ($filterCriteria["type"] == 'timestamp') {

                    $filterCriteriaChanged;
                    if ($filterCriteria["dateType"] == "year") {

                        $filterCriteriaChanged = " DATE_FORMAT(" . $filterCriteria["criteria"] . ",'%Y')";
                    } else if ($filterCriteria["dateType"] == "month") {
                        $filterCriteriaChanged = " DATE_FORMAT(" . $filterCriteria["criteria"] . ",'%Y-%m')";
                    } else {
                        $filterCriteriaChanged =  $filterCriteria["criteria"];
                    }

                    switch ($filterCriteria["condition"]) {
                        case 0:
                            $query .= $filterCriteriaChanged . " = " . " '" . $filterCriteria["value"] . "'";
                            break;
                        case 1:
                            $query .= $filterCriteriaChanged . " <= " . " '" . $filterCriteria["value"] . "'";
                            break;

                        case 2:
                            $query .= $filterCriteriaChanged . " < " .  " '" . $filterCriteria["value"] . "'";
                            break;

                        case 3:
                            $query .= $filterCriteriaChanged . " >=" .  " '" . $filterCriteria["value"] . "'";
                            break;

                        case 4:
                            $query .= $filterCriteriaChanged . " >" .  " '" . $filterCriteria["value"] . "'";
                            break;

                        case 5:
                            $query .= $filterCriteriaChanged . " = " . " '' ";
                            break;

                        case 6:
                            $query .= $filterCriteriaChanged . " != " . " '' ";
                            break;
                    }
                }

                if ($filterCriteria["type"] == 'employeeNumber') {
                    switch ($filterCriteria["condition"]) {
                        case 0:
                            $query .= $filterCriteria["criteria"] . " LIKE " . " '%" . $filterCriteria["value"] . "%' ";
                            break;
                        case 1:
                            $query .= $filterCriteria["criteria"] . " NOT LIKE " . " '%" . $filterCriteria["value"] . "%' ";
                            break;

                        case 2:
                            $query .= $filterCriteria["criteria"] . " = " . " '' ";
                            break;

                        case 3:
                            $query .= $filterCriteria["criteria"] . " != " . " '' ";
                            break;
                    }
                }

                if ($filterCriteria["type"] == 'boolean') {
                    switch ($filterCriteria["condition"]) {
                        case 0:
                            $query .= $filterCriteria["criteria"] . " = " . $filterCriteria["value"];
                            break;
                        case 1:
                            $query .= $filterCriteria["criteria"] . " != " . $filterCriteria["value"];
                            break;
                    }
                }

                if ($length - 1 > $index) {
                    $query .=  $filterCriteria["followedBy"] . " ";
                }

                $index++;
            }
            return $query;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    /**
     * Following function generates an GROUP BY clause based on the report data
     *
     * @param $id report data ID
     * @return string
     *
     * usage:
     * $selectedTables =>
     * "selectedTables": [
     * {
     *     "coulumnName": "firstName",
     *     "order": "ASC"
     * },
     * {
     *     "coulumnName": "lastName",
     *     "order": "ASC"
     * }
     * ]
     *
     * Sample Output:
     * " ORDER BY firstName ASC, lastName ASC"
     */
    public function getGroupByQueryClause($groupBy)
    {
        try {
            $query = " GROUP BY ";
            foreach ($groupBy as $value) {
                $query .= $value . ", ";
            }




            return substr($query, 0, -2);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    /**
     * Following function generates an ORDER BY clause based on the report data
     *
     * @param $id report data ID
     * @return string
     *
     * usage:
     * $selectedTables =>
     * "selectedTables": [
     * {
     *     "coulumnName": "firstName",
     *     "order": "ASC"
     * },
     * {
     *     "coulumnName": "lastName",
     *     "order": "ASC"
     * }
     * ]
     *
     * Sample Output:
     * " ORDER BY firstName ASC, lastName ASC"
     */
    public function getOrderByQueryClause($orderBy)
    {
        try {
            $query = " ORDER BY ";

            foreach ($orderBy as $orderCriteria) {
                $query .= $orderCriteria["columnName"] . " " . $orderCriteria["order"] . ",";
            }
            $query = substr($query, 0, -1); //removes the last "," (comma)

            return $query;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    public function getFilterDefinitions()
    {
        try {
            $definitions = config("reportFilterDefinitions");
            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_ALL_RETRIVE'), $definitions);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function delete a report.
     *
     * @param $id report id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Department deleted Successfully!",
     *      $data => {"title": "LK HR", ...}
     *
     */
    public function deleteReport($id)
    {
        try {
            $existingReportData = $this->store->getById($this->reportDataModel, $id);
            if (is_null($existingReportData)) {
                return $this->error(404, Lang::get('reportDataMessages.basic.ERR_NOT_EXIST'), null);
            }

            $recordExist = Util::checkRecordsExist($this->reportDataModel, $id);

            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('reportDataMessages.basic.ERR_NOTALLOWED'), null);
            }
            $reportDataModelName = $this->getModel("reportData", true);
            $result = $this->store->deleteById($reportDataModelName, $id, false);

            if (!$result) {
                return $this->error(502, Lang::get('reportDataMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_DELETE'), $existingReportData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('reportDataMessages.basic.ERR_DELETE'), null);
        }
    }


    public function update($id, $reportData)
    {
        try {
            $validationResponse = ModelValidator::validate($this->reportDataModel, $reportData, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('reportDataMessages.basic.VALIDATOIN_ERR'), $validationResponse);
            }

            if (!$this->isReportDataValid($reportData)) {
                return $this->error(400, Lang::get('reportDataMessages.basic.ERR_INVALID_REPORT_DATA'), null);
            }

            $storableReportData = $this->generateStorableReportData($reportData);

            if (empty($storableReportData)) {
                return $this->error($e->getCode(), Lang::get('reportDataMessages.basic.ERR_CREATE'), null);
            }

            $dbReportData = $this->store->getFacade()::table('reportData')->where('id', $id)->where('isDelete', false)->first();

            if ($dbReportData->isSystemReport) {
                return $this->error(403, Lang::get('reportDataMessages.basic.ERR_NOT_PERMITTED'), null);
            }

            if (is_null($dbReportData)) {
                return $this->error(404, Lang::get('reportDataMessages.basic.ERR_NONEXISTENT_RELIGION'), $religion);
            }

            $storableReportData['isDelete'] = $dbReportData->isDelete;
            $result = $this->store->updateById($this->reportDataModel, $id, $storableReportData);
            if (!$result) {
                return $this->error(502, Lang::get('reportDataMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_UPDATE'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('reportDataMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function generates an aggregate clause based on the report data
     *
     * @param $id report data ID
     * @return string
     *
     * usage:
     * $selectedTables =>
     * "selectedTables": [
     * {
     *     "coulumnName": "firstName",
     *     "order": "ASC"
     * },
     * {
     *     "coulumnName": "lastName",
     *     "order": "ASC"
     * }
     * ]
     *
     * Sample Output:
     * " ORDER BY firstName ASC, lastName ASC"
     */
    public function getAggregateQueryClause($reportData)
    {
        try {
            $aggregateFieldTable;
            $aggregateFieldColumn;
            $selectedTablesArray = $reportData["selectedTables"];



            foreach ($reportData["selectedTables"] as $nestedElement) {


                if ($nestedElement["dataIndex"] == $reportData["aggregateField"]) {

                    $aggregateFieldTable = $nestedElement["tableName"];
                    $aggregateFieldColumn = $nestedElement["columnName"];
                    break 1;
                }
            }

            switch ($reportData["aggregateType"]) {


                case "count":
                    $query = ", COUNT(" . $aggregateFieldTable . "." . $aggregateFieldColumn . ") AS value ";
                    break;

                case  "sum":
                    $query = ", SUM(" . $aggregateFieldTable . "." . $aggregateFieldColumn . ")  AS value  ";

                    break;

                case  "average":
                    $query = ", AVG(" . $aggregateFieldTable . "." . $aggregateFieldColumn . ")  AS value  ";
                    break;
            }

            return $query;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }


    /**
     * Following function generates a Head count chart
     *
     * @param $id report data ID
     * @return array
     *
     * usage:
     *
     *
     * Sample Output:
     * [
     *      "2020": "100",
     *      "2021": 25,
     *      "2022": "50"
     * ]
     */
    public function generateHeadCountReport()
    {
        try {
            $row = DB::select("SELECT YEAR (min(employeeEmployment.effectiveDate)) as minimumYear from employeeEmployment");
            $currentYear = Carbon::now()->year;
            $minimumYear = $row[0]->minimumYear;


            $reportData = [];
            for ($i = $minimumYear; $i <= $currentYear; $i++) {
                $lastYear = $i - 1;
                $currentYearLastDate = date("Y.12.31", strtotime($i . '-12-31'));
                $query =
                    // "   SELECT count(*) as value from(
                    //     SELECT employeeEmployment.employeeId
                    //     FROM employee
                    //     LEFT JOIN employeeEmployment ON employeeEmployment.employeeId=employee.id
                    //     where employeeEmployment.effectiveDate <='".$currentYearLastDate."'
                    //     and  employeeEmployment.effectiveDate != ' '
                    //     UNION
                    //     SELECT employeeEmployment.employeeId
                    //     FROM employee
                    //     LEFT JOIN employeeEmployment ON employeeEmployment.employeeId=employee.id
                    //     where employeeEmployment.effectiveDate <='".$currentYearLastDate."'
                    //     and employeeEmployment.employmentStatusId !=1
                    //     and  employeeEmployment.effectiveDate!= ' ') countTable";

                    $query =
                    "SELECT count(distinct employeeEmployment.employeeId) as value FROM employee
                LEFT JOIN employeeEmployment ON employeeEmployment.employeeId=employee.id
                where employeeEmployment.effectiveDate = (
                select employeeEmployment.effectiveDate
                from employeeEmployment
                where employeeEmployment.employeeId=employee.id and employeeEmployment.effectiveDate <= '" . $currentYearLastDate . " '
                order by effectiveDate desc limit 1) and employeeEmployment.employmentStatusId !=1";

                $report = DB::select($query);
                $reportSingle['type'] = $i;
                $reportSingle['value'] = $report[0]->value;
                array_push($reportData, $reportSingle);
            }



            return $this->success(200, Lang::get('reportDataMessages.basic.SUCC_ALL_RETRIVE'), $reportData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('reportDataMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    public function downloadReportByFormat($data)
    {
        try {

            $reportId = $data['reportId'];
            $format = $data['reportFormat'];

            $reportDataSet = $this->generateReport($reportId, 'report');

            $reportData = (!empty($reportDataSet['data']['data'])) ? $reportDataSet['data']['data'] : [];
            $columnData = (!empty($reportDataSet['data']['columnData'])) ? $reportDataSet['data']['columnData'] : [];
            $fileName = 'reportData.'.$format;
            $headerColumnCellRange = 'A1:F1';
            $excelData = Excel::download(new ReportExcelExport($columnData,  $reportData, $headerColumnCellRange), $fileName);
            $file = $excelData->getFile()->getPathname();
            $fileData = file_get_contents($file);
            unlink($file);

            $returnMesssage = ($format == 'csv') ? 'reportDataMessages.basic.SUCC_GET_CSV_FILE' : 'reportDataMessages.basic.SUCC_GET_EXCEL_FILE';

            return $this->success(200, Lang::get($returnMesssage), base64_encode($fileData));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }
}

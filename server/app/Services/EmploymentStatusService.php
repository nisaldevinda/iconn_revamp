<?php

namespace App\Services;

use Log;
use Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;
use App\Library\Util;

/**
 * Name: EmploymentStatusService
 * Purpose: Performs tasks related to the EmploymentStatus model.
 * Description: EmploymentStatus Service class is called by the EmploymentStatusController where the requests related
 * to EmploymentStatus Model (basic operations and others). Table that is being modified is employmentStatus.
 * Module Creator: Yohan
 */
class EmploymentStatusService extends BaseService
{

    use JsonModelReader;

    private $store;

    private $employmentStatusModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->employmentStatusModel = $this->getModel('employmentStatus', true);
    }


    /**
     * Following function creates a EmploymentStatus.
     *
     * @param $EmploymentStatus array containing the EmploymentStatus data
     * @return int | String | array
     *
     * Usage:
     * $EmploymentStatus => ["name": "New Opportunity"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "employmentStatus created Successuflly",
     * $data => {"name": "New Opportunity"}//$data has a similar set of values as the input
     *  */

    public function createEmploymentStatus($employmentStatus)
    {
        try {
            $validationResponse = ModelValidator::validate($this->employmentStatusModel, $employmentStatus, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employmentStatusMessages.basic.ERR_CREATE'), $validationResponse);
            }
            $employmentStatus['isDelete'] = 0;
            $newEmploymentStatus = $this->store->insert($this->employmentStatusModel, $employmentStatus, true);

            return $this->success(201, Lang::get('employmentStatusMessages.basic.SUCC_CREATE'), $newEmploymentStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employmentStatusMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all employmentStatus.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "employmentStatus created Successuflly",
     *      $data => {{"id": 1, name": "New Opportunity"}, {"id": 1, name": "New Opportunity"}}
     * ]
     */
    public function getAllEmploymentStatus($permittedFields, $options)
    {
        try {
            $filteredEmploymentStatus = $this->store->getAll(
                $this->employmentStatusModel,
                $permittedFields,
                $options,
                [],
                [['isDelete', '=', false]]
            );
            return $this->success(200, Lang::get('employmentStatusMessages.basic.SUCC_ALL_RETRIVE'), $filteredEmploymentStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employmentStatusMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single EmploymentStatus for a provided id.
     *
     * @param $id employmentStatus id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Marital Status created Successuflly",
     *      $data => {"id": 1, name": "New Opportunity"}
     * ]
     */
    public function getEmploymentStatus($id)
    {
        try {
            $employmentStatus = $this->store->getFacade()::table('employmentStatus')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($employmentStatus)) {
                return $this->error(404, Lang::get('employmentStatusMessages.basic.ERR_NONEXISTENT_EMPLOYMENT_STATUS'), $employmentStatus);
            }

            return $this->success(200, Lang::get('employmentStatusMessages.basic.SUCC_SINGLE_RETRIVE'), $employmentStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employmentStatusMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }



    /**
     * Following function retrives a single employmentStatus for a provided id.
     *
     * @param $id employmentStatus id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "name 1"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "employmentStatus created Successuflly",
     *      $data => {"id": 1, name": "New Opportunity"}
     * ]
     */
    public function getEmploymentStatusByKeyword($keyword)
    {
        try {
            $employmentStatus = $this->store->getFacade()::table('employmentStatus')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('employmentStatusMessages.basic.SUCC_ALL_RETRIVE'), $employmentStatus);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employmentStatusMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }



    /**
     * Following function updates a employmentStatus.
     *
     * @param $id employmentStatus id
     * @param $EmploymentStatus array containing EmploymentStatus data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "employmentStatus updated successfully.",
     *      $data => {"id": 1, name": "New Opportunity"} // has a similar set of data as entered to updating EmploymentStatus.
     *
     */
    public function updateEmploymentStatus($id, $employmentStatus)
    {
        try {
            if (isset($employmentStatus['data'])) {
                $this->store->getFacade()::beginTransaction();

                $errors = [];
                $data = $employmentStatus['data'];

                $name = $employmentStatus['name'];
                $existingRecordList = $this->store->getFacade()::table('employmentStatus')
                    ->where('name', $name)
                    ->where('isDelete', false)
                    ->select('id')
                    ->get()
                    ->toArray();

                $removedIdList = array_diff(
                    array_map(function ($record) {
                        return $record->id;
                    }, $existingRecordList),
                    array_map(function ($record) {
                        return $record['id'];
                    }, array_filter($data, function ($record) {
                        return isset($record['id']);
                    }))
                );

                foreach ($removedIdList as $id) {
                    $this->softDeleteEmploymentStatus($id);
                }

                foreach ($data as $index => $record) {
                    $record['name'] = $name;
                    $employeeStatusTitle = ucfirst(strtolower($record['name']));
                    $periodUnit = ucfirst(strtolower($record['periodUnit']));
                    $periodUnit = $record['period'] > 0 ? $periodUnit : substr($periodUnit, 0, -1);
                    $record['title'] = $employeeStatusTitle . ' - ' . $record['period'] . ' ' . $periodUnit;

                    if (isset($record['id'])) {
                        $response = $this->updateEmploymentStatus($record['id'], $record);
                    } else {
                        $response = $this->createEmploymentStatus($record);
                    }

                    if ($response['error']) {
                        $errors['data'][$index] = $response['data'] ?? [];
                    }
                }

                if (!empty($errors)) {
                    $this->store->getFacade()::rollback();
                    return $this->error(400, Lang::get('employmentStatusMessages.basic.ERR_UPDATE'), $errors);
                }

                $this->store->getFacade()::commit();
                return $this->success(200, Lang::get('employmentStatusMessages.basic.SUCC_UPDATE'), );
            } else {
                $validationResponse = ModelValidator::validate($this->employmentStatusModel, $employmentStatus, true);
                if (!empty($validationResponse)) {
                    return $this->error(400, Lang::get('employmentStatusMessages.basic.ERR_UPDATE'), $validationResponse);
                }

                $dbEmploymentStatus = $this->store->getFacade()::table('employmentStatus')->where('id', $id)->where('isDelete', false)->first();
                if (is_null($dbEmploymentStatus)) {
                    return $this->error(404, Lang::get('employmentStatusMessages.basic.ERR_NONEXISTENT_EMPLOYMENT_STATUS'), $employmentStatus);
                }

                if (empty($employmentStatus['name'])) {
                    return $this->error(400, Lang::get('employmentStatusMessages.basic.ERR_INVALID_CREDENTIALS'), null);
                }

                $employmentStatus['isDelete'] = $dbEmploymentStatus->isDelete;
                $result = $this->store->updateById($this->employmentStatusModel, $id, $employmentStatus);

                if (!$result) {
                    return $this->error(502, Lang::get('employmentStatusMessages.basic.ERR_UPDATE'), $id);
                }

                return $this->success(200, Lang::get('employmentStatusMessages.basic.SUCC_UPDATE'), $employmentStatus);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employmentStatusMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id employmentStatus id
     * @param $EmploymentStatus array containing EmploymentStatus data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "employmentStatus deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteEmploymentStatus($id)
    {
        try {
            $dbEmploymentStatus = $this->store->getById($this->employmentStatusModel, $id);
            if (is_null($dbEmploymentStatus)) {
                return $this->error(404, Lang::get('employmentStatusMessages.basic.ERR_NONEXISTENT_EMPLOYMENT_STATUS'), null);
            }

            //check whether has any employmentStatus link with employaa job
            $relatedEmployeeJobRecords = $this->store->getFacade()::table('employeeJob')
                ->where('employmentStatusId', $id)
                ->first();

            if (!empty($relatedEmployeeJobRecords)) {
                return $this->error(502, Lang::get('employmentStatusMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('employmentStatus')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('employmentStatusMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employmentStatusMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a employmentStatus.
     *
     * @param $id employmentStatus id
     * @param $EmploymentStatus array containing EmploymentStatus data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "employmentStatus deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteEmploymentStatus($id)
    {
        try {
            $dbEmploymentStatus = $this->store->getById($this->employmentStatusModel, $id);
            if (is_null($dbEmploymentStatus)) {
                return $this->error(404, Lang::get('employmentStatusMessages.basic.ERR_NONEXISTENT_EMPLOYMENT_STATUS'), null);
            }

            $this->store->deleteById($this->employmentStatusModel, $id);

            return $this->success(200, Lang::get('employmentStatusMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('employmentStatusMessages.basic.ERR_DELETE'), null);
        }
    }
}

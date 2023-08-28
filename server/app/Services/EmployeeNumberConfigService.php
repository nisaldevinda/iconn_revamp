<?php

namespace App\Services;

use Log;
use Exception;
use App\Library\Store;
use App\Library\Util;
use Illuminate\Support\Facades\Lang;
use App\Library\ModelValidator;
use App\Traits\JsonModelReader;

class EmployeeNumberConfigService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $employeeNumberConfigurationModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->employeeNumberConfigurationModel = $this->getModel('employeeNumberConfiguration', true);
    }


    public function addEmployeeNumberConfigs($config)
    {
        try {

            $validationResponse = ModelValidator::validate($this->employeeNumberConfigurationModel, $config);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeNumberConfigMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newConfig = $this->store->insert($this->employeeNumberConfigurationModel, $config, true);

            return $this->success(201, Lang::get('employeeNumberConfigMessages.basic.SUCC_CREATE'), $newConfig);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeNumberConfigMessages.basic.ERR_CREATE'), null);
        }
    }


    public function getAllEmployeeNumberConfigs($permittedFields, $options)
    {

        try {
            // $filteredConfigs = $this->store->getAll(
            //     $this->employeeNumberConfigurationModel,
            //     $permittedFields,
            //     $options,
            //     []
            // );

            // $x = [
            //     'id' => 1,
            //     'entityId' => 2,
            //     'prefix' => "A",
            //     'nextNumber' => 1,
            //     'numberLength' => 6,
            //     'isEditable' => true,
            //     'entity' => "ABC"
            // ];

            // $r = [
            //     'current' => 1,
            //     'pageSize' => 10,
            //     'total' => 1,
            //     'data' => [$x]
            // ];

            $filterData = $this->store->getFacade()::table('employeeNumberConfiguration')
                    ->join('orgEntity', 'orgEntity.id', '=', 'employeeNumberConfiguration.entityId')
                    ->select('employeeNumberConfiguration.*', 'orgEntity.name AS entity')
                    ->get()->toArray();

            $data = [
                'current' => 1,
                'pageSize' => 30,
                'total' => 1,
                'data' => $filterData
            ];



            return $this->success(200, Lang::get('employeeNumberConfigMessages.basic.SUCC_GETALL'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeNumberConfigMessages.basic.ERR_GETALL'), null);
        }
    }


    public function getEmployeeNumberConfigs($id)
    {
        try {
            $config = $this->store->getById($this->employeeNumberConfigurationModel, $id);
            if (is_null($config)) {
                return $this->error(404, Lang::get('employeeNumberConfigMessages.basic.ERR_NOT_EXIST'), null);
            }

            return $this->success(200, Lang::get('employeeNumberConfigMessages.basic.SUCC_GET'), $config);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeNumberConfigMessages.basic.ERR_GET'), null);
        }
    }


    public function updateEmployeeNumberConfigs($id, $config)
    {
        try {
            $validationResponse = ModelValidator::validate($this->employeeNumberConfigurationModel, $config, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('employeeNumberConfigMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $existingDivision = $this->store->getById($this->employeeNumberConfigurationModel, $id);
            if (is_null($existingDivision)) {
                return $this->error(404, Lang::get('employeeNumberConfigMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->updateById($this->employeeNumberConfigurationModel, $id, $config);

            if (!$result) {
                return $this->error(502, Lang::get('employeeNumberConfigMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('employeeNumberConfigMessages.basic.SUCC_UPDATE'), $config);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeNumberConfigMessages.basic.ERR_UPDATE'), null);
        }
    }


    public function removeEmployeeNumberConfigs($id)
    {
        try {
            $existingConfig = $this->store->getById($this->employeeNumberConfigurationModel, $id);
            if (is_null($existingConfig)) {
                return $this->error(404, Lang::get('employeeNumberConfigMessages.basic.ERR_NOT_EXIST'), null);
            }
            $recordExist = Util::checkRecordsExist($this->employeeNumberConfigurationModel, $id);

            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('employeeNumberConfigMessages.basic.ERR_NOTALLOWED'), null);
            }
            $employeeNumberConfigurationModelName = $this->employeeNumberConfigurationModel->getName();
            $result = $this->store->getFacade()::table('employeeNumberConfiguration')->where('id', $id)->delete();

            if ($result == 0) {
                return $this->error(502, Lang::get('employeeNumberConfigMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('employeeNumberConfigMessages.basic.SUCC_DELETE'), $existingConfig);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('employeeNumberConfigMessages.basic.ERR_DELETE'), null);
        }
    }
}

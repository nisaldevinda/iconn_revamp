<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\ModelValidator;
use App\Library\Store;
use App\Traits\JsonModelReader;

class ConfirmationProcessService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $confirmationProcessModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->confirmationProcessModel = $this->getModel('confirmationProcess', true);
    }

    public function createConfirmationProcess($data)
    {
        try {

            $validationResponse = ModelValidator::validate($this->confirmationProcessModel, $data, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('confirmationProcess.basic.ERR_CREATE'), $validationResponse);
            }

            $jobCategoryIds = $data['jobCategoryIds'];
            $employmentTypeIds = $data['employmentTypeIds'];
            unset($data['jobCategoryIds']);
            unset($data['employmentTypeIds']);

            $this->store->getFacade()::beginTransaction();

            $confirmationProcess = $this->store->insert($this->confirmationProcessModel, $data, true);

            $resJobCategories = array_map(function ($jobCategoryId) use ($confirmationProcess) {
                return [
                    'confirmationProcessId' => $confirmationProcess['id'],
                    'jobCategoryId' => $jobCategoryId
                ];
            }, $jobCategoryIds);

            $resEmploymentTypes = array_map(function ($employmentTypeId) use ($confirmationProcess) {
                return [
                    'confirmationProcessId' => $confirmationProcess['id'],
                    'employmentTypeId' => $employmentTypeId
                ];
            }, $employmentTypeIds);

            $this->store->getFacade()::table('confirmationProcessJobCategories')->insert($resJobCategories);

            $this->store->getFacade()::table('confirmationProcessEmploymentTypes')->insert($resEmploymentTypes);

            $this->store->getFacade()::commit();
        
            return $this->success(201, Lang::get('confirmationProcess.basic.SUCC_CREATE'), $confirmationProcess);
               
        } catch (Exception $e) {
            $this->store->getFacade()::rollBack();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationProcess.basic.ERR_CREATE'), null);
        }
    }

    public function getConfirmationProcessList($permittedFields, $options)
    {
        try {
            $filteredData = $this->store->getAll(
                $this->confirmationProcessModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );

            return $this->success(200, Lang::get('confirmationProcess.basic.SUCC_ALL_RETRIVE'), $filteredData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationProcess.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    public function getConfirmationProcess($id)
    {
        try {
            $confirmationProcess = $this->store->getFacade()::table('confirmationProcess')
                ->join('confirmationProcessJobCategories', 'confirmationProcess.id', '=', 'confirmationProcessJobCategories.confirmationProcessId')
                ->join('confirmationProcessEmploymentTypes', 'confirmationProcess.id', '=', 'confirmationProcessEmploymentTypes.confirmationProcessId')
                ->where('confirmationProcess.id', $id)
                ->where('isDelete', false)
                ->get(['confirmationProcess.id', 'confirmationProcess.name', 'confirmationProcess.formTemplateId', 'confirmationProcess.orgEntityId', 'confirmationProcessJobCategories.jobCategoryId', 'confirmationProcessEmploymentTypes.employmentTypeId']);

            if (empty($confirmationProcess)) {
                return $this->success(404, Lang::get('confirmationProcess.basic.ERR_NOT_EXIST'), null);
            }

            $jobCategoryIds = $confirmationProcess->unique('jobCategoryId')->pluck('jobCategoryId');
            $employmentTypeIds = $confirmationProcess->unique('employmentTypeId')->pluck('employmentTypeId');
            $data = $confirmationProcess->first();
            unset($data->jobCategoryId);
            unset($data->employmentTypeId);
            $data->jobCategoryIds = $jobCategoryIds;
            $data->employmentTypeIds = $employmentTypeIds;

            return $this->success(200, Lang::get('confirmationProcess.basic.SUCC_ALL_RETRIVE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationProcess.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    public function updateConfirmationProcess($id, $data)
    {
        try {

            // $validationResponse = ModelValidator::validate($this->confirmationProcessModel, $data, true);
            // if (!empty($validationResponse)) {
            //     return $this->error(400, Lang::get('confirmationProcess.basic.ERR_UPDATE'), $validationResponse);
            // }

            $confirmationProcess = $this->store->getFacade()::table('confirmationProcess')
                ->join('confirmationProcessJobCategories', 'confirmationProcess.id', '=', 'confirmationProcessJobCategories.confirmationProcessId')
                ->join('confirmationProcessEmploymentTypes', 'confirmationProcess.id', '=', 'confirmationProcessEmploymentTypes.confirmationProcessId')
                ->where('confirmationProcess.id', $id)
                ->where('isDelete', false)
                ->get(['confirmationProcess.id', 'confirmationProcess.name', 'confirmationProcess.formTemplateId', 'confirmationProcess.orgEntityId', 'confirmationProcessJobCategories.jobCategoryId', 'confirmationProcessEmploymentTypes.employmentTypeId']);

            if (empty($confirmationProcess)) {
                return $this->success(404, Lang::get('confirmationProcess.basic.ERR_NOT_EXIST'), null);
            }

            $existingCategoryIds = $confirmationProcess->pluck('jobCategoryId')->all();
            $removedCategoryIds = array_diff($existingCategoryIds, $data['jobCategoryIds']);
            $newCategoryIds = array_diff($data['jobCategoryIds'], $existingCategoryIds);
            $newJobCategories = array_map(function ($jobCategoryId) use ($id) {
                return [
                    'confirmationProcessId' => $id,
                    'jobCategoryId' => $jobCategoryId
                ];
            }, $newCategoryIds);
            unset($data['jobCategoryIds']);

            $existingEmploymentTypeIds = $confirmationProcess->pluck('employmentTypeId')->all();
            $removedEmploymentTypeIds = array_diff($existingEmploymentTypeIds, $data['employmentTypeIds']);
            $newEmploymentTypeIds = array_diff($data['employmentTypeIds'], $existingEmploymentTypeIds);
            $newEmploymentTypes = array_map(function ($employmentTypeId) use ($id) {
                return [
                    'confirmationProcessId' => $id,
                    'employmentTypeId' => $employmentTypeId
                ];
            }, $newEmploymentTypeIds);
            unset($data['employmentTypeIds']);
            unset($data['id']);

            $this->store->getFacade()::beginTransaction();

            $this->store->getFacade()::table('confirmationProcess')->where('id', $id)->update($data);

            $this->store->getFacade()::table('confirmationProcessJobCategories')->insert($newJobCategories);
            
            if (!empty($removedCategoryIds)) {
                $this->store->getFacade()::table('confirmationProcessJobCategories')
                    ->where('confirmationProcessId', $id)->whereIn('jobCategoryId', $removedCategoryIds)
                    ->delete();
            }

            $this->store->getFacade()::table('confirmationProcessEmploymentTypes')->insert($newEmploymentTypes);

            if (!empty($removedEmploymentTypeIds)) {
                $this->store->getFacade()::table('confirmationProcessEmploymentTypes')
                    ->where('confirmationProcessId', $id)->whereIn('employmentTypeId', $removedEmploymentTypeIds)
                    ->delete();
            }

            $this->store->getFacade()::commit();

            return $this->success(200, Lang::get('confirmationProcess.basic.SUCC_UPDATE'), $data);
        } catch (Exception $e) {
            $this->store->getFacade()::rollBack();
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('confirmationProcess.basic.ERR_UPDATE'), []);
        }
    }

    public function deleteConfirmationProcess($id)
    {
        try {

            $confirmationProcess = $this->store->getFacade()::table('confirmationProcess')->first(['id']);

            if (empty($confirmationProcess)) {
                return $this->success(404, Lang::get('confirmationProcess.basic.ERR_NOT_EXIST'), null);
            }

            $this->store->getFacade()::table('confirmationProcess')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('confirmationProcess.basic.SUCC_DELETE'), $id);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('confirmationProcess.basic.ERR_DELETE'), null);
        }
    }
}

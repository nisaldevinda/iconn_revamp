<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\ModelValidator;
use App\Library\Store;
use App\Traits\JsonModelReader;
use Illuminate\Support\Facades\DB;

class ResignationProcessService extends BaseService
{
    use JsonModelReader;

    private $store;
    private $resignationProcessModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->resignationProcessModel = $this->getModel('resignationProcess', true);
    }

    public function createResignationProcess($data)
    {
        try {

            $validationResponse = ModelValidator::validate($this->resignationProcessModel, $data, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('resignationProcess.basic.ERR_CREATE'), $validationResponse);
            }

            $jobCategoryIds = $data['jobCategoryIds'];
            unset($data['jobCategoryIds']);

            $this->store->getFacade()::beginTransaction();

            $resignationProcess = $this->store->insert($this->resignationProcessModel, $data, true);

            $resJobCategories = array_map(function ($jobCategoryId) use ($resignationProcess) {
                return [
                    'resignationProcessId' => $resignationProcess['id'],
                    'jobCategoryId' => $jobCategoryId
                ];
            }, $jobCategoryIds);

            $this->store->getFacade()::table('resignationProcessJobCategories')->insert($resJobCategories);

            $this->store->getFacade()::commit();
        
            return $this->success(201, Lang::get('resignationProcess.basic.SUCC_CREATE'), $resignationProcess);
               
        } catch (Exception $e) {
            $this->store->getFacade()::rollBack();
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationProcess.basic.ERR_CREATE'), null);
        }
    }

    public function getResignationProcessList($permittedFields, $options)
    {
        try {
            $filteredData = $this->store->getAll(
                $this->resignationProcessModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );

            return $this->success(200, Lang::get('resignationProcess.basic.SUCC_ALL_RETRIVE'), $filteredData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationProcess.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    public function getResignationProcess($id)
    {
        try {
            $resignationProcess = $this->store->getFacade()::table('resignationProcess')
                ->join('resignationProcessJobCategories', 'resignationProcess.id', '=', 'resignationProcessJobCategories.resignationProcessId')
                ->where('resignationProcess.id', $id)
                ->where('isDelete', false)
                ->get(['resignationProcess.id', 'resignationProcess.name', 'resignationProcess.formTemplateId', 'resignationProcess.orgEntityId', 'resignationProcessJobCategories.jobCategoryId']);

            if (empty($resignationProcess)) {
                return $this->success(404, Lang::get('resignationProcess.basic.ERR_NOT_EXIST'), null);
            }

            $jobCategoryIds = $resignationProcess->pluck('jobCategoryId');
            $data = $resignationProcess->first();
            unset($data->jobCategoryId);
            $data->jobCategoryIds = $jobCategoryIds;

            return $this->success(200, Lang::get('resignationProcess.basic.SUCC_ALL_RETRIVE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationProcess.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    public function updateResignationProcess($id, $data)
    {
        try {

            // $validationResponse = ModelValidator::validate($this->resignationProcessModel, $data, true);
            // if (!empty($validationResponse)) {
            //     return $this->error(400, Lang::get('resignationProcess.basic.ERR_UPDATE'), $validationResponse);
            // }

            $resignationProcess = $this->store->getFacade()::table('resignationProcess')
                ->join('resignationProcessJobCategories', 'resignationProcess.id', '=', 'resignationProcessJobCategories.resignationProcessId')
                ->where('resignationProcess.id', $id)
                ->where('isDelete', false)
                ->get(['resignationProcess.id', 'resignationProcess.name', 'resignationProcess.formTemplateId', 'resignationProcess.orgEntityId', 'resignationProcessJobCategories.jobCategoryId']);

            if (empty($resignationProcess)) {
                return $this->success(404, Lang::get('resignationProcess.basic.ERR_NOT_EXIST'), null);
            }

            $existingCategoryIds = $resignationProcess->pluck('jobCategoryId')->all();
            $removedCategoryIds = array_diff($existingCategoryIds, $data['jobCategoryIds']);
            $newCategoryIds = array_diff($data['jobCategoryIds'], $existingCategoryIds);
            $newJobCategories = array_map(function ($jobCategoryId) use ($id) {
                return [
                    'resignationProcessId' => $id,
                    'jobCategoryId' => $jobCategoryId
                ];
            }, $newCategoryIds);
            unset($data['jobCategoryIds']);
            unset($data['id']);

            $this->store->getFacade()::beginTransaction();

            $this->store->getFacade()::table('resignationProcess')->where('id', $id)->update($data);

            $this->store->getFacade()::table('resignationProcessJobCategories')->insert($newJobCategories);
            
            if (!empty($removedCategoryIds)) {
                $this->store->getFacade()::table('resignationProcessJobCategories')
                    ->where('resignationProcessId', $id)->whereIn('jobCategoryId', $removedCategoryIds)
                    ->delete();
            }

            $this->store->getFacade()::commit();

            return $this->success(200, Lang::get('resignationProcess.basic.SUCC_UPDATE'), $data);
        } catch (Exception $e) {
            $this->store->getFacade()::rollBack();
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('resignationProcess.basic.ERR_UPDATE'), []);
        }
    }

    public function deleteResignationProcess($id)
    {
        try {

            $resignationProcess = $this->store->getFacade()::table('resignationProcess')->first(['id']);

            if (empty($resignationProcess)) {
                return $this->success(404, Lang::get('resignationProcess.basic.ERR_NOT_EXIST'), null);
            }

            $this->store->getFacade()::table('resignationProcess')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('resignationProcess.basic.SUCC_DELETE'), $id);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('resignationProcess.basic.ERR_DELETE'), null);
        }
    }
}

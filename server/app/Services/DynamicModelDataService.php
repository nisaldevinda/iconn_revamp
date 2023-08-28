<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Traits\JsonModelReader;

/**
 * Name: DynamicModelDataService
 * Purpose: Performs tasks related to the User model.
 * Description: DynamicModelDataService class is called by the JobtitleController where the requests related
 * to dynamicModel Model (basic operations and others).
 * Module Creator: Chalaka
 */
class DynamicModelDataService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $dynamicModelModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Following function creates a job title. THe job title details that are provided in the Request
     * are extracted and saved to the dynamicModel table in the database. job title's id is auto genarated.
     *
     * @param $user array containing the user data
     * @return int | String | array
     *
     * Usage:
     * $user => ["dynamicModel": "Job 1", "jobDescription": "The job is described here.","jobSpecification": "attachment link is provided here", "notes": "This is a note about the title."]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Job title created Successfully!",
     * $data => {"id": 1, dynamicModel": "Job 1", "jobDescription": "The job is described here.","jobSpecification":
     * "attachment link is provided here", "notes":
     * "This is a note about the title."}
     */
    public function createDynamicModelData($modelName, $dynamicModel)
    {
        try {
            $this->dynamicModelModel = $this->getModel($modelName, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_MODEL_NOT_FOUND'), null);
        }

        try {
            $validationResponse = ModelValidator::validate($this->dynamicModelModel, $dynamicModel, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('dynamicModelMessages.basic.ERR_CREATE'), $validationResponse);
            }
            $dynamicModel['isDelete'] = 0;
            $newDynamicModel = $this->store->insert($this->dynamicModelModel, $dynamicModel, true);

            return $this->success(201, Lang::get('dynamicModelMessages.basic.SUCC_CREATE'), $newDynamicModel);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_CREATE'), null);
        }
    }

    /**
     * Following function retrives all job titles.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Job Titles retrieved Successfully!",
     *      $data => [{"id": 1, dynamicModel": "Job 1", "jobDescription": "The job is described here."}, {"id": 2, dynamicModel": "John2"}]
     * ]
     */
    public function getAllDynamicModelData($modelName, $permittedFields, $options)
    {
        try {
            $this->dynamicModelModel = $this->getModel($modelName, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_MODEL_NOT_FOUND'), null);
        }

        try {
            $filtereddynamicModel = $this->store->getAll(
                $this->dynamicModelModel,
                $permittedFields,
                $options,
                [],
                [['isDelete', '=', false]]
            );
            return $this->success(200, Lang::get('dynamicModelMessages.basic.SUCC_ALL_RETRIVE'), $filtereddynamicModel);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Job Title for a provided id.
     *
     * @param $id job title id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Job Titles retrieved Successfully!",
     *      $data => {"id": 1, dynamicModel": "Job 1", "jobDescription": "The job is described here."}
     * ]
     */
    public function getDynamicModelData($modelName, $id)
    {
        try {
            $this->dynamicModelModel = $this->getModel($modelName, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_MODEL_NOT_FOUND'), null);
        }

        try {
            $dynamicModel = $this->store->getFacade()::table($this->dynamicModelModel->getName())->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dynamicModel)) {
                return $this->error(404, Lang::get('dynamicModelMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), $dynamicModel);
            }

            return $this->success(200, Lang::get('dynamicModelMessages.basic.SUCC_SINGLE_RETRIVE'), $dynamicModel);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Job Title for a provided keyword.
     *
     * @param $id job title id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "Engineer"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Job Titles retrieved Successfully!",
     *      $data => {"id": 1, dynamicModel": "Job 1", "jobDescription": "The job is described here."}
     * ]
     */
    public function getDynamicModelDataListByKeyword($modelName, $keyword)
    {
        try {
            $this->dynamicModelModel = $this->getModel($modelName, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_MODEL_NOT_FOUND'), null);
        }

        try {
            $dynamicModel = $this->store->getFacade()::table($this->dynamicModelModel->getName())->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('dynamicModelMessages.basic.SUCC_ALL_RETRIVE'), $dynamicModel);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function updates a job title.
     *
     * @param $id job title id
     * @param $job title array containing job data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Job title Updated",
     *      $data => {"id": 1, dynamicModel": "Job 1", "jobDescription": "The job is described here."} // has a similar set of data as entered to updating user.
     *
     */
    public function updateDynamicModelData($modelName, $id, $dynamicModel)
    {
        try {
            $this->dynamicModelModel = $this->getModel($modelName, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_MODEL_NOT_FOUND'), null);
        }

        try {
            $validationResponse = ModelValidator::validate($this->dynamicModelModel, $dynamicModel, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('dynamicModelMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbDynamicModel = $this->store->getFacade()::table($this->dynamicModelModel->getName())->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbDynamicModel)) {
                return $this->error(404, Lang::get('dynamicModelMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), $dynamicModel);
            }

            if (empty($dynamicModel['name'])) {
                return $this->error(400, Lang::get('dynamicModelMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }

            $dynamicModel['isDelete'] = $dbDynamicModel->isDelete;
            $result = $this->store->updateById($this->dynamicModelModel, $id, $dynamicModel);

            if (!$result) {
                return $this->error(502, Lang::get('dynamicModelMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('dynamicModelMessages.basic.SUCC_UPDATE'), $dynamicModel);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id dynamicModel id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "dynamicModel deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteDynamicModelData($modelName, $id)
    {
        try {
            $this->dynamicModelModel = $this->getModel($modelName, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_MODEL_NOT_FOUND'), null);
        }

        try {
            $dbDynamicModel = $this->store->getById($this->dynamicModelModel, $id);
            if (is_null($dbDynamicModel)) {
                return $this->error(404, Lang::get('dynamicModelMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), null);
            }

            $recordExist = $this->store->getRelationallyDependentRecords($this->dynamicModelModel, $id);

            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('dynamicModelMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table($this->dynamicModelModel->getName())->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('dynamicModelMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a dynamicModel.
     *
     * @param $id dynamicModel id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "dynamicModel deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteDynamicModelData($modelName, $id)
    {
        try {
            $this->dynamicModelModel = $this->getModel($modelName, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_MODEL_NOT_FOUND'), null);
        }

        try {
            $dbDynamicModel = $this->store->getById($this->dynamicModelModel, $id);
            if (is_null($dbDynamicModel)) {
                return $this->error(404, Lang::get('dynamicModelMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), null);
            }

            $this->store->deleteById($this->dynamicModelModel, $id);

            return $this->success(200, Lang::get('dynamicModelMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('dynamicModelMessages.basic.ERR_DELETE'), null);
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\Util;
use App\Traits\JsonModelReader;

/**
 * Name: JobCategoryService
 * Purpose: Performs tasks related to the User model.
 * Description: JobCategoryService class is called by the JobtitleController where the requests related
 * to jobTitle Model (basic operations and others).
 * Module Creator: Tharindu Darshana
 */
class JobCategoryService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $jobCategoryModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->jobCategoryModel = $this->getModel('jobCategory', true);
    }


    /**
     * Following function creates a job category. THe job category details that are provided in the Request
     * are extracted and saved to the jobCategory table in the database. job category's id is auto genarated.
     *
     * @param $user array containing the user data
     * @return int | String | array
     *
     * Usage:
     * $user => ["name": "Junior"]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Job category created Successfully!",
     * $data => {"id": 1, name": "Junior"}
     *  */

    public function createJobCategory($jobTitle)
    {
        try {
            $validationResponse = ModelValidator::validate($this->jobCategoryModel, $jobTitle, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('jobCategoryMessages.basic.ERR_CREATE'), $validationResponse);
            }
            $jobTitle['isDelete'] = 0;
            $newJobTitle = $this->store->insert($this->jobCategoryModel, $jobTitle, true);

            return $this->success(201, Lang::get('jobCategoryMessages.basic.SUCC_CREATE'), $newJobTitle);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobCategoryMessages.basic.ERR_CREATE'), null);
        }
    }


    /**
     * Following function retrives all job categories.
     *
     * @return int | String | array
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Job Categories retrieved Successfully!",
     *      $data => [{"id": 1, name": "Junior"}]
     * ]
     */
    public function getAllJobCategory($permittedFields, $options)
    {
        try {
            $filteredjobTitle = $this->store->getAll(
                $this->jobCategoryModel,
                $permittedFields,
                $options,
                [],
                [['isDelete', '=', false]]
            );
            return $this->success(200, Lang::get('jobCategoryMessages.basic.SUCC_ALL_RETRIVE'), $filteredjobTitle);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobCategoryMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Job Title for a provided id.
     *
     * @param $id job category id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Job Category retrieved Successfully!",
     *      $data => {"id": 1, name": "Junior"}
     * ]
     */
    public function getJobCategory($id)
    {
        try {
            $jobCategory = $this->store->getFacade()::table('jobCategory')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($jobCategory)) {
                return $this->error(404, Lang::get('jobCategoryMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), $jobCategory);
            }

            return $this->success(200, Lang::get('jobCategoryMessages.basic.SUCC_SINGLE_RETRIVE'), $jobCategory);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobCategoryMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /**
     * Following function retrives a single Job Title for a provided keyword.
     *
     * @param $id job title id
     * @return int | String | array
     *
     * Usage:
     * $keyword => "Junior"
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "All Job Categories retrieved Successfully!",
     *      $data => {"id": 1, name": "Junior"}
     * ]
     */
    public function getJobCategoryByKeyword($keyword)
    {
        try {
            $jobTitle = $this->store->getFacade()::table('jobCategory')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('jobCategoryMessages.basic.SUCC_ALL_RETRIVE'), $jobTitle);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobCategoryMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function updates a job category.
     *
     * @param $id job category id
     * @param $job category array containing job data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Job title Updated",
     *      $data => {"id": 1, name": "Junior"}
     *
     */
    public function updateJobCategory($id, $jobCategory)
    {
        try {
            $validationResponse = ModelValidator::validate($this->jobCategoryModel, $jobCategory, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('jobCategoryMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbJobTitle = $this->store->getFacade()::table('jobCategory')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbJobTitle)) {
                return $this->error(404, Lang::get('jobCategoryMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), $jobCategory);
            }

            if (empty($jobCategory['name'])) {
                return $this->error(400, Lang::get('jobCategoryMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }

            $jobCategory['isDelete'] = $dbJobTitle->isDelete;
            $result = $this->store->updateById($this->jobCategoryModel, $id, $jobCategory);

            if (!$result) {
                return $this->error(502, Lang::get('jobCategoryMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('jobCategoryMessages.basic.SUCC_UPDATE'), $jobCategory);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobCategoryMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id jobCategory id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "job category deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteJobCategory($id)
    {
        try {
            $dbJobTitle = $this->store->getById($this->jobCategoryModel, $id);
            if (is_null($dbJobTitle)) {
                return $this->error(404, Lang::get('jobCategoryMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), null);
            }

            $recordExist = Util::checkRecordsExist($this->jobCategoryModel, $id);

            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('jobCategoryMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('jobCategory')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('jobCategoryMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobCategoryMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a jobTitle.
     *
     * @param $id job category id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "job category deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteJobCategory($id)
    {
        try {
            $dbJobTitle = $this->store->getById($this->jobCategoryModel, $id);
            if (is_null($dbJobTitle)) {
                return $this->error(404, Lang::get('jobCategoryMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), null);
            }

            $this->store->deleteById($this->jobCategoryModel, $id);

            return $this->success(200, Lang::get('jobCategoryMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobCategoryMessages.basic.ERR_DELETE'), null);
        }
    }
}

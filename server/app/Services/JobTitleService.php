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
 * Name: JobTitleService
 * Purpose: Performs tasks related to the User model.
 * Description: JobTitleService class is called by the JobtitleController where the requests related
 * to jobTitle Model (basic operations and others).
 * Module Creator: Chalaka
 */
class JobTitleService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $jobTitleModel;

    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->jobTitleModel = $this->getModel('jobTitle', true);
    }


    /**
     * Following function creates a job title. THe job title details that are provided in the Request
     * are extracted and saved to the jobTitle table in the database. job title's id is auto genarated.
     *
     * @param $user array containing the user data
     * @return int | String | array
     *
     * Usage:
     * $user => ["jobTitle": "Job 1", "jobDescription": "The job is described here.","jobSpecification": "attachment link is provided here", "notes": "This is a note about the title."]
     *
     * Sample output:
     * $statusCode => 200,
     * $message => "Job title created Successfully!",
     * $data => {"id": 1, jobTitle": "Job 1", "jobDescription": "The job is described here.","jobSpecification":
     * "attachment link is provided here", "notes":
     * "This is a note about the title."}
     *  */

    public function createJobTitle($jobTitle)
    {
        try {
            $validationResponse = ModelValidator::validate($this->jobTitleModel, $jobTitle, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('jobTitleMessages.basic.ERR_CREATE'), $validationResponse);
            }
            $jobTitle['isDelete'] = 0;
            $newJobTitle = $this->store->insert($this->jobTitleModel, $jobTitle, true);

            return $this->success(201, Lang::get('jobTitleMessages.basic.SUCC_CREATE'), $newJobTitle);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobTitleMessages.basic.ERR_CREATE'), null);
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
     *      $data => [{"id": 1, jobTitle": "Job 1", "jobDescription": "The job is described here."}, {"id": 2, jobTitle": "John2"}]
     * ]
     */
    public function getAllJobTitles($permittedFields, $options)
    {
        try {
            $filteredjobTitle = $this->store->getAll(
                $this->jobTitleModel,
                $permittedFields,
                $options,
                [],
                [['isDelete', '=', false]]
            );
            return $this->success(200, Lang::get('jobTitleMessages.basic.SUCC_ALL_RETRIVE'), $filteredjobTitle);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobTitleMessages.basic.ERR_ALL_RETRIVE'), null);
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
     *      $data => {"id": 1, jobTitle": "Job 1", "jobDescription": "The job is described here."}
     * ]
     */
    public function getJobTitle($id)
    {
        try {
            $jobTitle = $this->store->getFacade()::table('jobTitle')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($jobTitle)) {
                return $this->error(404, Lang::get('jobTitleMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), $jobTitle);
            }

            return $this->success(200, Lang::get('jobTitleMessages.basic.SUCC_SINGLE_RETRIVE'), $jobTitle);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobTitleMessages.basic.ERR_SINGLE_RETRIVE'), null);
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
     *      $data => {"id": 1, jobTitle": "Job 1", "jobDescription": "The job is described here."}
     * ]
     */
    public function getJobTitleByKeyword($keyword)
    {
        try {
            $jobTitle = $this->store->getFacade()::table('jobTitle')->where('name', 'like', '%' . $keyword . '%')->where('isDelete', false)->get();

            return $this->success(200, Lang::get('jobTitleMessages.basic.SUCC_ALL_RETRIVE'), $jobTitle);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobTitleMessages.basic.ERR_ALL_RETRIVE'), null);
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
     *      $data => {"id": 1, jobTitle": "Job 1", "jobDescription": "The job is described here."} // has a similar set of data as entered to updating user.
     *
     */
    public function updateJobTitle($id, $jobTitle)
    {
        try {
            $validationResponse = ModelValidator::validate($this->jobTitleModel, $jobTitle, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('jobTitleMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbJobTitle = $this->store->getFacade()::table('jobTitle')->where('id', $id)->where('isDelete', false)->first();
            if (is_null($dbJobTitle)) {
                return $this->error(404, Lang::get('jobTitleMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), $jobTitle);
            }

            if (empty($jobTitle['name'])) {
                return $this->error(400, Lang::get('jobTitleMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }

            $jobTitle['isDelete'] = $dbJobTitle->isDelete;
            $result = $this->store->updateById($this->jobTitleModel, $id, $jobTitle);

            if (!$result) {
                return $this->error(502, Lang::get('jobTitleMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('jobTitleMessages.basic.SUCC_UPDATE'), $jobTitle);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobTitleMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function sets the isDelete to false.
     *
     * @param $id jobTitle id
     * @param $JobTitle array containing JobTitle data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "jobTitle deleted successfully.",
     *      $data => null
     *
     */
    public function softDeleteJobTitle($id)
    {
        try {
            $dbJobTitle = $this->store->getById($this->jobTitleModel, $id);
            if (is_null($dbJobTitle)) {
                return $this->error(404, Lang::get('jobTitleMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), null);
            }

            $recordExist = Util::checkRecordsExist($this->jobTitleModel, $id);

            if (!empty($recordExist)) {
                return $this->error(502, Lang::get('jobTitleMessages.basic.ERR_NOTALLOWED'), null);
            }
            $this->store->getFacade()::table('jobTitle')->where('id', $id)->update(['isDelete' => true]);

            return $this->success(200, Lang::get('jobTitleMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobTitleMessages.basic.ERR_DELETE'), null);
        }
    }

    /**
     * Following function deletes a jobTitle.
     *
     * @param $id jobTitle id
     * @param $JobTitle array containing JobTitle data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "jobTitle deleted successfully.",
     *      $data => null
     *
     */
    public function hardDeleteJobTitle($id)
    {
        try {
            $dbJobTitle = $this->store->getById($this->jobTitleModel, $id);
            if (is_null($dbJobTitle)) {
                return $this->error(404, Lang::get('jobTitleMessages.basic.ERR_NONEXISTENT_JOB_TITLE'), null);
            }

            $this->store->deleteById($this->jobTitleModel, $id);

            return $this->success(200, Lang::get('jobTitleMessages.basic.SUCC_DELETE'), null);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('jobTitleMessages.basic.ERR_DELETE'), null);
        }
    }
}

<?php

namespace App\Services;

use App\Library\FileStore;
use Log;
use Exception;
// use App\Library\Facades\Store;
use App\Library\Store;
use App\Library\JsonModel;
use App\Library\Interfaces\ModelReaderInterface;
use App\Library\Session;
use App\Traits\JsonModelReader;
use \Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use App\Library\ModelValidator;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;

class FormTemplateService extends BaseService
{
    use JsonModelReader;
    private $store;
    private $fileStore;
    private $session;
    private $formTemplateModel;
    private $employeeJobModel;
    private $userRoleModel;

    public function __construct(Store $store, Session $session, FileStore $fileStore)
    {
        $this->store = $store;
        $this->session = $session;
        $this->fileStore = $fileStore;
        $this->formTemplateModel =  $this->getModel('formTemplate', true);
        $this->employeeJobModel =  $this->getModel('employeeJob', true);
        $this->userRoleModel =  $this->getModel('userRole', true);
    }

    /**
     * Following function retrieves a form template for a provided template id.
     *
     * @param $id template id
     * @return int | String | array
     *
     * Usage:
     * $id => 1
     *
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "Form Template retrieved Successfully!",
     *      $data => {"name": "Evaluation Form", ...}
     * ]
     */
    public function getFormTemplate($id)
    {
        try {
            $formTemplate = $this->store->getById(
                $this->formTemplateModel,
                $id
            );

            if (empty($formTemplate)) {
                return $this->error(404, Lang::get('formTemplateMessages.basic.ERR_NOT_EXIST'), null);
            }

            return $this->success(200, Lang::get('formTemplateMessages.basic.SUCC_GET'), $formTemplate);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('formTemplateMessages.basic.ERR_GET'), null);
        }
    }

    /**
     * Following function retrieves all templates.
     * Sample output:
     * [
     *      $statusCode => 200,
     *      $message => "message: "All templates retrieved Successfully.",
     *      $data => data: [
     *  0: {id: 1, name: "Test Form", status: "Unpublished", type: "FEEDBACK", ...}
     * ]
     */
    public function getAllFormTemplates($permittedFields, $options)
    {
        try {
            $templates = $this->store->getFacade()::table($this->formTemplateModel->getName());
            
            $templates->where('isDelete', false);
            $filterData = json_decode($options["filterBy"], true);

            if (!empty($filterData) && array_key_exists("name", $filterData))
                $templates->where('name', 'LIKE', '%' . $filterData['name'] . '%');

            $templates = $templates->orderBy('createdAt', 'desc')->get();

            $company = DB::table('company')->first('timeZone');
            $timeZone = $company->timeZone;
            foreach ($templates as $key => $template) {
                $template = (array) $template;
                $templates[$key]->createdAt =  $this->getFormattedDateForList($template['createdAt'], $timeZone);
            }
            return $this->success(200, Lang::get('formTemplateMessages.basic.SUCC_GETALL'), $templates);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('formTemplateMessages.basic.ERR_GETALL'), null);
        }
    }


    /**
     * Following function return the formatted date for given time stamp
     *
     * @return | String 
     *  */
    private function getFormattedDateForList($date, $timeZone)
    {
        try {
            $formattedDate = '-';
            if (!empty($date) && $date !== '-' && $date !== '0000-00-00 00:00:00') {
                
                $formattedDate = Carbon::parse($date, 'UTC')->copy()->tz($timeZone);
                $approvedAtArr = explode(' ', $formattedDate);
                if (!empty($approvedAtArr) && sizeof($approvedAtArr) >= 2) {
                    $formattedTime = Carbon::parse($approvedAtArr[1]);

                    $formattedDate = Carbon::parse($approvedAtArr[0])->format('d-m-Y') . ' at ' . $formattedTime->format('g:i A');
                }
            }
            
            return $formattedDate;
        } catch (\Exception $e) {
            echo 'invalid date';
        }
    }



    /**
     * Following function creates a template.
     * message: "Template created Successfully."
     */
    public function createFormTemplate($formTemplate)
    {
        try {

            $validationResponse = ModelValidator::validate($this->formTemplateModel, $formTemplate, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('departmentMessages.basic.ERR_CREATE'), $validationResponse);
            }

            $newFormTemplate = $this->store->insert($this->formTemplateModel, $formTemplate, true);

            if (!$newFormTemplate) {
                return $this->error(502, Lang::get('formTemplateMessages.basic.ERR_CREATE'), $notice);
            }

            return $this->success(200, Lang::get('formTemplateMessages.basic.SUCC_CREATE'), $newFormTemplate);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    /**
     * Following function updates a template.
     *
     * @param $id > template id
     * @param $template array containing template data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Template updated Successfully",
     *      $data => {"name": "Test Form", ...} // has a similar set of data as entered to create template.
     */
    public function updateFormTemplate($id, $template)
    {
        try {
            $existingTemplate = $this->store->getById($this->formTemplateModel, $id);

            if (empty($existingTemplate)) {
                return $this->error(404, Lang::get('formTemplateMessages.basic.ERR_NOT_EXIST'), null);
            }

            
            $result = $this->store->updateById($this->formTemplateModel, $id, $template, true);

            if (!$result) {
                return $this->error(502, Lang::get('formTemplateMessages.basic.ERR_UPDATE'), $result);
            }

            return $this->success(200, Lang::get('formTemplateMessages.basic.SUCC_UPDATE'), $result);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('formTemplateMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function updates a template status.
     *
     * @param $notice array containing template status data
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Template updated Successfully",
     */
    public function updateFormTemplateStatus($data)
    {
        try {
            $existingTemplate = $this->store->getById($this->formTemplateModel, $data['id']);

            if (empty($existingTemplate)) {
                return $this->error(404, Lang::get('formTemplateMessages.basic.ERR_NOT_EXIST'), null);
            }

            $existingTemplate->status = $data['status'];

            
            $result = $this->store->updateById($this->formTemplateModel, $data['id'], ['status' => $data['status']], true);

            if (!$result) {
                return $this->error(502, Lang::get('formTemplateMessages.basic.ERR_UPDATE'), $result);
            }

            return $this->success(200, Lang::get('formTemplateMessages.basic.SUCC_UPDATE'), []);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('formTemplateMessages.basic.ERR_UPDATE'), null);
        }
    }

    /**
     * Following function delete a template.
     *
     * @param $id template id
     * @return int | String | array
     *
     * Sample output:
     *      $statusCode => 200,
     *      $message => "Template deleted Successfully!",
     *      $data => {"name": "Test Form", ...}
     */
    public function deleteFormTemplate($id)
    {
        try {
            $existingTemplate = $this->store->getById($this->formTemplateModel, $id);

            if (empty($existingTemplate)) {
                return $this->error(404, Lang::get('formTemplateMessages.basic.ERR_NOT_EXIST'), null);
            }

            $result = $this->store->deleteById($this->formTemplateModel, $id, true);

            if (!$result) {
                return $this->error(502, Lang::get('formTemplateMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('formTemplateMessages.basic.SUCC_DELETE'), $existingTemplate);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, Lang::get('formTemplateMessages.basic.ERR_DELETE'), null);
        }
    }

    public function getFormTemplateInstance($instanceHash)
    {
        try {
            $formTemplateInstance = $this->store->getFacade()::table('formTemplateInstance')->where('hash', '=', $instanceHash)->first();

            if (empty($formTemplateInstance)) {
                return $this->error(404, Lang::get('formTemplateMessages.basic.ERR_FORM_INSTANCE_NOT_EXIST'), null);
            }

            // $requestedEmployee = isset($this->session->getEmployee()->id) ? $this->session->getEmployee()->id : null;

            // if ($requestedEmployee != $formTemplateInstance->authorizedEmployeeId) {
            //     return $this->error(403, Lang::get('formTemplateMessages.basic.ERR_FORM_INSTANCE_PERMISSION'), null);
            // }

            return $this->success(200, Lang::get('formTemplateMessages.basic.FORM_INSTANCE_SUCC_GET'), $formTemplateInstance);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('formTemplateMessages.basic.FORM_INSTANCE_ERR_GET'), null);
        }
    }

    public function updateFormTemplateInstance($id, $data)
    {
        try {
            $formTemplateInstance = $this->store->getFacade()::table('formTemplateInstance')->where('id', '=', $id)->first();

            if (empty($formTemplateInstance)) {
                return $this->error(404, Lang::get('formTemplateMessages.basic.ERR_FORM_INSTANCE_NOT_EXIST'), null);
            }

            $requestedEmployee = $this->session->getEmployee()->id;

            if ($requestedEmployee != $formTemplateInstance->authorizedEmployeeId) {
                return $this->error(403, Lang::get('formTemplateMessages.basic.ERR_FORM_INSTANCE_PERMISSION'), null);
            }

            if (in_array($formTemplateInstance->status, ['COMPLETED', 'CANCELED'])) {
                return $this->error(403, Lang::get('formTemplateMessages.basic.ERR_FORM_INSTANCE_EDIT'), null);
            }

            $instanceData = [
                'response' => $data['values'],
                'status' => 'COMPLETED',
                'updatedBy' => $requestedEmployee
            ];

            $this->store->getFacade()::table('formTemplateInstance')->where('id', '=', $id)->update($instanceData);

            return $this->success(200, Lang::get('formTemplateMessages.basic.FORM_INSTANCE_SUCC_UPDATE'), $id);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('formTemplateMessages.basic.FORM_INSTANCE_ERR_UPDATE'), null);
        }
    }

    public function getFormTemplateJobInstances($jobId)
    {
        try {
            $formTemplateJobInstances = $this->store->getFacade()::table('employeeJobformTemplateInstance')
                ->join('formTemplateInstance', 'formTemplateInstance.id', '=', 'employeeJobformTemplateInstance.formTemplateInstanceId')
                ->where('employeeJobformTemplateInstance.employeeJobId', $jobId)->get();

            return $this->success(200, Lang::get('formTemplateMessages.basic.FORM_INSTANCE_SUCC_GET'), $formTemplateJobInstances);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(500, Lang::get('formTemplateMessages.basic.FORM_INSTANCE_ERR_GET'), null);
        }
    }
}

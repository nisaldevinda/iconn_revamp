<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\RelationshipType;
use App\Traits\JsonModelReader;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use Illuminate\Support\Facades\DB;

/**
 * Name: EmailTemplateService
 * Purpose: Performs tasks related to the Email Template model.
 */
class EmailTemplateService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $emailTemplateModel;
    private $emailTemplateContentModel;
    private $user;
    private $role;
    private $location;
    private $department;

    public function __construct(Store $store, UserService $user, LocationService $location, UserRoleService $role, DepartmentService $department)
    {
        $this->store = $store;
        $this->emailTemplateModel = $this->getModel('emailTemplate', true);
        $this->emailTemplateContentModel = $this->getModel('emailTemplateContent', true);
        $this->user = $user;
        $this->location = $location;
        $this->department = $department;
        $this->role = $role;

    }

    /**
     * Following function create a Email Template.
     * 
     * @param $data array of email template data
     * 
     * Usage:
     * $data => ["name": "Male"]
     * 
     * Sample output:
     * $statusCode => 201,
     * $message => "Email Template created Successuflly",
     * $data => {"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"}
     *  */

    public function createEmailTemplate($data)
    {
        try {
            $validationResponse = ModelValidator::validate($this->emailTemplateModel, $data, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('emailTemplateMessages.basic.ERR_CREATE'), $validationResponse);
            }
            if($data['emailMessage']=="newTemplate"){
                $templateContent['templateName']=$data['alertName'];
                $templateContent['content']=$data['content'];

                $emailTemplateContent = $this->store->insert($this->emailTemplateContentModel, $templateContent, true);
                $data['contentId']=$emailTemplateContent['id'];
                $emailTemplate = $this->store->insert($this->emailTemplateModel, $data, true);

            }
            else{
                $emailTemplate = $this->store->insert($this->emailTemplateModel, $data, true);

            }

            return $this->success(201, Lang::get('emailTemplateMessages.basic.SUCC_CREATE'), $emailTemplate);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('emailTemplateMessages.basic.ERR_CREATE'), null);
        }
    }

    /** 
     * Following function retrive Email Template by id.
     * 
     * @param $id email id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Email Template retrieved Successfully",
     *      $data => {"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"}
     * ]
     */
    public function getEmailTemplate($id)
    {
        $db = $this->store->getFacade();
        try {
            $emailTemplate = $db::table('emailTemplate')->where('id', $id)->first();

            if (is_null($emailTemplate)) {
                return $this->error(404, Lang::get('emailTemplateMessages.basic.ERR_NONEXISTENT'), $emailTemplate);
            }

            $emailTemplate->from = json_decode($emailTemplate->from, true);
            $emailTemplate->to = json_decode($emailTemplate->to, true);
            $emailTemplate->cc = json_decode($emailTemplate->cc, true);
            $emailTemplate->bcc = json_decode($emailTemplate->bcc, true);
            $emailTemplate->status =$emailTemplate->status ? true : false;
            $emailTemplate->nextPerformActions =json_decode($emailTemplate->nextPerformActions, true);


            return $this->success(200, Lang::get('emailTemplateMessages.basic.SUCC_SINGLE_RETRIVE'), $emailTemplate);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('emailTemplateMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }
    
    /** 
     * Following function retrive Email content Template by id.
     * 
     * @param $id email id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Email Template retrieved Successfully",
     *      $data => {"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"}
     * ]
     */
    public function getEmailTemplateContent($id)
    {
        $db = $this->store->getFacade();
        try {
            $emailTemplate = $db::table('emailTemplateContent')->where('id', $id)->first();

            if (is_null($emailTemplate)) {
                return $this->error(404, Lang::get('emailTemplateMessages.basic.ERR_NONEXISTENT'), $emailTemplate);
            }

            return $this->success(200, Lang::get('emailTemplateMessages.basic.SUCC_SINGLE_RETRIVE'), $emailTemplate);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('emailTemplateMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }
    /** 
     * Following function retrive all email templates.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Email Templates retrieved Successfully.",
     *      $data => [{"name": "Template A", "description": "this is template A"}]
     * ] 
     */
    public function listEmailTemplates($permittedFields, $options)
    {
        try {
            $filteredTemplates = $this->store->getAll(
                $this->emailTemplateModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('emailTemplateMessages.basic.SUCC_ALL_RETRIVE'), $filteredTemplates);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('emailTemplateMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

     /** 
     * Following function retrive all email content templates.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Email Templates retrieved Successfully.",
     *      $data => [{"name": "Template A", "description": "this is template A"}]
     * ] 
     */
    public function listEmailContentTemplates($permittedFields, $options)
    {
        try {
            $filteredTemplates = $this->store->getAll(
                $this->emailTemplateContentModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('emailTemplateMessages.basic.SUCC_ALL_RETRIVE'), $filteredTemplates);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('emailTemplateMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
    /**
     * Following function updates email template.
     * 
     * @param $id email template id
     * @param $data array containing email template data
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "email template updated successfully.",
     *      $data => {"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"}
     * 
     */
    public function updateEmailTemplate($id, $data)
    {
        try {
                 
            $validationResponse = ModelValidator::validate($this->emailTemplateModel, $data, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('emailTemplateMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbTemplate = $this->store->getFacade()::table('emailTemplate')->where('id', $id)->first();
            if (is_null($dbTemplate)) {
                return $this->error(404, Lang::get('emailTemplateMessages.basic.ERR_NONEXISTENT'), $data);
            }

            if (empty($data['contentId'])) {
                return $this->error(400, Lang::get('emailTemplateMessages.basic.ERR_INVALID_CREDENTIALS'), null);
            }
            $emailTemplatecontent = $this->store->getFacade()::table('emailTemplateContent')->where('id', $data['contentId'])->first();
            unset($data['contentId']) ;
            $data['contentId'] = $emailTemplatecontent->id;
            $result = $this->store->updateById($this->emailTemplateModel, $id, $data);
            if (!empty($data['content']) && !empty($data['contentId'])) {
                $templateContent =[] ;
                $templateContent['content'] = $data['content'];
                $templateContentData = $this->store->updateById($this->emailTemplateContentModel, $data['contentId'],  $templateContent);
            }
           
            if (!$result) {
                return $this->error(502, Lang::get('emailTemplateMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('emailTemplateMessages.basic.SUCC_UPDATE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('emailTemplateMessages.basic.ERR_UPDATE'), null);
        }
    }

    /** 
     * Delete Email Template by id.
     * 
     * @param $id email id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Email Template deleted Successfully",
     *      $data => {id: 1}
     * ]
     */
    public function deleteEmailTemplate($id)
    {
        try {
            $dbTemplate = $this->store->getFacade()::table('emailTemplate')->where('id', $id)->first();
            if (is_null($dbTemplate)) {
                return $this->error(404, Lang::get('emailTemplateMessages.basic.ERR_DELETE'), null);
            }

            $result = $this->store->deleteById($this->emailTemplateModel, $id, true);

            if (!$result) {
                return $this->error(502, Lang::get('emailTemplateMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('emailTemplateMessages.basic.SUCC_DELETE'), ['id' => $id]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, $e->getMessage(), null);
        }
    }

    /** 
     * Following function retrive all email content templates.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Email Templates retrieved Successfully.",
     *      $data => [{"name": "Template A", "description": "this is template A"}]
     * ] 
     */
    public function getEmailTemplateRelateTreeData()
    {
        try {
            $data = [];
            $userRoleTreeData = [];
            $usersTreeData = [];
            $locationsTreeData = [];
            $departmentsTreeData = [];

            $commonTreeData = [
                [ "pId" => 0, "value" => 'allUsers', "title" => 'Users' ],
                [ "pId" => 1, "value" => 'allRoles', "title" => 'Roles' ],
                [ "pId" => 2, "value" => 'allDepartments', "title" => 'Departments' ],
                [ "pId" => 3, "value" => 'allLocations', "title" => 'Locations' ]
            ];
            $workflowRelateTreeData = [
                // [ "pId" => 0, "value" => 'manager', "title" => 'Manager', "isLeaf" => true ],
                [ "pId" => 0, "value" => 'employee', "title" => 'Employee', "isLeaf" => true ],
                // [ "pId" => 2, "value" => 'allRoles', "title" => 'Roles'],
                // [ "pId" => 3, "value" => 'nextActionPerformer', "title" => 'Next Action Performer'],
                [ "pId" => 1, "value" => 'nextLevelPerformer', "title" => 'Next Level Performer'],
                [ "pId" => 2, "value" => 'currentLevelPerformer', "title" => 'Current Level Performer'],
                // [ "pId" => 5, "value" => 'ActionPerformer', "title" => 'Next Action Performer'],
            ];

            //get user roles data
            $userRoles = $this->role->getAllUserRoles(['*'], null);
            // if ($userRoles['error']) {
            //     return $this->error(502, Lang::get('emailTemplateMessages.basic.ERR_UPDATE'), []);
            // }
            $userRoles = $userRoles['data'];
            
            //get locations data
            $locations = $this->location->getAllLocationsList(['*'], null);
            // if ($locations['error']) {
            //     return $this->error(502, Lang::get('emailTemplateMessages.basic.ERR_UPDATE'), []);
            // }
            $locations = $locations['data'];
            
            // get users data
            $userOptions = [
                "sorter" => null,
                "pageSize" => null,
                "current" => null,
                "filter" => null,
                "keyword" => null,
                "searchFields" => ['email', 'employeeName'],
            ];
            $users = $this->user->getAllUsers(['id'], $userOptions);
            // if ($users['error']) {
            //     return $this->error(502, Lang::get('emailTemplateMessages.basic.ERR_UPDATE'), []);
            // }
            $users = $users['data'];

            //get departments data
            $departments = $this->department->getAllDepartments(['*'], null);
            // if ($departments['error']) {
            //     return $this->error(502, Lang::get('emailTemplateMessages.basic.ERR_UPDATE'), []);
            // }
            $departments = $departments['data'];

            //prepare user roles tree data
            foreach ($userRoles as $key1 => $userRole) {
                $userRole = (array) $userRole;
                $temp = [
                    'value' => 'r'.$userRole['id'],
                    'title' => $userRole['title'],
                    'isLeaf' => true
                ];

                $userRoleTreeData[] = $temp;
            }

            //prepare user roles tree data
            foreach ($users as $key2 => $user) {
                $user = (array) $user;
                $temp = [
                    'value' => 'u'.$user['id'],
                    'title' => $user['employeeName'],
                    'isLeaf' => true
                ];
                
                $usersTreeData[] = $temp;
            }

            //prepare user roles tree data
            foreach ($locations as $key3 => $location) {
                $location = (array) $location;
                $temp = [
                    'value' => 'l'.$location['id'],
                    'title' => $location['name'],
                    'isLeaf' => true
                ];

                $locationsTreeData[] = $temp;
            }

            //prepare user roles tree data
            foreach ($departments as $key4 => $department) {
                $department = (array) $department;
                $temp = [
                    'value' => 'd'.$department['id'],
                    'title' => $department['name'],
                    'isLeaf' => true
                ];

                $departmentsTreeData[] = $temp;
            }

        
            //set Children data to common tree data
            foreach ($commonTreeData as $key5 => $commonArr) {
                switch ($commonArr['value']) {
                    case 'allUsers':
                        $commonTreeData[$key5]['children'] = $usersTreeData;
                        break;
                    case 'allRoles':
                        $commonTreeData[$key5]['children'] = $userRoleTreeData;
                        break;
                    case 'allDepartments':
                        $commonTreeData[$key5]['children'] = $departmentsTreeData;
                        break;
                    case 'allLocations':
                        $commonTreeData[$key5]['children'] = $locationsTreeData;
                        break;
                    default:
                        # code...
                        break;
                }
            }


            // Children data to workflow relate tree data
            foreach ($workflowRelateTreeData as $key6 => $workflowArr) {
                switch ($workflowArr['value']) {
                    case 'allRoles':
                        $workflowRelateTreeData[$key6]['children'] = $userRoleTreeData;
                        break;
                    default:
                        # code...
                        break;
                }
            }


            $data = [
                'commonTreeData' => $commonTreeData,
                'workflowRelateTreeData' => $workflowRelateTreeData
            ];
            
            // error_log(json_encode($data));
            
            return $this->success(200, Lang::get('emailTemplateMessages.basic.SUCC_ALL_RETRIVE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('emailTemplateMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrive all email content templates according to context id.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Email Templates retrieved Successfully.",
     *      $data => [{"name": "Template A", "description": "this is template A"}]
     * ] 
     */
    public function getEmailContentTemplatesByContextId($contextId)
    {
        try {
            $dbTemplateContents = $this->store->getFacade()::table('emailTemplateContent')
                ->select('*')
                ->leftJoin('emailTemplate', 'emailTemplateContent.id', '=', 'emailTemplate.contentId' )
                ->where('emailTemplateContent.isDelete', '=', false);

                
            if (!empty($contextId)) {
                $dbTemplateContents->where('emailTemplate.workflowContextId', '=', $contextId);
            }
            $data = $dbTemplateContents->get();
                
            $templates = [];
            foreach ($data as $key => $value) {
                $value = (array) $value;

                $temp = [
                    'id' => (int)$value['contentId'],
                    'templateName' => $value['templateName']
                ];
                
                $templates[] = $temp;
            }

            return $this->success(200, Lang::get('emailTemplateMessages.basic.SUCC_ALL_RETRIVE'), $templates);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('emailTemplateMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }
}

<?php

namespace App\Services;

use Log;
use \Illuminate\Support\Facades\Lang;
use App\Exceptions\Exception;
use App\Library\Store;
use App\Library\ModelValidator;
use App\Library\RelationshipType;
use App\Traits\JsonModelReader;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;
use App\Jobs\SendBulkLetterTemplate;
use Illuminate\Support\Facades\DB;
use App\Library\Session;
/**
 * Name: DocumentTemplateService
 * Purpose: Performs tasks related to the Document Template model.
 */
class DocumentTemplateService extends BaseService
{
    use JsonModelReader;

    private $store;

    private $documentTemplateModel;
    private $session;
    public function __construct(Store $store , Session $session)
    {
        $this->store = $store;
        $this->documentTemplateModel = $this->getModel('documentTemplate', true);
        $this->documentCategoryModel = $this->getModel('documentCategory', true);
        $this->session = $session;
    }

    /**
     * Following function create a Document Template.
     * 
     * @param $data array of document template data
     * 
     * Usage:
     * $data => ["name": "Male"]
     * 
     * Sample output:
     * $statusCode => 201,
     * $message => "Document Template created Successuflly",
     * $data => {"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"}
     *  */

    public function createDocumentTemplate($data)
    {
        try {
            $validationResponse = ModelValidator::validate($this->documentTemplateModel, $data, false);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('documentTemplateMessages.basic.ERR_CREATE'), $validationResponse);
            }

            // set default values to page settings
            $data['pageSettings']['marginBottom'] = empty($data['pageSettings']['marginBottom']) ? '0' : $data['pageSettings']['marginBottom'];
            $data['pageSettings']['marginLeft'] = empty($data['pageSettings']['marginLeft']) ? '0' : $data['pageSettings']['marginLeft'];
            $data['pageSettings']['marginRight'] = empty($data['pageSettings']['marginRight']) ? '0' : $data['pageSettings']['marginRight'];
            $data['pageSettings']['marginTop'] = empty($data['pageSettings']['marginTop']) ? '0' : $data['pageSettings']['marginTop'];
            $pageSize = isset($data['pageSettings']['pageSize']) ? $data['pageSettings']['pageSize'] : '';
            $data['pageSettings']['pageSize'] = in_array($pageSize, ['a4', 'a5', 'letter']) ? $pageSize : 'a4';

            $data['pageSettings'] = json_encode($data['pageSettings']);

            $documentTemplate = $this->store->insert($this->documentTemplateModel, $data, true);

            return $this->success(201, Lang::get('documentTemplateMessages.basic.SUCC_CREATE'), $documentTemplate);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_CREATE'), null);
        }
    }

    /** 
     * Following function retrive Document Template by id.
     * 
     * @param $id document id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Document Template retrieved Successfully",
     *      $data => {"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"}
     * ]
     */
    public function getDocumentTemplate($id)
    {
        $db = $this->store->getFacade();
        try {
            $docuemntTemplate = $db::table('documentTemplate')->where('id', $id)->first();

            if (is_null($docuemntTemplate)) {
                return $this->error(404, Lang::get('documentTemplateMessages.basic.ERR_NONEXISTENT'), $docuemntTemplate);
            }

            $docuemntTemplate->pageSettings = json_decode($docuemntTemplate->pageSettings, true);

            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_SINGLE_RETRIVE'), $docuemntTemplate);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /** 
     * Following function retrive all document templates.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Document Templates retrieved Successfully.",
     *      $data => [{"name": "Template A", "description": "this is template A"}]
     * ] 
     */
    public function listDocumentTemplates($permittedFields, $options)
    {
        try {
            $filteredTemplates = $this->store->getAll(
                $this->documentTemplateModel,
                $permittedFields,
                $options,
                [],
                [['isDelete','=',false]]
            );
            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_ALL_RETRIVE'), $filteredTemplates);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_ALL_RETRIVE'), null);
        }
    }

    /**
     * Following function updates document template.
     * 
     * @param $id document template id
     * @param $data array containing document template data
     * 
     * Sample output: 
     *      $statusCode => 200,
     *      $message => "docuemnt template updated successfully.",
     *      $data => {"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"}
     * 
     */
    public function updateDocumentTemplate($id, $data)
    {
        try {

            $validationResponse = ModelValidator::validate($this->documentTemplateModel, $data, true);
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('documentTemplateMessages.basic.ERR_UPDATE'), $validationResponse);
            }

            $dbTemplate = $this->store->getFacade()::table('documentTemplate')->where('id', $id)->first();
            if (is_null($dbTemplate)) {
                return $this->error(404, Lang::get('documentTemplateMessages.basic.ERR_NONEXISTENT'), $data);
            }

            if (empty($data['name'])) {
                return $this->error(400, Lang::get('documentTemplateMessages.basic.ERR_INVALID_NAME'), null);
            }

            // set default values to page settings
            $data['pageSettings']['marginBottom'] = empty($data['pageSettings']['marginBottom']) ? '0' : $data['pageSettings']['marginBottom'];
            $data['pageSettings']['marginLeft'] = empty($data['pageSettings']['marginLeft']) ? '0' : $data['pageSettings']['marginLeft'];
            $data['pageSettings']['marginRight'] = empty($data['pageSettings']['marginRight']) ? '0' : $data['pageSettings']['marginRight'];
            $data['pageSettings']['marginTop'] = empty($data['pageSettings']['marginTop']) ? '0' : $data['pageSettings']['marginTop'];
            $pageSize = isset($data['pageSettings']['pageSize']) ? $data['pageSettings']['pageSize'] : '';
            $data['pageSettings']['pageSize'] = in_array($pageSize, ['a4', 'a5', 'letter']) ? $pageSize : 'a4';

            $data['pageSettings'] = json_encode($data['pageSettings']);

            $result = $this->store->updateById($this->documentTemplateModel, $id, $data);

            if (!$result) {
                return $this->error(502, Lang::get('documentTemplateMessages.basic.ERR_UPDATE'), $id);
            }

            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_UPDATE'), $data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_UPDATE'), null);
        }
    }

    /** 
     * Delete Document Template by id.
     * 
     * @param $id document id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Document Template deleted Successfully",
     *      $data => {id: 1}
     * ]
     */
    public function deleteDocumentTemplate($id)
    {
        try {
            $dbTemplate = $this->store->getFacade()::table('documentTemplate')->where('id', $id)->first();
            if (is_null($dbTemplate)) {
                return $this->error(404, Lang::get('documentTemplateMessages.basic.ERR_DELETE'), null);
            }

            $result = $this->store->deleteById($this->documentTemplateModel, $id, true);

            if (!$result) {
                return $this->error(502, Lang::get('documentTemplateMessages.basic.ERR_DELETE'), $id);
            }

            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_DELETE'), ['id' => $id]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, $e->getMessage(), null);
        }
    }

    /** 
     * Following function retrive Document Template by id.
     * 
     * @param $id document id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Document Template retrieved Successfully",
     *      $data => {"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"}
     * ]
     */
    public function getEmployeeDocument($employeeId, $templateId)
    {
        try {
            $response = $this->generateDocumentContent($templateId, $employeeId);

            if ($response['error']) {
                return $this->error(404, $response['msg'], null);
            }

            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_SINGLE_RETRIVE'), $response['data']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /** 
     * Get document template with db values.
     * 
     * @param $templateId document template id
     * @param $employeeId employee id
     * 
     * Sample output: 
     * [
     *      error => false,
     *      msg => "success",
     *      data => "<h3>Hi smith !!!</h3>"
     * ]
     */
    public function generateDocumentContent($templateId, $employeeId)
    {
        try {
            $db = $this->store->getFacade();
            $docuemntTemplate = $db::table('documentTemplate')->where('id', $templateId)->where('isDelete', false)->first();

            if (is_null($docuemntTemplate)) {
                return ['error' => true, 'msg' => Lang::get('documentTemplateMessages.basic.ERR_NONEXISTENT'), 'data' => null];
            }

            $employee = $db::table('employee')->where('id', $employeeId)->first();

            if (is_null($employee)) {
                return ['error' => true, 'msg' => Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), 'data' => null];
            }

            $pageSettings = $docuemntTemplate->pageSettings = json_decode($docuemntTemplate->pageSettings, true);

            // get defined tokens in the content
            preg_match_all("/{#(.*?)#}/", $docuemntTemplate->content, $matches);

            // get tokens
            $placeHolders = $matches[0];
            $tokenNames = $matches[1];
            $notFilledTokens = $tokenNames;
            $filledValues = array_fill_keys($tokenNames, null);

            // get employee model
            $employeeModel = $this->getModel('employee', true);

            // get employee model tokens
            $employeeTokens = $employeeModel->getTemplateTokens();

            // get document input related to employee model
            $tokens = array_intersect($notFilledTokens, array_values($employeeTokens));

            // get employee data
            $employeeData = (array) $this->store->getById($employeeModel, $employeeId);

            // fill employee data
            array_walk($tokens, function ($token) use ($employeeTokens, $employeeData, &$filledValues) {
                $fieldName = array_search($token, $employeeTokens);
                $filledValues[$token] = $employeeData[$fieldName];
            });

            // remove employee model tokens
            $notFilledTokens = array_diff($notFilledTokens, $tokens);

            // check whether left to fill
            // this block handle one to one relation
            if (!empty($notFilledTokens)) {

                // get employee relations
                $employeeRelations = $employeeModel->getRelationsByRelationType(RelationshipType::HAS_ONE);

                $oneToOneModels = $employeeModel->getRelationalModels($employeeRelations);

                // get employee foreign keys
                $employeeForeignKeys = array_column($oneToOneModels, 'field');

                //to keep model token data
                $tokenModelData = [];

                array_walk($oneToOneModels, function ($model) use (&$tokenModelData, &$notFilledTokens, $employeeModel) {
                    $modelObj = $this->getModel($model['model'], true);
                    $modelTokens = $modelObj->getTemplateTokens();

                    foreach ($notFilledTokens as $token) {
                        if (in_array($token, array_values($modelTokens))) {
                            $tokenModelData[$model['model']] = isset($tokenModelData[$model['model']]) ? $tokenModelData[$model['model']] : [];
                            $foreignKey = $employeeModel->getForeignKey($model['field']);
                            array_push($tokenModelData[$model['model']], ['token' => $token, 'foreignKey' => $foreignKey]);
                        }
                    }
                });

                // featch data from db
                foreach ($tokenModelData as $model => $tokenData) {
                    // get foreign key value
                    $foreignKey = $tokenData[0]['foreignKey'];
                    // create model object
                    $modelObj = $this->getModel($model, true);
                    // get model template tokens
                    $modelTokens = $modelObj->getTemplateTokens();
                    // get foreign key value
                    $foreignKeyValue = isset($employeeData[$foreignKey]) ? $employeeData[$foreignKey] : null;
                    // get relevent record from db
                    $data = (array) $this->store->getById($modelObj, $foreignKeyValue);
                    // fill db data
                    foreach ($tokenData as $value) {
                        $token = $value['token'];
                        if (!is_null($foreignKeyValue)) {
                            $data = (array) $this->store->getById($modelObj, $foreignKeyValue);
                            $fieldName = array_search($token, $modelTokens);
                            $filledValues[$token] = $data[$fieldName];
                        } else {
                            $filledValues[$token] = '-';
                        }
                        // remove employee model tokens
                        $notFilledTokens = array_diff($notFilledTokens, [$token]);
                    }
                }
            }

            // handle one to many effective date considerable relations
            if (!empty($notFilledTokens)) {
                // get employee effective date considerable relations
                $oneToManyModels = $employeeModel->getEffectiveDateConsiderableModels();

                // get effective date considerable relation dependent models
                array_walk($oneToManyModels, function ($model) use (&$notFilledTokens, &$filledValues, $employeeId) {
                    $parentTokenModelData = [];

                    $modelObj = $this->getModel($model, true);
                    // get model template tokens
                    $modelTokens = $modelObj->getTemplateTokens();
                    foreach ($notFilledTokens as $token) {
                        $fieldName = array_search($token, $modelTokens);
                        if ($fieldName != false) {
                            $parentTokenModelData[$token] = $fieldName;
                            // remove parent model tokens                            
                            $notFilledTokens = array_diff($notFilledTokens, [$token]);
                        }
                    }

                    if (!empty($notFilledTokens)) {
                        $childTokenModelData = [];
                        // get dependent relations
                        $dependentRelations = $modelObj->getRelationsByRelationType(RelationshipType::HAS_ONE);
                        // get dependent models
                        $dependentModels = $modelObj->getRelationalModels($dependentRelations);
                        foreach ($dependentModels as $dependentModel) {
                            // create model object
                            $dependentModelObj = $this->getModel($dependentModel['model'], true);
                            // get model tokens
                            $dependentModelTokens = $dependentModelObj->getTemplateTokens();
                            foreach ($notFilledTokens as $token) {
                                if (in_array($token, array_values($dependentModelTokens))) {
                                    $childTokenModelData[$dependentModel['model']] = isset($childTokenModelData[$dependentModel['model']]) ? $childTokenModelData[$dependentModel['model']] : [];
                                    $foreignKey = $modelObj->getForeignKey($dependentModel['field']);
                                    array_push($childTokenModelData[$dependentModel['model']], ['token' => $token, 'foreignKey' => $foreignKey]);
                                    // remove child model tokens                             
                                    $notFilledTokens = array_diff($notFilledTokens, [$token]);
                                }
                            }
                        }
                    }

                    $parentData = [];
                    // get parent model data 
                    if (!empty($parentTokenModelData) || !empty($childTokenModelData)) {
                        // get data from db
                        $db = $this->store->getFacade();
                        // TODO:: this must move to store
                        $parentData = (array) $db::table($model)->where('employeeId', $employeeId)->latest('effectiveDate')->first();

                        foreach ($parentTokenModelData as $token => $fieldName) {
                            $filledValues[$token] = isset($parentData[$fieldName]) ? $parentData[$fieldName] : "-";
                        }
                    }

                    // get child model data if exist
                    if (!empty($childTokenModelData)) {

                        foreach ($childTokenModelData as $model => $modelData) {
                            foreach ($modelData as $data) {
                                $foreignKey = $data['foreignKey'];
                                $token = $data['token'];
                                // get foreign key db value
                                $foreignKeyValue = isset($parentData[$foreignKey]) ? $parentData[$foreignKey] : null;
                                // if foreign key value exist
                                if (!is_null($foreignKeyValue)) {
                                    // create model object
                                    $dependentModelObj = $this->getModel($model, true);
                                    // get model tokens
                                    $dependentModelTokens = $dependentModelObj->getTemplateTokens();
                                    // get dependent model data form db
                                    $childData = (array) $this->store->getById($dependentModelObj, $foreignKeyValue);
                                    $fieldName = array_search($token, $dependentModelTokens);
                                    $filledValues[$token] = $childData[$fieldName];
                                } else {
                                    $filledValues[$token] = "-";
                                }
                            }
                        }
                    }
                });
            }

            // remove duplicate tokens
            $placeHolders = array_unique($placeHolders);

            $templatePlaceHolders = [];

            array_walk($placeHolders, function($placeHolder) use (&$templatePlaceHolders, $filledValues) {
                $key = str_replace(['#','{','}'], '', $placeHolder);
                $templatePlaceHolders[$placeHolder] = isset($filledValues[$key]) ? $filledValues[$key] : "";
            });

            $content = str_replace(array_keys($templatePlaceHolders), array_values($templatePlaceHolders), $docuemntTemplate->content, $count);

            return ['error' => false, 'msg' => 'success', 'data' => ['name' => $docuemntTemplate->name, 'content' => $content, 'pageSettings' => $pageSettings]];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ['error' => true, 'msg' => $e->getMessage(), 'data' => null];
        }
    }

    /** 
     * Download employee document template as docx file.
     * 
     * @param $templateId document template id
     * @param $employeeId employee id
     * 
     * Sample output: 
     * [
     * ]
     */
    public function downloadEmployeeDocumentAsDocx($employeeId, $templateId, $content)
    {
        try {

            if (empty($content)) {
                $response = $this->generateDocumentContent($templateId, $employeeId);

                if ($response['error']) {
                    return $this->error(404, $response['msg'], null);
                }

                // get template content
                $content = $response['data']['content'];
                // get page settings
                $pageSettings = $response['data']['pageSettings'];

            } else {
                $db = $this->store->getFacade();
                $docuemntTemplate = $db::table('documentTemplate')->where('id', $templateId)->where('isDelete', false)->first();

                if (is_null($docuemntTemplate)) {
                    return ['error' => true, 'msg' => Lang::get('documentTemplateMessages.basic.ERR_NONEXISTENT'), 'data' => null];
                }

                $employee = $db::table('employee')->where('id', $employeeId)->first();

                if (is_null($employee)) {
                    return ['error' => true, 'msg' => Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), 'data' => null];
                }

                $pageSettings = $docuemntTemplate->pageSettings = json_decode($docuemntTemplate->pageSettings, true);
            }

            // get html page
            $html = $this->getHtmlPage($content, $pageSettings);

            // Creating the new document
            $phpWord = new PhpWord();

            $phpWord->setDefaultFontName('arial');
            $phpWord->setDefaultFontSize(10);

            // Adding an empty Section to the document
            $section = $phpWord->addSection();

            Html::addHtml($section, $html, true);

            Settings::setOutputEscapingEnabled(true);

            $objWriter = IOFactory::createWriter($phpWord, "Word2007");

            $fileName = Str::random(20) . "-doc.docx";

            $objWriter->save($fileName);

            $content = file_get_contents($fileName);

            unlink($fileName);  // remove temp file

            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_SINGLE_RETRIVE'), base64_encode($content));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /** 
     * Download employee document template as pdf file.
     * 
     * @param $templateId document template id
     * @param $employeeId employee id
     * 
     * Sample output: 
     * [
     * ]
     */
    public function downloadEmployeeDocumentAsPdf($employeeId, $templateId, $content)
    {
        try {

            if (empty($content)) {
                $response = $this->generateDocumentContent($templateId, $employeeId);

                if ($response['error']) {
                    return $this->error(404, $response['msg'], null);
                }

                // get template content
                $content = $response['data']['content'];
                // get page settings
                $pageSettings = $response['data']['pageSettings'];

            } else {
                $db = $this->store->getFacade();
                $docuemntTemplate = $db::table('documentTemplate')->where('id', $templateId)->where('isDelete', false)->first();

                if (is_null($docuemntTemplate)) {
                    return ['error' => true, 'msg' => Lang::get('documentTemplateMessages.basic.ERR_NONEXISTENT'), 'data' => null];
                }

                $employee = $db::table('employee')->where('id', $employeeId)->first();

                if (is_null($employee)) {
                    return ['error' => true, 'msg' => Lang::get('employeeMessages.basic.ERR_NOT_EXIST'), 'data' => null];
                }

                $pageSettings = $docuemntTemplate->pageSettings = json_decode($docuemntTemplate->pageSettings, true);
            }

            // get html page
            $html = $this->getHtmlPage($content, $pageSettings);

            $pdf = app()->make('dompdf.wrapper');
            $pdf->loadHTML($html);

            $output = $pdf->setPaper($pageSettings['pageSize'], 'portrait')->output();

            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_SINGLE_RETRIVE'), base64_encode($output));
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }

    /**
     * Get document template html 
     * 
     * @param $content template content
     * @param $pageSettings template settings
     * 
     * Sample output: <html><body>...</body></html>
     * 
     */
    private function getHtmlPage($content, $pageSettings)
    {
        $html = '<html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style>
                    .page-break {
                        page-break-after: always;
                    }

                    body {
                        font-family:"arial";
                        font-size:10pt;
                    }

                    @page { margin-top: ' . $pageSettings['marginTop'] . 'mm; margin-right: ' . $pageSettings['marginRight'] . 'mm; margin-bottom: ' . $pageSettings['marginBottom'] . 'mm; margin-left: ' . $pageSettings['marginLeft'] . 'mm;}
                </style>
            </head>
            <body>
            ' . $content . '
            </body>
        </html>';

        return str_replace("\n", "", $html);
    }

    /**
     * Following function create a Document Category.
     * 
     * @param $categoryData array of document category data
     * 
     * Usage:
     * $categoryData => ["name": "category A"]
     * 
     * Sample output:
     * $statusCode => 201,
     * $message => "Document category created Successuflly",
     * $categoryData => {"name": "category A"}
     */
    public function createCategory($categoryData) {
        try {
            $validationResponse = ModelValidator::validate($this->documentCategoryModel, $categoryData, false);
           
            if (!empty($validationResponse)) {
                return $this->error(400, Lang::get('documentTemplateMessages.basic.ERR_CATEGORY_VALIDATE'), $validationResponse);
            }

            $documentCategory = $this->store->insert($this->documentCategoryModel, $categoryData, true);

            return $this->success(201, Lang::get('documentTemplateMessages.basic.SUCC_CATEGORY_CREATE'),  $documentCategory);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_CREATE'), null);
        }
    }

    /** 
     * Following function retrive all document category.
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "All Document Category retrieved Successfully.",
     *      $data => [{ "id" : 1 ,"name": "category A"}, {"id":2,"name" : "category B" }]
     * ] 
     */
    public function getAllDocumentCategories()
    {
        try {
            $permittedFields =null;
            $options =null;
            $filteredDocuments = $this->store->getAll(
                $this->documentCategoryModel,
                $permittedFields,
                $options,
                [],
                []
            );
            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_ALL_CATEGORY_RETRIVE'), $filteredDocuments);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_ALL_CATEGORY_RETRIVE'), null);
        }
    }
    /** 
     * Following function retrive Document Template by categoryId.
     * 
     * @param $id documentCategory id
     * 
     * Usage:
     * $id => 1
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Document Template retrieved Successfully",
     *      $data => [{"name": "Template A", "description": "this is template A", content: "<p>Hi #first_name#</p>"} ,..]
     * ]
     */
    public function getDocumentTemplateList($id) {
        
        try {

            $docuemntTemplates = $this->store->getFacade()::table('documentTemplate')
              ->select('id','name')
              ->where('documentCategoryId', $id)
              ->get();
            
              return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_SINGLE_RETRIVE'), $docuemntTemplates);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), Lang::get('documentTemplateMessages.basic.ERR_SINGLE_RETRIVE'), null);
        }
    }
    /** 
     * Following function send letter templates to the selected employeeId.
     * 
     * @param $data array contain templateData
     * 
     * Usage:
     * templateId => 1,
     * documentCategoryId => 1,
     * audienceType => "ALL"
     * audienceData => {"employeeIds": ["2","3"]}
     * 
     * Sample output: 
     * [
     *      $statusCode => 200,
     *      $message => "Document Template sent  Successfully",
     *      $data => true
     * ]
     */
    public function generateBulkLetter($data) {
       try {
           
            $bulkLetterLogData = [
              'templateId' => $data['templateId'],
              'status' => "PENDING",
              'createdBy' => $this->session->getUser()->id
            ];

            $bulkLetterLogId = $this->store->getFacade()::table('bulkLetterLog')->insertGetId($bulkLetterLogData);

            $data['bulkLetterLogId'] = $bulkLetterLogId;
            if (!empty($data['templateId'] && !empty($data['audienceData']))) {
            
               dispatch(new SendBulkLetterTemplate($data));

            }
            
            return $this->success(200, Lang::get('documentTemplateMessages.basic.SUCC_SHARE_BULK_LETTER'), true);
        } catch(Exception $e) {
          Log::error($e->getMessage());
          return $this->error($e->getCode(), Lang::get('documentTemplateMessagess.basic.ERR_SHARE_BULK_LETTER'), null);
       }
    }

}

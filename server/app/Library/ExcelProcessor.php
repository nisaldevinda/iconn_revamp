<?php

namespace App\Library;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Library\Model;
use App\Library\Store;
use App\Traits\JsonModelReader;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Concerns\ToArray;
use App\Library\ModelValidator;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Log;
use App\Library\Session;
use App\Traits\ConfigHelper;
use App\Services\UserService;
use App\Traits\EmployeeHelper;
use Carbon\Carbon;

class ExcelProcessor  implements WithHeadings, WithEvents, ToArray
{
    protected $model;
    protected $store;
    protected $defintionStrcutre;
    protected $queryBuilder;
    protected $rowResults;
    private $validationFeilds;
    private $overallColMap = array(); // Overall Col Indexs
    private $excelHeadings = array();
    private $feildCount;
    private $modelData = [];
    private $specialModalOptionCellKey = 'TA';
    private $modelValidatorErrors = []; // restrcutred uploaded data from the excel sheet
    private $session;
    private $addedCount;
    private $addedEmployeeIds = []; // the array that consists of all the bulk uploaded employee ids

    use Exportable;
    use JsonModelReader;
    use ConfigHelper;
    use EmployeeHelper;

    public function __construct(Model $model, Session $session, Store $store, $downloadParams = [], $uploadParams = [])
    {
        $this->model = $model;
        $this->store = $store;
        $this->queryBuilder = $store->getFacade();
        $this->session = $session;

        if (!empty($downloadParams)) {
            $this->defintionStrcutre = $downloadParams['defintionStrcutre'];
            $this->feildCount = $downloadParams['feildCount'] + 1;
            $this->excelHeadings = $this->processHeadings();
        }
    }

    private function processHeadings()
    {
        try {
            $dynamicModel = $this->model->toArray();
            $feildKeys = array(); // Frontend Definition Generated Keys
            $templateContent = array(); // Needed data for to generate excel headings
            $excelHeadings = array(); // Final Mutated array to genrate excel sheet

            if (!empty($dynamicModel) && isset($dynamicModel)) {
                $hasFrontendDefinition = $dynamicModel['hasFrontEndDefinition'];

                if ($hasFrontendDefinition) {

                    // fetching the edit frontend definition content strcuture
                    $frontendDefinitionContent = (array) $this->queryBuilder::table('frontEndDefinition')
                        ->where('modelName', $dynamicModel['name'])
                        ->where('id', 2)
                        ->first();

                    $frontendDefintionStrcutres = json_decode($frontendDefinitionContent['structure']);

                    // Allocate feild keys from frontend definition
                    foreach ($frontendDefintionStrcutres as $index => $frontendStruct) {
                        $feildKeys[] = $frontendStruct->content;
                    }


                    // array_pop($feildKeys); // uncomment to disable the allow access feild set

                    $structuredFeildKeys = [];

                    foreach ($feildKeys as $feildKey) {
                        foreach ($feildKey as $feildKeyIndex => $newFeildKey) {
                            foreach ($newFeildKey->content as $feildContent) {
                                if (str_contains($feildContent, '.')) {
                                    $feildHeadings[]  =   explode(".", $feildContent);
                                    foreach ($feildHeadings as $feildHeading) {
                                        $structuredFeildKeys[] = $feildHeading[0];
                                    };
                                } else {
                                    $structuredFeildKeys[] = $feildContent;
                                }
                            }
                        }
                    }

                    // if a new feild key is added change the indexs accordinly
                    $structuredFeildKeys =  array_unique($structuredFeildKeys);
                    array_push($structuredFeildKeys, 'jobs');

                    // not needed keys
                    $unsetKeys = [
                        'initials',
                        // 'fullName',
                        // 'maidenName',
                        'bloodGroup',
                        // 'nicNumber',
                        'passportNumber',
                        'passportExpiryDate',
                        'drivingLicenceNumber',
                        'religion',
                        'nationality',
                        'race',
                        'residentialAddressStreet1',
                        'residentialAddressStreet2',
                        'residentialAddressCity',
                        'residentialAddressZip',
                        'residentialAddressCountry',
                        'residentialAddressState',
                        'permanentAddressCountry',
                        'permanentAddressState',
                        'facebookLink',
                        'linkedIn',
                        'twitter',
                        'instagram',
                        'pinterest',
                        'recentHireDate',
                        'payGrade',
                        'dependents',
                        'dateOfRegistration',
                        'certificateNumber',
                        'experiences',
                        'educations',
                        'competencies',
                        'emergencyContacts',
                        'documentTemplates',
                        'documents',
                        'jobTitle',
                        'employeeJourney',
                        'noticePeriod',
                        'retirementDate',
                        'contractRenewalDate',
                        'status',
                        'employeeSalarySection',
                        'homePhone'
                    ];

                    $tempStructuredFeildKey = [];

                    foreach ($structuredFeildKeys as $structuredFeildKeyIndex => $structuredFeildKey) {
                        if (!in_array($structuredFeildKey, $unsetKeys)) {
                            $tempStructuredFeildKey[] = $structuredFeildKey;
                        }
                    }

                    $structuredFeildKeys = $tempStructuredFeildKey;

                    // adding allow access keys
                    array_push($structuredFeildKeys, "allowAccess", "employeeRole", "managerRole");

                    $dynamicModelFeildKeys = $dynamicModel['fields'];

                    // Allocate feild definition content
                    foreach ($dynamicModelFeildKeys as $dynamicModelIndex => $dynamicModelFeildKey) {
                        foreach ($structuredFeildKeys as $feildKeyIndex => $feildKey) {
                            if ($dynamicModelFeildKey['name'] == $feildKey) {
                                $templateContent[] = $dynamicModelFeildKeys[$feildKey];
                            }
                        }
                    }



                    foreach ($templateContent as $templateData) {

                        // adding the headings to the excel sheet
                        switch ($templateData['type']) {
                            case 'timestamp':
                                $excelHeadings[] = [
                                    'headingName' => $templateData['name'],
                                    'validationType' => 'timestamp'
                                ];
                                break;
                            case 'model':
                                $relationType = $this->model->getRelationType($templateData['name']);
                                switch ($relationType) {
                                    case RelationshipType::HAS_ONE:
                                        $excelHeadings[] = [
                                            'headingName' => $templateData['name'],
                                            'validationType' => 'model'
                                        ];
                                        if (array_key_exists('modelFilters', $templateData)) {
                                            $filterNames = array_keys($templateData['modelFilters']);
                                            if (!empty($filterNames)) {
                                                $filterValues = $templateData['modelFilters'][$filterNames[0]];
                                                $feildOptions = $this->queryBuilder::table($templateData['modelName'])
                                                    ->select($templateData['enumLabelKey'])
                                                    ->where($filterNames[0], '=', $filterValues[0])
                                                    ->get();
                                                foreach ($feildOptions as $feildIndex => $feildOption) {
                                                    $feildOptionsArray[$feildIndex] = (array) $feildOptions[$feildIndex];
                                                    $this->modelData[$templateData['name']][] =
                                                        $feildOptionsArray[$feildIndex][$templateData['enumLabelKey']];
                                                }
                                            }
                                        } else {
                                            $feildOptions = $this->queryBuilder::table($templateData['modelName'])
                                                ->select($templateData['enumLabelKey'])
                                                ->get();
                                            foreach ($feildOptions as $feildIndex => $feildOption) {
                                                $feildOptionsArray[$feildIndex] = (array) $feildOptions[$feildIndex];
                                                $this->modelData[$templateData['modelName']][] =
                                                    $feildOptionsArray[$feildIndex][$templateData['enumLabelKey']];
                                            }
                                        }
                                        break;
                                    case RelationshipType::HAS_MANY:
                                        $foreignModel = $this->getModel($templateData['modelName'], true)->toArray();

                                        // filtering out the System value feilds
                                        foreach ($foreignModel['fields'] as $foreignModelFeildIndex => $foreignModelFeild) {
                                            if (array_key_exists('isSystemValue', $foreignModelFeild)) {
                                                unset($foreignModel['fields'][$foreignModelFeildIndex]);
                                            }
                                            // if (array_key_exists('calendar', $foreignModel['fields'])) {
                                            //     unset($foreignModel['fields']['calendar']);
                                            // }

                                            // deleting not needed feilds in the employee employement
                                            if ($templateData['modelName'] = 'employeeEmployment') {

                                                unset($foreignModel['fields']['effectiveDate']);
                                                unset($foreignModel['fields']['terminationType']);
                                                unset($foreignModel['fields']['terminationType']);
                                                unset($foreignModel['fields']['comment']);
                                                unset($foreignModel['fields']['terminationReason']);
                                                unset($foreignModel['fields']['rehireEligibility']);
                                            }

                                            if ($templateData['modelName'] = 'employeeJob') {
                                                // unset($foreignModel['fields']['jobTitle']);
                                                // unset($foreignModel['fields']['division']);
                                                // unset($foreignModel['fields']['reportsToEmployee']);
                                                // unset($foreignModel['fields']['functionalReportsToEmployee']);
                                                // unset($foreignModel['fields']['scheme']);
                                                unset($foreignModel['fields']['transferType']);
                                                unset($foreignModel['fields']['promotionType']);
                                                unset($foreignModel['fields']['confirmationReason']);
                                                unset($foreignModel['fields']['resignationType']);
                                                unset($foreignModel['fields']['confirmationRemark']);
                                                unset($foreignModel['fields']['transferReason']);
                                                unset($foreignModel['fields']['confirmationAction']);
                                                unset($foreignModel['fields']['resignationHandoverDate']);
                                                unset($foreignModel['fields']['attachmentId']);
                                                unset($foreignModel['fields']['lastWorkingDate']);
                                                unset($foreignModel['fields']['resignationReason']);
                                                unset($foreignModel['fields']['promotionReason']);
                                                unset($foreignModel['fields']['employeeJourneyType']);
                                                unset($foreignModel['fields']['rollbackReason']);
                                                unset($foreignModel['fields']['resignationNoticePeriodRemainingDays']);
                                                unset($foreignModel['fields']['department']);
                                                unset($foreignModel['fields']['division']);
                                            }
                                        }

                                        //   array_shift($foreignModel['fields']); // deleting the effective date key
                                        foreach ($foreignModel['fields'] as $feildsIndex => $feild) {
                                            $excelHeadings[] = [
                                                'headingName' => $feild['name'],
                                                'validationType' => $feild['type']
                                            ];

                                            if (array_key_exists('modelName', $feild)) {
                                                if (array_search('reportsToEmployee', $feild, true) || array_search('functionalReportsToEmployee', $feild, true)) {
                                                    $modelFeilds = $this->getModel($feild['modelName'], true)->toArray()['fields'];
                                                    if (array_key_exists($feild['enumLabelKey'], $modelFeilds)) {
                                                        $concatFeilds = implode(', ', $modelFeilds[$feild['enumLabelKey']]['concatFields']);
                                                        $selectConcatFeildsSql = "CONCAT_WS(' ', employee.firstName,employee.lastName,)";
                                                        $feildOptions = $this->queryBuilder::table($feild['modelName'])
                                                            ->select($this->queryBuilder::raw('employee.firstName,employee.lastName,employeeNumber'))
                                                            ->leftJoin('user', 'user.employeeId', "=", "employee.id")
                                                            ->whereNotNull("user.managerRoleId")
                                                            ->where("user.isDelete", false)
                                                            ->get();
                                                        foreach ($feildOptions as $feildOptionIndex => $feildOptionValue) {
                                                            $tableColName = $feild['enumLabelKey'];
                                                            $this->modelData[$feild['name']][] = $feildOptions[$feildOptionIndex]->employeeNumber . '- ' .
                                                                $feildOptions[$feildOptionIndex]->firstName;
                                                        }
                                                    }
                                                } else if (array_search('employmentStatus', $feild, true)) {
                                                    $feildOptions = $this->queryBuilder::table($feild['modelName'])
                                                        ->select($feild['enumLabelKey'])
                                                        ->where('isDelete', false)
                                                        ->get();
                                                    foreach ($feildOptions as $feildOptionIndex => $feildOptionValue) {
                                                        $tableColName = $feild['enumLabelKey'];
                                                        $this->modelData[$feild['name']][] = $feildOptions[$feildOptionIndex]->$tableColName;
                                                    }
                                                } else if (array_search('calendar', $feild, true)) {
                                                    $feildOptions = $this->queryBuilder::table('workCalendar')
                                                        ->select('name')
                                                        // ->where('isDelete', false)
                                                        ->get();
                                                    foreach ($feildOptions as $feildOptionIndex => $feildOptionValue) {
                                                        $tableColName = 'name';
                                                        $this->modelData[$feild['name']][] = $feildOptions[$feildOptionIndex]->$tableColName;
                                                    }
                                                } else if (array_search('orgStructureEntity', $feild, true)) {
                                                    $combinationArray = $this->getOrgStructureCombinations();
                                                    $options = array_values(array_reverse($combinationArray));

                                                    $this->modelData[$feild['name']] = $options;
                                                } else if (array_search('branch', $feild, true)) {
                                                    $feildOptions = $this->queryBuilder::table($feild['modelName'])
                                                        ->leftJoin('bank', 'bank.id', "=", "bankBranch.bankId")
                                                        ->select('bankBranch.name as branchName', 'bank.name as bankName')
                                                        ->get();
                                                    foreach ($feildOptions as $feildOptionIndex => $feildOptionValue) {
                                                        $feildOptionValue = (array) $feildOptionValue;
                                                        $tableColName = $feild['enumLabelKey'];
                                                        $this->modelData[$feild['name']][] = $feildOptionValue['bankName'] . ' -' . $feildOptionValue['branchName'];
                                                    }
                                                } else {
                                                    $feildOptions = $this->queryBuilder::table($feild['modelName'])
                                                        ->select($feild['enumLabelKey'])
                                                        ->get();
                                                    foreach ($feildOptions as $feildOptionIndex => $feildOptionValue) {
                                                        $tableColName = $feild['enumLabelKey'];
                                                        $this->modelData[$feild['name']][] = $feildOptions[$feildOptionIndex]->$tableColName;
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                    default:
                                        $excelHeadings[] = [
                                            'headingName' => $templateData['name'],
                                            'validationType' => null
                                        ];
                                        break;
                                }
                                break;
                            case 'switch':
                                $excelHeadings[] = [
                                    'headingName' => $templateData['name'],
                                    'validationType' => 'switch'
                                ];
                                break;
                            case 'enum':
                                $excelHeadings[] = [
                                    'headingName' => $templateData['name'],
                                    'validationType' => 'enum'
                                ];

                                foreach ($templateData['values'] as $feildOptionIndex => $feildOptionValue) {
                                    $feildOptionValue = (array) $feildOptionValue;
                                    $this->modelData[$templateData['name']][] = $feildOptionValue['value'];
                                }


                                break;
                            default:
                                if ($templateData['name'] != 'workSchedule') {
                                    $excelHeadings[] = [
                                        'headingName' => $templateData['name'],
                                        'validationType' => null
                                    ];
                                }
                                break;
                        }
                    }

                    // array_pop($excelHeadings); //  temporarily disabling pay grade

                    // overall and validation heading cell map
                    foreach ($excelHeadings as $excelHeadingIndex => $destrcutredHeadings) {
                        if ($destrcutredHeadings['validationType']) {
                            $this->overallColMap[] = [
                                'cellColumnIndex' => Coordinate::stringFromColumnIndex($excelHeadingIndex + 1),
                                'cellName' => $destrcutredHeadings['headingName'],
                                'validationType' => $destrcutredHeadings['validationType']
                            ];
                        }
                        if (!$destrcutredHeadings['validationType']) {
                            $this->overallColMap[] = [
                                'cellColumnIndex' => Coordinate::stringFromColumnIndex($excelHeadingIndex + 1),
                                'cellName' => $destrcutredHeadings['headingName'],
                                'validationType' => $destrcutredHeadings['validationType']
                            ];
                        }
                    }
                }
            }
            return $excelHeadings;
        } catch (Exception $ex) {
            Log::error("processHeadings" . $ex->getMessage());
            throw $ex;
        }
    }


    public function getOrgStructureCombinations()
    {
        $feildOptions = $this->queryBuilder::table('orgEntity')
            ->select('id', 'name', 'parentEntityId', 'entityLevel')
            ->where('isDelete', false)
            ->get();
        $levelWiseEntities = [];
        foreach ($feildOptions as $entityKey => $entity) {
            $entity = (array) $entity;
            $levelWiseEntities[$entity['entityLevel']][] = $entity;
        }

        $levelWiseEntities = array_reverse($levelWiseEntities);
        $combinationArray = [];
        foreach ($levelWiseEntities as $entityLevelkey => $entityLevelArr) {
            $levelDetailArr = explode('level', $entityLevelkey);
            $levelNumber = (int)$levelDetailArr[1];


            foreach ($entityLevelArr as $entityObjkey => $entityData) {
                $entityData = (array) $entityData;
                $currentLevelName = $entityData['name'];
                $expectedNumOfParents = $levelNumber - 1;
                $relatedEntityId = $entityData['id'];
                $currnetParentId = $entityData['parentEntityId'];

                $relatedParentsArray  = [];
                if ($expectedNumOfParents > 0) {
                    $relatedParentsArray = $this->getRelatedParents($currnetParentId, $expectedNumOfParents);
                }

                $relatedParentsArray = array_reverse($relatedParentsArray);

                array_push($relatedParentsArray, $currentLevelName);

                $combinationString = implode("  >  ", $relatedParentsArray);

                $combinationKey = 'combination-' . $relatedEntityId;

                $combinationArray[$combinationKey] = $combinationString;
            }
        }

        return $combinationArray;
    }
    public function getRelatedParents($currnetParentId, $expectedNumOfParents)
    {

        $parentArray = [];
        $parentId = $currnetParentId;
        for ($i = 0; $i < $expectedNumOfParents; $i++) {
            //get currnet Parent

            if (!is_null($parentId)) {
                $parentData = $this->queryBuilder::table('orgEntity')
                    ->select('id', 'name', 'parentEntityId', 'entityLevel')
                    ->where('isDelete', false)
                    ->where('id', $parentId)
                    ->first();
                array_push($parentArray, $parentData->name);
            }

            $parentId = $parentData->parentEntityId;
        }

        return $parentArray;
    }


    public function headings(): array
    {
        $headingNames = array();
        foreach ($this->excelHeadings as $mappedExcelHeadings) {
            $headingNames[] = $this->camelCaseToGeneralText($mappedExcelHeadings['headingName']);
        }
        return $headingNames;
    }

    public function registerEvents(): array
    {
        try {
            if (!empty($this->overallColMap)) {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet;
                        //dynamically maping the validation feilds
                        foreach ($this->overallColMap as $colMap) {
                            if ($colMap['validationType'] == 'model') {
                                if (array_key_exists($colMap['cellName'], $this->modelData)) {
                                    $specialModelCells = ['location', 'orgStructureEntity', 'jobTitle', 'jobCategory', 'reportsToEmployee', 'functionalReportsToEmployee', 'employmentStatus', 'payGrade', 'bank', 'branch'];

                                    if (!empty($this->modelData[$colMap['cellName']])) {
                                        $validation = $event->sheet->getCell("{$colMap['cellColumnIndex']}2")->getDataValidation();
                                        $sheet->setCellValue("{$colMap['cellColumnIndex']}2", "select item");
                                        $validation->setType(DataValidation::TYPE_LIST);
                                        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                                        $validation->setAllowBlank(false);
                                        $validation->setShowInputMessage(true);
                                        $validation->setShowErrorMessage(true);
                                        $validation->setShowDropDown(true);
                                        $validation->setErrorTitle('Input error');
                                        $validation->setError('Value is not in list.');
                                        $validation->setPromptTitle('Pick from list');
                                        $validation->setPrompt('Please pick a value from the drop-down list.');
                                        // $validation->setFormula1(implode(',', $this->restructureSpaceStrings($this->modelData[$colMap['cellName']])));

                                        $optionCount = 1;
                                        if (!empty($this->modelData[$colMap['cellName']])) {
                                            foreach ($this->modelData[$colMap['cellName']] as $option) {
                                                $optionCount++;
                                                $sheet->setCellValue($this->specialModalOptionCellKey . $optionCount, $option);
                                            }
                                            $validation->setFormula1('Worksheet!$' . $this->specialModalOptionCellKey . '$2:$' . $this->specialModalOptionCellKey . '$' . $optionCount);
                                            $sheet->getColumnDimension($this->specialModalOptionCellKey)->setVisible(false);
                                            $this->specialModalOptionCellKey = ++$this->specialModalOptionCellKey;
                                        }

                                        // cloning the validation to the other cells
                                        for ($i = 3; $i <= $this->feildCount; $i++) {
                                            $sheet->setCellValue("{$colMap['cellColumnIndex']}{$i}", "select item");
                                            $event->sheet->getCell("{$colMap['cellColumnIndex']}{$i}")->setDataValidation(clone $validation);
                                        }
                                    }
                                }
                            }

                            if ($colMap['validationType'] == 'switch') {
                                $options = [
                                    'yes',
                                    'no'
                                ];

                                $validation = $event->sheet->getCell("{$colMap['cellColumnIndex']}2")->getDataValidation();
                                $sheet->setCellValue("{$colMap['cellColumnIndex']}2", "select item");
                                $validation->setType(DataValidation::TYPE_LIST);
                                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                                $validation->setAllowBlank(false);
                                $validation->setShowInputMessage(true);
                                $validation->setShowErrorMessage(true);
                                $validation->setShowDropDown(true);
                                $validation->setErrorTitle('Input error');
                                $validation->setError('Value is not in list.');
                                $validation->setPromptTitle('Pick from list');
                                $validation->setPrompt('Please pick a value from the drop-down list.');
                                $validation->setFormula1(sprintf('"%s"', implode(',', $options)));

                                for ($i = 3; $i <= $this->feildCount; $i++) {
                                    $sheet->setCellValue("{$colMap['cellColumnIndex']}{$i}", "select item");
                                    $event->sheet->getCell("{$colMap['cellColumnIndex']}{$i}")->setDataValidation(clone $validation);
                                }
                            }

                            if ($colMap['validationType'] == 'enum') {

                                if ($colMap['cellName'] == 'title') {

                                    $validation = $event->sheet->getCell("{$colMap['cellColumnIndex']}2")->getDataValidation();
                                    $sheet->setCellValue("{$colMap['cellColumnIndex']}2", "select item");
                                    $validation->setType(DataValidation::TYPE_LIST);
                                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                                    $validation->setAllowBlank(false);
                                    $validation->setShowInputMessage(true);
                                    $validation->setShowErrorMessage(true);
                                    $validation->setShowDropDown(true);
                                    $validation->setErrorTitle('Input error');
                                    $validation->setError('Value is not in list.');
                                    $validation->setPromptTitle('Pick from list');
                                    $validation->setPrompt('Please pick a value from the drop-down list.');
                                    $validation->setFormula1(sprintf('"%s"', implode(',', $this->modelData[$colMap['cellName']])));
                                    for ($i = 3; $i <= $this->feildCount; $i++) {
                                        $sheet->setCellValue("{$colMap['cellColumnIndex']}{$i}", "select item");
                                        $event->sheet->getCell("{$colMap['cellColumnIndex']}{$i}")->setDataValidation(clone $validation);
                                    }
                                }
                            }

                            if ($colMap['validationType'] == 'timestamp') {
                                $validation = $event->sheet->getCell("{$colMap['cellColumnIndex']}2")->getDataValidation();
                                $sheet->setCellValue("{$colMap['cellColumnIndex']}2", "YYYY-MM-DD");

                                $sheet->getStyle("{$colMap['cellColumnIndex']}2")->getNumberFormat()->setFormatCode("YYYY-MM-DD");

                                for ($i = 3; $i <= $this->feildCount; $i++) {
                                    $sheet->setCellValue("{$colMap['cellColumnIndex']}{$i}", "YYYY-MM-DD");
                                    $event->sheet->getCell("{$colMap['cellColumnIndex']}{$i}")->setDataValidation(clone $validation);
                                }
                            }
                        }

                        // format the excel cols
                        for ($i = 1; $i <= count($this->overallColMap); $i++) {
                            $column = Coordinate::stringFromColumnIndex($i);

                            if (!empty($this->overallColMap[$i])) {
                                if ($this->overallColMap[$i]['cellName'] == 'orgStructureEntity') {

                                    $orgHierarchyConfig = (array) $this->getConfigValue('organization_hierarchy');
                                    if (sizeof($orgHierarchyConfig) > 5) {
                                        $width = 140;
                                    } else {
                                        $width = 75;
                                    }

                                    $event->sheet->getColumnDimension($this->overallColMap[$i]['cellColumnIndex'])->setWidth($width);
                                } else {
                                    $event->sheet->getColumnDimension($this->overallColMap[$i]['cellColumnIndex'])->setAutoSize(true);
                                }
                            }
                        }
                    }
                ];
            }
            return [];
        } catch (Exception $ex) {
            Log::error("registerEvents" . $ex->getMessage());
            throw $ex;
        }
    }

    public function array(array $excelFeilds)
    {
        try {
            $headers =  array_shift($excelFeilds); // removing the headings
            $feildData = [];
            $dyanamicModel = $this->model->toArray();
            $feildDefinitions = $dyanamicModel['fields'];
            $modelFeildData = [];  // array to add model relation data and model feild name
            $restructuredValidationFeildData = [];
            $restucturedHeaders = [];

            foreach ($headers  as $headerIndex => $header) {

                if (!empty($header)) {
                    $restucturedHeaders[] = $this->labelToCamelCase($header);
                }
            }

            // creating the needed feild data for the model validator
            foreach ($excelFeilds as $values) {
                $feilds = [];
                $isValidRow  = false;
                foreach ($restucturedHeaders as $headersIndex => $excelHeaders) {
                    if ($values[$headersIndex] == 'select item') {
                        $values[$headersIndex] = null;
                    }
                    if (!is_null($values[$headersIndex])) {
                        $isValidRow = true;
                    }
                    $feilds[$excelHeaders] = $values[$headersIndex];
                }

                if ($isValidRow) {
                    $feildData[] = $feilds;
                }
            }

            //filtering out the feilds with the model type and fetching their data
            foreach ($feildDefinitions as $dyanamicFeild) {
                foreach ($restucturedHeaders as $templateHeader) {
                    if ($dyanamicFeild['name'] == $templateHeader) {
                        if ($dyanamicFeild['type'] == 'model') {
                            $modelFeildData[] = [
                                'modelRelation' => $this->model->getRelationType($dyanamicFeild['name']),
                                'modelName' => $dyanamicFeild['modelName'],
                                'enumLabelKey' => $dyanamicFeild['enumLabelKey'],
                                'enumValueKey' => $dyanamicFeild['enumValueKey']
                            ];
                        }
                    }
                }
            }

            foreach ($feildData as $feildIndex => $feildValue) {
                foreach ($restucturedHeaders as $heading) {
                    foreach ($modelFeildData as $modelFeild) {

                        // fetching employee and manager role ids from db
                        if (array_key_exists('allowAccess', $feildData[$feildIndex])) {
                            if ($feildData[$feildIndex]['allowAccess'] == 'yes') {
                                if (isset($modelFeild['modelName']) && $modelFeild['modelName'] == 'userRole') {
                                    if (array_key_exists('employeeRole', $feildValue)) {
                                        if (!is_null($feildValue['employeeRole'])) {
                                            $feildKeyData = (array) $this->queryBuilder::table($modelFeild['modelName'])
                                                ->select($modelFeild['enumValueKey'])
                                                ->where($modelFeild['enumLabelKey'], '=', $feildValue['employeeRole'])
                                                ->first();
                                            //TODO: need to handle below null condition using validation msg
                                            $feildData[$feildIndex]['employeeRoleId'] = !empty($feildKeyData) ? $feildKeyData['id'] : null;
                                            unset($feildData[$feildIndex]['employeeRole']);
                                        }
                                        if (array_key_exists('managerRole', $feildValue)) {
                                            if (!is_null($feildValue['managerRole'])) {
                                                $feildKeyData = (array) $this->queryBuilder::table($modelFeild['modelName'])
                                                    ->select($modelFeild['enumValueKey'])
                                                    ->where($modelFeild['enumLabelKey'], '=', $feildValue['managerRole'])
                                                    ->first();
                                                //TODO: need to handle below null condition using validation msg
                                                $feildData[$feildIndex]['managerRoleId'] = !empty($feildKeyData) ? $feildKeyData['id'] : null;
                                                unset($feildData[$feildIndex]['managerRole']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // fetching other model realted ids
                        if ($modelFeild['modelName'] == $heading) {
                            if ($modelFeild['modelRelation'] == 'HAS_ONE') {
                                $feildKeyData = (array) $this->queryBuilder::table($modelFeild['modelName'])
                                    ->select($modelFeild['enumValueKey'])
                                    ->where($modelFeild['enumLabelKey'], '=', $feildValue[$modelFeild['modelName']])
                                    ->first();
                                if (array_key_exists('id', $feildKeyData)) {
                                    $feildData[$feildIndex][$modelFeild['modelName'] . 'Id'] = $feildKeyData['id'];
                                } else {
                                    $feildData[$feildIndex][$modelFeild['modelName'] . 'Id'] = null;
                                }

                                if ($feildValue['hireDate'] == 'YYYY-MM-DD') {
                                    $feildValue['hireDate'] = null;
                                }

                                if ($feildValue['dateOfBirth'] == 'YYYY-MM-DD') {
                                    $feildValue['dateOfBirth'] = null;
                                }

                                if (is_numeric($feildValue['hireDate'])) {
                                    $feildData[$feildIndex]['hireDate'] = Date::excelToDateTimeObject($feildValue['hireDate'])->format('Y-m-d');
                                } else {
                                    $feildData[$feildIndex]['hireDate'] = (!empty($feildValue['hireDate'])) ? $feildValue['hireDate'] : null;
                                }

                                if (is_numeric($feildValue['dateOfBirth'])) {
                                    $feildData[$feildIndex]['dateOfBirth'] = Date::excelToDateTimeObject($feildValue['dateOfBirth'])->format('Y-m-d');
                                } else {
                                    $feildData[$feildIndex]['dateOfBirth'] = (!empty($feildValue['dateOfBirth'])) ? $feildValue['dateOfBirth'] : null;
                                }

                                if (isset($feildValue['isOTAllowed']) && $feildValue['isOTAllowed'] == 'yes') {
                                    $feildData[$feildIndex]['isOTAllowed'] = true;
                                } else {
                                    $feildData[$feildIndex]['isOTAllowed'] = false;
                                }

                                // Dyanmicially adding the employee job dataset
                                $feildData[$feildIndex]['jobs'] = [
                                    array(
                                        'effectiveDate' => !empty($feildData[$feildIndex]['hireDate']) ?  $feildData[$feildIndex]['hireDate'] : null,
                                        'locationId' => !empty($feildData[$feildIndex]['location']) ? $this->getFeildIdFromName($feildData[$feildIndex]['location'], 'location') : null,
                                        'departmentId' => !empty($feildData[$feildIndex]['department']) ?
                                            $this->getFeildIdFromName($feildData[$feildIndex]['department'], 'department') : null,
                                        'divisionId' => !empty($feildData[$feildIndex]['division']) ?
                                            $this->getFeildIdFromName($feildData[$feildIndex]['division'], 'division') : null,
                                        'jobTitleId' => !empty($feildData[$feildIndex]['jobTitle']) ?
                                            $this->getFeildIdFromName($feildData[$feildIndex]['jobTitle'], 'jobTitle') : null,
                                        'jobCategoryId' => !empty($feildData[$feildIndex]['jobCategory']) ?
                                            $this->getFeildIdFromName($feildData[$feildIndex]['jobCategory'], 'jobCategory') : null,
                                        'reportsToEmployeeId' => !empty($feildData[$feildIndex]['reportsToEmployee']) ?
                                            $this->getFeildIdFromName(
                                                $feildData[$feildIndex]['reportsToEmployee'],
                                                'employee',
                                                true,
                                                'employeeNumber'
                                            ) :
                                            null,
                                        'functionalReportsToEmployeeId' => !empty($feildData[$feildIndex]['functionalReportsToEmployee']) ?
                                            $this->getFeildIdFromName(
                                                $feildData[$feildIndex]['functionalReportsToEmployee'],
                                                'employee',
                                                true,
                                                'employeeNumber'
                                            ) :
                                            null,
                                        'employmentStatusId' => !empty($feildData[$feildIndex]['employmentStatus']) ?
                                            $this->getFeildIdFromName(
                                                $feildData[$feildIndex]['employmentStatus'],
                                                'employmentStatus',
                                                false,
                                                'name'
                                            ) : null,
                                        'employeeJourneyType' => 'JOINED',
                                        'orgStructureEntityId' => !empty($feildData[$feildIndex]['orgStructureEntity']) ?
                                            $this->getOrgEntityId(
                                                $feildData[$feildIndex]['orgStructureEntity'],
                                            ) :
                                            null,
                                        'payGradeId' => !empty($feildData[$feildIndex]['payGrade']) ?
                                            $this->getFeildIdFromName($feildData[$feildIndex]['payGrade'], 'payGrades') : null,
                                        'schemeId' => !empty($feildData[$feildIndex]['scheme']) ?
                                            $this->getFeildIdFromName($feildData[$feildIndex]['scheme'], 'scheme') : null,
                                        'calendarId' => !empty($feildData[$feildIndex]['calendar']) ?
                                            $this->getFeildIdFromName($feildData[$feildIndex]['calendar'], 'workCalendar') : null,
                                    )
                                ];


                                // if (!empty($feildValue['location'])) {
                                //     $feildData[$feildIndex]['jobs'] = [
                                //         array(
                                //             'effectiveDate' => $feildData[$feildIndex]['hireDate'],
                                //             'locationId' => $this->getFeildIdFromName($feildData[$feildIndex]['location'], 'location'),
                                //             'departmentId' => !empty($feildData[$feildIndex]['department']) ?
                                //                 $this->getFeildIdFromName($feildData[$feildIndex]['department'], 'department') : null,
                                //             'divisionId' => !empty($feildData[$feildIndex]['division']) ?
                                //                 $this->getFeildIdFromName($feildData[$feildIndex]['division'], 'division') : null,
                                //             'jobTitleId' => !empty($feildData[$feildIndex]['jobTitle']) ?
                                //                 $this->getFeildIdFromName($feildData[$feildIndex]['jobTitle'], 'jobTitle') : null,
                                //             'reportsToEmployeeId' => !empty($feildData[$feildIndex]['reportsToEmployee']) ?
                                //                 $this->getFeildIdFromName(
                                //                     $feildData[$feildIndex]['reportsToEmployee'],
                                //                     'employee',
                                //                     true,
                                //                     'employeeNumber'
                                //                 ) :
                                //                 null,
                                //             'employmentStatusId' => !empty($feildData[$feildIndex]['employmentStatus']) ?
                                //             $this->getFeildIdFromName(
                                //                 $feildData[$feildIndex]['employmentStatus'],
                                //                 'employmentStatus',
                                //                 false,
                                //                 'title'
                                //             ) : null,
                                //             'employeeJourneyType' => 'JOINED'
                                //         )
                                //     ];

                                //     error_log(json_encode($feildData[$feildIndex]['jobs']));
                                // } else {
                                //     $feildData[$feildIndex]['jobs'] = array(
                                //         'locationId' => $this->getFeildIdFromName($feildData[$feildIndex]['location'], 'location'),
                                //         'departmentId' => !empty($feildData[$feildIndex]['department']) ?
                                //             $this->getFeildIdFromName($feildData[$feildIndex]['department'], 'department') : null,
                                //         'employmentStatusId' => !empty($feildData[$feildIndex]['employmentStatus']) ?
                                //         $this->getFeildIdFromName(
                                //             $feildData[$feildIndex]['employmentStatus'],
                                //             'employmentStatus',
                                //             false,
                                //             'title'
                                //         ) : null,
                                //         'employeeJourneyType' => 'JOINED'

                                //     );
                                //     error_log('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFF');
                                //     error_log(json_encode($feildData[$feildIndex]['jobs']));
                                // }

                                // Dyanmicially adding the employeement status multi record dataset
                                // if (!empty($feildValue['employmentStatus']) && !empty($feildValue['probationEndDate'])) {
                                // if (!empty($feildValue['employmentStatus'])) {
                                //     $feildData[$feildIndex]['employments'] = [
                                //         array(
                                //             'effectiveDate' => $feildData[$feildIndex]['hireDate'],
                                //             'probationEndDate' => (!empty($feildData[$feildIndex]['probationEndDate'])) ? Date::excelToDateTimeObject($feildData[$feildIndex]['probationEndDate'])
                                //                 ->format('Y-m-d') : null,
                                //             'employmentStatusId' => !empty($feildData[$feildIndex]['employmentStatus']) ?
                                //                 $this->getFeildIdFromName(
                                //                     $feildData[$feildIndex]['employmentStatus'],
                                //                     'employmentStatus'
                                //                 ) : null
                                //         )
                                //     ];
                                // } else {
                                //     $feildData[$feildIndex]['employments'] = [
                                //         array(
                                //             'employmentStatusId' => null
                                //         )
                                //     ];
                                // }

                                // Dyanmicially adding the salaries multi record dataset
                                if (!empty($feildValue['basic'])) {
                                    $feildData[$feildIndex]['salaries'] = [
                                        array(
                                            'effectiveDate' => $feildData[$feildIndex]['hireDate'],
                                            'basic' => $feildData[$feildIndex]['basic'],
                                            'allowance' => $feildData[$feildIndex]['allowance'],
                                            'epfEmployer' => $feildData[$feildIndex]['epfEmployer'],
                                            'epfEmployee' => $feildData[$feildIndex]['epfEmployee'],
                                            'etf' => $feildData[$feildIndex]['etf'],
                                            'payeeTax' => $feildData[$feildIndex]['payeeTax'],
                                            'ctc' => $feildData[$feildIndex]['payeeTax'],
                                        )
                                    ];
                                }

                                // Dyanmicially adding the bank multi record dataset
                                if (!empty($feildValue['bank'])) {
                                    $bankId = $this->getFeildIdFromName($feildData[$feildIndex]['bank'], 'bank');
                                    $branchId = null;

                                    if (!empty($feildData[$feildIndex]['branch'])) {
                                        $branchArr = explode("-", $feildData[$feildIndex]['branch']);
                                        $branchName = (sizeof($branchArr) == 2) ? $branchArr[1] : null;
                                        $feild = $this->queryBuilder::table('bankBranch')
                                            ->select('id')
                                            ->where('bankId', '=', $bankId)
                                            ->where('name', '=', $branchName)
                                            ->first();
                                        if (!empty($feild)) {
                                            $branchId = ($feild->id) ? $feild->id : null;
                                        } else {

                                            $otherRes = $this->queryBuilder::table('bankBranch')
                                                ->select('id')
                                                ->where('name', '=', $branchName)
                                                ->first();
                                            if (!empty($otherRes)) {
                                                $branchId = ($otherRes->id) ? $otherRes->id : null;
                                            }
                                        }
                                    }

                                    $feildData[$feildIndex]['bankAccounts'] = [
                                        array(
                                            'effectiveDate' => $feildData[$feildIndex]['hireDate'],
                                            'bankId' => $bankId,
                                            'branchId' => $branchId,
                                            'accountNumber' => (!empty($feildData[$feildIndex]['accountNumber'])) ? $feildData[$feildIndex]['accountNumber'] : null,
                                        )
                                    ];
                                }
                            }
                        }
                    }
                }
                $restructuredValidationFeildData[] = $feildData[$feildIndex];
            }

            $feildErrors = []; // array containing the overall feild errors of all rows
            $hasValidationErrors = false;

            //get all employee number config details
            $empNumberConfigDataSet = DB::table("employeeNumberConfiguration")->get(['id', 'entityId', 'prefix', 'nextNumber', 'numberLength']);
            $entityWiseEmpNectNumArray = [];

            foreach ($empNumberConfigDataSet as $empNumConfigkey => $empNumConfigData) {
                $keyVal= 'entity-'.$empNumConfigData->entityId;
                $entityWiseEmpNectNumArray[$keyVal] = $empNumConfigData->nextNumber;
            }
            
            foreach ($restructuredValidationFeildData as $bulkUploadIndex => $bulkEmployeeData) {

                // $rowKey = 'Row-'.$bulkEmployeeData
                $validationResponse = ModelValidator::validate($this->model, $bulkEmployeeData, false);
                if (!empty($validationResponse)) {
                    $hasValidationErrors = true;
                }
                $feildErrors[] = $validationResponse;

                //check hire date format
                if (isset($bulkEmployeeData['hireDate']) && !empty($bulkEmployeeData['hireDate']) && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $bulkEmployeeData['hireDate'])) {
                    $hasValidationErrors = true;
                    $feildErrors[$bulkUploadIndex]['hireDate'] = ['Invalid Format (YYYY-MM-DD)'];
                }

                //check date of birt format
                if (isset($bulkEmployeeData['dateOfBirth']) && !empty($bulkEmployeeData['dateOfBirth']) && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $bulkEmployeeData['dateOfBirth'])) {
                    $hasValidationErrors = true;
                    $feildErrors[$bulkUploadIndex]['dateOfBirth'] = ['Invalid Format (YYYY-MM-DD)'];
                }

                //check whether employee Number in correct format
                if (isset($bulkEmployeeData['employeeNumber']) && !empty($bulkEmployeeData['employeeNumber']) && !isset($feildErrors[$bulkUploadIndex]['employeeNumber'])) {

                    if(isset($bulkEmployeeData['jobs'][0]['orgStructureEntityId']) && !empty($bulkEmployeeData['jobs'][0]['orgStructureEntityId'])) {
                        $employeeNumberResponse = $this->generateEmployeeNumber($bulkEmployeeData['jobs'][0]['orgStructureEntityId']);

                        if ($employeeNumberResponse['error']) {
                            $hasValidationErrors = true;
                            $feildErrors[$bulkUploadIndex]['employeeNumber'] = ['Emp number not config for selected org entity'];
                        } else {
                            $restructuredValidationFeildData[$bulkUploadIndex]['employeeNumberConfigId'] = $employeeNumberResponse['data']['numberConfigId'];
                            //check whether emp number in correct format
                            $employeeNumber = $this->checkEmployeeNumberFormat($bulkEmployeeData['employeeNumber'], $employeeNumberResponse['data']['configRecord']);
                           
                            if (is_null($employeeNumber)) {
                                $empNumberExampleFormat = $this->getEmployeeNumberFormat($employeeNumberResponse['data']['configRecord']);
                                $hasValidationErrors = true;
                                $feildErrors[$bulkUploadIndex]['employeeNumber'] = ['Invalid Format - Should Be (' . $empNumberExampleFormat . ')'];
                            } else {
                                $entityIndexString = 'entity-'.$employeeNumberResponse['data']['configRecord']->entityId;
                                $nextNumber = $entityWiseEmpNectNumArray[$entityIndexString];
    
                                //check emp number sequence is right
                                $postfix = str_pad($nextNumber, $employeeNumberResponse['data']['configRecord']->numberLength, "0", STR_PAD_LEFT);
                                $nextEmpNumber = $employeeNumberResponse['data']['configRecord']->prefix.$postfix;
    
                                if($nextEmpNumber !== $bulkEmployeeData['employeeNumber']) {
                                    $hasValidationErrors = true;
                                    $feildErrors[$bulkUploadIndex]['employeeNumber'] = ['Not Match With Emp Number Sequence'];
                                }
                                $entityWiseEmpNectNumArray[$entityIndexString] += 1;
    
                            }
                        }

                    }
               
                }
            }

            if (!$hasValidationErrors) {

                foreach ($restructuredValidationFeildData as $bulkUploadIndex => $bulkEmployeeData) {
                    $userModel = $this->getModel('user', true);
                    $empNumberConfigId = $restructuredValidationFeildData[$bulkUploadIndex]['employeeNumberConfigId'];

                    $addedEmployee = $this->store->insert($this->model, $restructuredValidationFeildData[$bulkUploadIndex], true);
                    $this->incrementEmployeeNumber($empNumberConfigId);
                    $this->addedEmployeeIds[] = $addedEmployee['id'];

                    // creating user array
                    if (array_key_exists('allowAccess', $restructuredValidationFeildData[$bulkUploadIndex])) {
                        if ($restructuredValidationFeildData[$bulkUploadIndex]['allowAccess'] == 'yes') {
                            if (
                                array_key_exists('employeeRoleId', $restructuredValidationFeildData[$bulkUploadIndex]) &&
                                array_key_exists('managerRoleId', $restructuredValidationFeildData[$bulkUploadIndex])
                            ) {
                                $restructuredValidationFeildData[$bulkUploadIndex]['user'] = [
                                    'email' => $restructuredValidationFeildData[$bulkUploadIndex]['workEmail'],
                                    'employeeRoleId' => $restructuredValidationFeildData[$bulkUploadIndex]['employeeRoleId'],
                                    'firstName' => $restructuredValidationFeildData[$bulkUploadIndex]['firstName'],
                                    'lastName' => $restructuredValidationFeildData[$bulkUploadIndex]['lastName'],
                                    'managerRoleId' => $restructuredValidationFeildData[$bulkUploadIndex]['managerRoleId'],
                                    'middleName' => $restructuredValidationFeildData[$bulkUploadIndex]['middleName'],
                                    'employeeId' => $addedEmployee['id']
                                ];
                            } else if (array_key_exists('managerRoleId', $restructuredValidationFeildData[$bulkUploadIndex])) {
                                $restructuredValidationFeildData[$bulkUploadIndex]['user'] = [
                                    'email' => $restructuredValidationFeildData[$bulkUploadIndex]['workEmail'],
                                    'employeeRoleId' => null,
                                    'firstName' => $restructuredValidationFeildData[$bulkUploadIndex]['firstName'],
                                    'lastName' => $restructuredValidationFeildData[$bulkUploadIndex]['lastName'],
                                    'managerRoleId' => $restructuredValidationFeildData[$bulkUploadIndex]['managerRoleId'],
                                    'middleName' => $restructuredValidationFeildData[$bulkUploadIndex]['middleName'],
                                    'employeeId' => $addedEmployee['id']
                                ];
                            } else if (array_key_exists('employeeRoleId', $restructuredValidationFeildData[$bulkUploadIndex])) {
                                $restructuredValidationFeildData[$bulkUploadIndex]['user'] = [
                                    'email' => $restructuredValidationFeildData[$bulkUploadIndex]['workEmail'],
                                    'employeeRoleId' => $restructuredValidationFeildData[$bulkUploadIndex]['employeeRoleId'],
                                    'firstName' => $restructuredValidationFeildData[$bulkUploadIndex]['firstName'],
                                    'lastName' => $restructuredValidationFeildData[$bulkUploadIndex]['lastName'],
                                    'managerRoleId' => null,
                                    'middleName' => $restructuredValidationFeildData[$bulkUploadIndex]['middleName'],
                                    'employeeId' => $addedEmployee['id']
                                ];
                            }
                        }
                    }
                    if (isset($userModel)) {
                        if (array_key_exists('user', $restructuredValidationFeildData[$bulkUploadIndex])) {
                            $userService = new UserService($this->store, $this->session);
                            $userService->createUser($restructuredValidationFeildData[$bulkUploadIndex]['user']);
                            // $this->store->insert($userModel, $restructuredValidationFeildData[$bulkUploadIndex]['user']);
                            unset($restructuredValidationFeildData[$bulkUploadIndex]['user']);
                        }
                    }

                    if (isset($addedEmployee)) {
                        $this->addedCount = $bulkUploadIndex;
                    }
                }
            }

            $restrcutredFeilds = []; // contains the overall strctured error feilds
            $feildCount = 1;

            foreach ($feildErrors as $feildErrorsIndex => $error) {

                if (array_key_exists('salaries', $error)) {
                    unset($error['salaries']);
                }

                if (array_key_exists('bankAccounts', $error)) {
                    foreach ($error['bankAccounts'] as $bankAccountErrorkey => $bankAccountError) {
                        $bankAccountError = (array) $bankAccountError;
                        if (array_key_exists('bank', $bankAccountError) && !array_key_exists('branch', $bankAccountError)) {
                            $error['bank'] = $bankAccountError['bank'];
                        }

                        if (!array_key_exists('bank', $bankAccountError) && array_key_exists('branch', $bankAccountError)) {
                            $error['branch'] = $bankAccountError['branch'];
                        }

                        if (!array_key_exists('bank', $bankAccountError) && array_key_exists('accountNumber', $bankAccountError)) {
                            $error['accountNumber'] = $bankAccountError['accountNumber'];
                        }
                    }

                    unset($error['bankAccounts']);
                }

                if (array_key_exists('jobs', $error)) {

                    foreach ($error['jobs'] as $jobErrorkey => $jobError) {
                        $jobError = (array) $jobError;
                        if (array_key_exists('department', $jobError)) {
                            $error['department'] = $jobError['department'];
                        }

                        if (array_key_exists('location', $jobError)) {
                            $error['location'] = $jobError['location'];
                        }

                        if (array_key_exists('employmentStatus', $jobError)) {
                            $error['employmentStatus'] = $jobError['employmentStatus'];
                        }

                        if (array_key_exists('payGrade', $jobError)) {
                            $error['payGrade'] = $jobError['payGrade'];
                        }

                        if (array_key_exists('jobTitle', $jobError)) {
                            $error['jobTitle'] = $jobError['jobTitle'];
                        }

                        if (array_key_exists('jobCategory', $jobError)) {
                            $error['jobCategory'] = $jobError['jobCategory'];
                        }

                        if (array_key_exists('calendar', $jobError)) {
                            $error['calendar'] = $jobError['calendar'];
                        }

                        if (array_key_exists('orgStructureEntity', $jobError)) {
                            $error['orgStructureEntity'] = $jobError['orgStructureEntity'];
                        }

                        if (array_key_exists('reportsToEmployee', $jobError)) {
                            $error['reportsToEmployee'] = $jobError['reportsToEmployee'];
                        }
                    }
                    unset($error['jobs']);
                }

                if (array_key_exists('employments', $error)) {


                    foreach ($error['employments'] as $employeementErrorkey => $employeementError) {
                        $employeementError = (array) $employeementError;
                        if (array_key_exists('employmentStatus', $employeementError)) {
                            $error['employmentStatus'] = $employeementError['employmentStatus'];
                        }
                    }
                    unset($error['employments']);
                }

                $feildKeys = []; // contains all the strctured feild keys
                foreach ($error as $errorIndex => $errorFeild) {
                    $feildKeys[$errorIndex] = [
                        "key" => $feildCount,
                        "name" => $this->camelCaseToGeneralText($errorIndex),
                        "value" =>  $restructuredValidationFeildData[$feildErrorsIndex][$errorIndex] ?? null,
                        "isListFeild" => true,
                        "feildKey" => $feildCount,
                        "errorMessage" => $error[$errorIndex][0]
                    ];
                    $feildCount++;
                }

                // if (!empty($feildKeys))
                    $restrcutredFeilds[$feildErrorsIndex] = (object) $feildKeys;
            }

            if (!empty($restrcutredFeilds)) {
                $withoutErrorRowCount = 0;
                foreach ($restrcutredFeilds as $restrcutredFeildsKey => $restrcutredFeild) {
                    $restrcutredFeild = (array) $restrcutredFeild;
                    if (empty($restrcutredFeild)) {
                        $withoutErrorRowCount ++;
                    }
                }

                $this->modelValidatorErrors =  (sizeof($restrcutredFeilds) == $withoutErrorRowCount) ? [] : $restrcutredFeilds;
            }
        } catch (Exception $ex) {
            Log::error("array" . $ex->getMessage());
            throw $ex;
        }
    }

    private function getOrgEntityId($orgCombination)
    {
        $combinationArray = $this->getOrgStructureCombinations();
        $relatedEntityId = null;
        foreach ($combinationArray as $combinationKey => $combinationString) {
            if ($orgCombination == $combinationString) {
                $keyArr = explode('-', $combinationKey);
                $relatedEntityId = (!empty($keyArr[1])) ? (int) $keyArr[1] : null;
                break;
            }
        }
        return $relatedEntityId;
    }

    private function checkEmployeeNumberFormat($payLoadId, $configRecord)
    {
        // $getPrefixCode = $this->queryBuilder::table('prefixCode')->where('modelType', 'employee')->first();

        // if (is_null($getPrefixCode)) {
        //     return 'no emp format';
        // }

        $prefixStringLength = strlen($configRecord->prefix);
        $prefixCode = $configRecord->prefix;
        $prefixCodeLength = $configRecord->numberLength;
        $employeeNumberPrefixCode = substr($payLoadId, 0, $prefixStringLength);

        if (!strcmp($prefixCode, $employeeNumberPrefixCode)) {
            $employeeNumber = substr($payLoadId, $prefixStringLength);
            if (strlen($employeeNumber) ==  $prefixCodeLength) {
                return $payLoadId;
            }
        } else {
            return null;
        }
    }

    private function getEmployeeNumberFormat($configRecord)
    {
        $getPrefixCode = $this->queryBuilder::table('prefixCode')->where('modelType', 'employee')->first();
        $exampleEmpCode = $configRecord->prefix;

        for ($i = 0; $i < $configRecord->numberLength; $i++) {
            $exampleEmpCode = $exampleEmpCode . '0';
        }

        return $exampleEmpCode;
    }

    public function getValidationFeildErrors(): array
    {
        return $this->modelValidatorErrors;
    }

    public function getAddedCount()
    {
        return $this->addedCount;
    }

    private function restructureSpaceStrings($dataSet)
    {
        $dashedStringSet = [];
        foreach ($dataSet as $dataString) {
            if ($dataString == trim($dataString) && str_contains($dataString, '')) {
                $dashedStringSet[] = trim(str_replace(' ', '-', trim($dataString)));
            }
        }
        return $dashedStringSet;
    }

    private function getFeildIdFromName($feildValue, $tableName, $isReportsToFeild = false, $tableCol = 'name')
    {
        if ($tableName == 'employmentStatus') {
            if (!$isReportsToFeild) {
                if (!empty($feildValue) || !empty($tableName) || !empty($tableFeild)) {
                    $feild = $this->queryBuilder::table($tableName)
                        ->select('id')
                        ->where($tableCol, '=', $feildValue)
                        ->where('isDelete', false)
                        ->first();

                    return empty($feild) ? null : $feild->id;
                }
            }
        }

        if (!$isReportsToFeild) {
            if (!empty($feildValue) || !empty($tableName) || !empty($tableFeild)) {
                $feild = $this->queryBuilder::table($tableName)
                    ->select('id')
                    ->where($tableCol, '=', $feildValue)
                    ->first();

                return empty($feild) ? null : $feild->id;
            }
        }

        if (!is_null($feildValue) && $isReportsToFeild) {
            $destructuredValues[]  =   explode("-", $feildValue);
            $feild = $this->queryBuilder::table($tableName)
                ->select('id')
                ->where($tableCol, '=', $destructuredValues[0][0])
                ->first();

            return empty($feild) ? null : $feild->id;
        }
    }

    public function getAddedEmployees(): array
    {
        return $this->addedEmployeeIds;
    }

    private function camelCaseToGeneralText($string, $us = " ")
    {
        return ucwords(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/',
            $us,
            $string
        ));
    }

    private function labelToCamelCase($string)
    {
        $str = str_replace(' ', '',  ucwords($string));
        $str[0] = strtolower($str[0]);
        return $str;
    }
}

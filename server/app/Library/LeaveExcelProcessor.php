<?php

namespace App\Library;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
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
use App\Services\UserService;
use Carbon\Carbon;

class LeaveExcelProcessor implements WithHeadings, WithEvents, ToArray
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
    private $modelValidatorErrors = []; // restrcutred uploaded data from the excel sheet
    private $session;
    private $addedCount;
    private $addedEmployeeIds = []; // the array that consists of all the bulk uploaded employee ids
    private $processedLeaveDataset = []; // the array that consists of all the bulk uploaded employee ids

    use Exportable;
    use JsonModelReader;

    public function __construct(Model $model, Session $session, Store $store, $downloadParams = [], $uploadParams = [])
    {
        $this->model = $model;
        $this->store = $store;
        $this->queryBuilder = $store->getFacade();
        $this->session = $session;

        if (!empty($downloadParams)) {
            $this->defintionStrcutre = $downloadParams['defintionStrcutre'];
            $this->feildCount = $downloadParams['feildCount'] + 2;
            // $this->excelHeadings = $this->processHeadings();
        }
    }

    public function startCell(): string
    {
        return 'A3';
    }

    
    public function headings(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        try {
            return [
                AfterSheet::class => function (AfterSheet $event) {
                    $sheet = $event->sheet;

                    $sheet = $event->sheet;

                    $sheet->mergeCells('A1:A2');
                    $sheet->setCellValue('A1', "Employee Number");

                    $leaveTypes = $this->queryBuilder::table('leaveType')
                            ->select('id','name')
                            ->where('allowExceedingBalance', false)
                            ->where('isDelete', false)
                            ->get();
                    $startLetter = 'A';
                    $currentLetter = ++$startLetter;
                    $nextLetter = null;
                    $afterLetter = null;

                    foreach ($leaveTypes as $key => $value) {
                        $value = (array) $value;
                        $current = $currentLetter;
                        $nextLetter = ++$currentLetter;
                        $next = $nextLetter;
                        $afterLetter = $nextLetter;
                        $sheet->mergeCells("{$current}1:{$next}1");
                        $sheet->setCellValue("{$current}1", $value['name']);
                        $sheet->setCellValue("{$current}2", "Allocated");
                        $sheet->setCellValue("{$next}2", "Used");

                        $currentLetter = ++$nextLetter;
                    }

                    
                    $styleArray = [
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ],
                    ];
                    
                    $cellRange = "A1:{$afterLetter}1"; // All headers
                    $event->sheet->getDelegate()->getStyle($cellRange)->applyFromArray($styleArray);

                }
            ];
        } catch (Exception $ex) {
            Log::error("registerEvents" . $ex->getMessage());
            throw $ex;
        }
    }

    public function array(array $excelFeilds)
    {
        try {
            $mainHeaders =  array_shift($excelFeilds); // removing the headings
            $subHeaders =  array_shift($excelFeilds); // removing the headings
            $feildData = [];
            $restructuredValidationFeildData = [];
            $restucturedHeaders = [];
            $mainFilterHeaders = [];

            foreach ($mainHeaders  as $headerIndex => $header) {
                if (!empty($header)) {
                    $restucturedHeaders[] = [
                        'columnName' => $header,
                        'value' => Null,
                        'subColumns' => Null
                    ];
                }                
            }

            $leaveTypeSet = 1;
            $limit = 2;
            foreach ($subHeaders  as $subHeaderIndex => $subHeader) {
                if ($subHeaderIndex === 0) {
                    continue;
                } else {

                    if ($subHeaderIndex <= $limit) {
                        $restucturedHeaders[$leaveTypeSet]['subColumns'][$subHeader]['value'] = null;
                        $restucturedHeaders[$leaveTypeSet]['subColumns'][$subHeader]['index'] = $subHeaderIndex;
                    } else {
                        $limit = $subHeaderIndex+1;
                        $leaveTypeSet ++;
                        $restucturedHeaders[$leaveTypeSet]['subColumns'][$subHeader]['value'] = null;
                        $restucturedHeaders[$leaveTypeSet]['subColumns'][$subHeader]['index'] = $subHeaderIndex;
                    }

                    
                }
            }

            
            
            
            foreach ($excelFeilds  as $fieldIndex => $values) {
                $values = (array) $values;
                $dataArray = $restucturedHeaders;

                foreach ($dataArray as $headersIndex => $excelHeaderArr) {
                    $excelHeaderArr = (array) $excelHeaderArr;
                    if ($headersIndex == 0) {
                        $dataArray[$headersIndex]['value'] = $values[0];
                    } else {
                        if (!empty($dataArray[$headersIndex]['subColumns'])) {
                            foreach ($dataArray[$headersIndex]['subColumns'] as $key => $subColArr) {
                                $dataArray[$headersIndex]['subColumns'][$key]['value'] = $values[$dataArray[$headersIndex]['subColumns'][$key]['index']];
                            }
                        }
                        
                    }
                    
                }
                
                $feildData[] = $dataArray;
            }

 
            $finaleData = [];
            foreach ($feildData as $fieldIndex => $fieldArr) {
                $fieldArr = (array) $fieldArr;
                $employeeName = $fieldArr[0]['value'];
                
                foreach ($fieldArr as $dataIndex => $data) {
                    $tempData = [];
                    if (!empty($data['columnName'])) {
                        if ($dataIndex == 0) {
                            continue;
                        } else {
                            $tempData['employeeNumber'] = $employeeName;
                            $tempData['leaveType'] = $data['columnName'];
                            $tempData['entilementCount'] = $data['subColumns']['Allocated']['value'];
                            $tempData['usedCount'] = $data['subColumns']['Used']['value'];
                        }
    
                        if (!is_null($tempData['entilementCount']) || !is_null($tempData['usedCount'])) {
                            $tempData['pendingCount'] = 0;
                            $tempData['type'] = 'MANUAL';
                            $tempData['key'] = rand(10000,1000000);
                            $tempData['leavePeriodFrom'] = NULL;
                            $tempData['leavePeriodTo'] = NULL;
                            $tempData['validFrom'] = NULL;
                            $tempData['validTo'] = NULL;
                            $tempData['hasErrors'] = false;
                            $finaleData[] = $tempData;
                        }
                    }
                }
            }
            $this->processedLeaveDataset = $finaleData;
        
        } catch (Exception $ex) {
            Log::error("array" . $ex->getMessage());
            throw $ex;
        }
    }

    public function getValidationFeildErrors(): array
    {
        return $this->modelValidatorErrors;
    }

    public function getAddedCount()
    {
        return $this->addedCount;
    }
    
    public function getProcessedDataset(): array
    {
        return $this->processedLeaveDataset;
    }

}

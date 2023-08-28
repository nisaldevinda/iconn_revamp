<?php

namespace App\Exports;

use Log;
use Exception;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\AfterSheet;
use \Maatwebsite\Excel\Sheet;
use DateTime;
Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
    $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
});

class ExcelExport implements WithEvents, FromArray
{
    protected $headerArray;
    protected $headerColumnCellRange;
    protected $dataSetArray;
    protected $report;
    public function __construct($headerArray, $dataSetArray, $headerColumnCellRange ,$report)
    {
        $this->headerArray = $headerArray;
        $this->headerColumnCellRange = $headerColumnCellRange;
        $this->dataSetArray = $dataSetArray;
        $this->report = $report;
    }

    public function array(): array
    {
        try {
            $attendanceArrays = [];
            array_push($attendanceArrays, $this->headerArray);

            foreach ($this->dataSetArray as $key => $dataSet) {
                $reportArray =[];
                if ($this->report === "employee") {
                  $reportArray = [
                      $dataSet->name,
                      DateTime::createFromFormat('Y-m-d', $dataSet->leavePeriodFrom)->format('d-m-Y') .' to '. DateTime::createFromFormat('Y-m-d', $dataSet->leavePeriodTo)->format('d-m-Y'),
                      $dataSet->entitlementCount ,
                      $dataSet->pendingCount ,
                      $dataSet->usedCount ,
                      $dataSet->leaveBalance
                  ];
                } else if ($this->report === 'employeeLeaveRequestReport') {
                    $reportArray = [
                        $dataSet->employeeNumber,
                        $dataSet->employeeName,
                        $dataSet->leaveTypeName,
                        DateTime::createFromFormat('Y-m-d', $dataSet->fromDate)->format('d-m-Y'),
                        DateTime::createFromFormat('Y-m-d', $dataSet->toDate)->format('d-m-Y'),
                        $dataSet->numberOfLeaveDates ,
                        $dataSet->reason ,
                        $dataSet->StateLabel ,
                        $dataSet->levelApproveDetails
                    ];

                }  else if ($this->report === 'employeeShortLeaveRequestReport') {
                    $reportArray = [
                        $dataSet->employeeNumber,
                        $dataSet->employeeName,
                        DateTime::createFromFormat('Y-m-d', $dataSet->fromDate)->format('d-m-Y'),
                        $dataSet->hours ,
                        $dataSet->reason ,
                        $dataSet->StateLabel ,
                        $dataSet->levelApproveDetails
                    ];

                } else {
                    $reportArray = [
                        $dataSet->employeeNumber,
                        $dataSet->employeeName,
                        $dataSet->name,
                        DateTime::createFromFormat('Y-m-d', $dataSet->leavePeriodFrom)->format('d-m-Y') .' to '. DateTime::createFromFormat('Y-m-d', $dataSet->leavePeriodTo)->format('d-m-Y'),
                        $dataSet->entitlementCount ,
                        $dataSet->pendingCount ,
                        $dataSet->usedCount ,
                        $dataSet->leaveBalance
                    ];
                  }
                array_push($attendanceArrays, $reportArray);
            }

            return [
                $attendanceArrays
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error($e->getCode(), $e->getMessage(), null);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle($this->headerColumnCellRange)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('DD4B39');

                $event->sheet->getDelegate()->getStyle($this->headerColumnCellRange)
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}

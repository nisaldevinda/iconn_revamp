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

class LeaveEntitlementReportExcelExport implements WithEvents, FromArray
{
    protected $headerArray;
    protected $headerColumnCellRange;
    protected $dataSetArray;
    protected $report;
    public function __construct($headerArray, $dataSetArray, $headerColumnCellRange ,$report, $store, $leaveTypes)
    {
        $this->headerArray = $headerArray;
        $this->leaveTypes = $leaveTypes;
        $this->headerColumnCellRange = $headerColumnCellRange;
        $this->dataSetArray = $dataSetArray;
        $this->queryBuilder = $store->getFacade();
        $this->report = $report;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function array(): array
    {
        try {
            $attendanceArrays = [];

            $headerArray = [];
            array_push($attendanceArrays, $headerArray);

            $headerArray = [];
            array_push($attendanceArrays, $headerArray);


            foreach ($this->dataSetArray as $key => $employee) {
                $employee = (array) $employee;
                $rowArray = [];

                $rowArray[] = $employee['employeeNumber'];
                $rowArray[] = $employee['employeeName'];

                foreach ($employee['leaveTypeDetails'] as $key => $leaveTypeDetail) {
                    $leaveTypeDetail = (array) $leaveTypeDetail;
                    $rowArray[] = ($leaveTypeDetail['allocated'] == 0) ? '0' : $leaveTypeDetail['allocated'];
                    $rowArray[] = ($leaveTypeDetail['approved'] == 0) ? '0' : $leaveTypeDetail['approved'];
                    $rowArray[] = ($leaveTypeDetail['pending'] == 0) ? '0' : $leaveTypeDetail['pending'];
                    $rowArray[] = ($leaveTypeDetail['balance'] == 0) ? '0' : $leaveTypeDetail['balance'];

                }

                array_push($attendanceArrays, $rowArray);

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
                $sheet = $event->sheet;

                $sheet = $event->sheet;

                $sheet->mergeCells('A1:A2');
                $sheet->setCellValue('A1', "Employee Number");

                $sheet->mergeCells('B1:B2');
                $sheet->setCellValue('B1', "Employee Name");

                $leaveTypes = $this->queryBuilder::table('leaveType')
                        ->select('id','name')
                        ->whereIn('id', $this->leaveTypes)
                        ->where('isDelete', false)
                        ->get();
                $startLetter = 'B';
                $currentLetter = ++$startLetter;
                $nextLetter = null;
                $thirdLetter = null;
                $forthLetter = null;
                $afterLetter = null;

                foreach ($leaveTypes as $key => $value) {
                    $value = (array) $value;
                    $current = $currentLetter;
                    $nextLetter = ++$currentLetter;
                    $next = $nextLetter;
                    $thirdLetter = ++$nextLetter;
                    $third = $thirdLetter;
                    $forthLetter = ++$thirdLetter;
                    $forth = $forthLetter;
                    $afterLetter = $forthLetter;
                    $sheet->mergeCells("{$current}1:{$forth}1");
                    $sheet->setCellValue("{$current}1", $value['name']);
                    $sheet->setCellValue("{$current}2", "Allocation");
                    $sheet->setCellValue("{$next}2", "Approved");
                    $sheet->setCellValue("{$third}2", "Pending");
                    $sheet->setCellValue("{$forth}2", "Balance");

                    $currentLetter = ++$forthLetter;
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
    }
}

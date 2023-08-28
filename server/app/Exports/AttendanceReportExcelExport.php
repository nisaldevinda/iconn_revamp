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

class AttendanceReportExcelExport implements WithEvents, FromArray
{
    protected $headerArray;
    protected $headerColumnCellRange;
    protected $columnMappingDataIndexs;
    protected $dataSetArray;
    protected $report;
    public function __construct($headerArray, $dataSetArray, $headerColumnCellRange ,$report, $columnMappingDataIndexs)
    {
        $this->headerArray = $headerArray;
        $this->headerColumnCellRange = $headerColumnCellRange;
        $this->dataSetArray = $dataSetArray;
        $this->columnMappingDataIndexs = $columnMappingDataIndexs;
        $this->report = $report;
    }

    public function array(): array
    {
        try {
            $attendanceArrays = [];
            array_push($attendanceArrays, $this->headerArray);

            foreach ($this->dataSetArray as $key => $dataSet) {
                $dataSet = (array) $dataSet;
                $reportArray =[];

                foreach ($this->headerArray as $key => $header) {
                    $cellVal = !empty($dataSet[$this->columnMappingDataIndexs[$header]]) ? $dataSet[$this->columnMappingDataIndexs[$header]] : '-';
                    array_push($reportArray,$cellVal);
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
                    ->setARGB('F2F4F6');

                $event->sheet->getDelegate()->getStyle($this->headerColumnCellRange)
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}

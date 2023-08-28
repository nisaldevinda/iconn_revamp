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

class LeaveRequestExcelExport implements WithEvents, FromArray
{
    protected $headerArray;
    protected $headerColumnCellRange;
    protected $dataSetArray;

    public function __construct($headerArray, $dataSetArray, $headerColumnCellRange)
    {
        $this->headerArray = $headerArray;
        $this->headerColumnCellRange = $headerColumnCellRange;
        $this->dataSetArray = $dataSetArray;
    }

    public function array(): array
    {
        try {
            $leavesArrays = [];
            array_push($leavesArrays, $this->headerArray);

            if (is_array($this->dataSetArray) && !empty($this->dataSetArray)) {

                foreach ($this->dataSetArray as $key => $dataSet) {
                    $dataSet = (array)$dataSet;
                    $reportArray =[];
                    $reportArray = [
                        $dataSet['employeeName'],
                        $dataSet['fromDate'],
                        $dataSet['toDate'] ,
                        $dataSet['leaveTypeName'] ,
                        $dataSet['numberOfLeaveDates'] ,
                        $dataSet['StateLabel']
                    ];
                   
                    array_push($leavesArrays, $reportArray);
                }
            }


            return [
                $leavesArrays
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error(400, $e->getMessage(), null);
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

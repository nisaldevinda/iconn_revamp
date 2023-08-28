<?php

namespace App\Exports;

use Log;
use Exception;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\AfterSheet;
use \Maatwebsite\Excel\Sheet;
use App\Traits\ServiceResponser;

Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
    $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
});

class ReportExcelExport implements WithEvents, FromArray
{
    use ServiceResponser;

    protected $headerData;
    protected $headerColumnCellRange;
    protected $dataSetArray;

    public function __construct($headerData, $dataSetArray, $headerColumnCellRange)
    {
        $this->headerData = $headerData;
        $this->headerColumnCellRange = $headerColumnCellRange;
        $this->dataSetArray = $dataSetArray;
    }

    public function array(): array
    {
        try {
            $leavesArrays = [];
            $headerArray = [];
            $headerIndexArr = [];

            foreach ($this->headerData as $headerKey => $header) {
                $header = (array) $header;
                if (!$header['hideInTable']) {
                    array_push($headerArray, $header['title']);
                    array_push($headerIndexArr, $header['dataIndex']);
                }
            }

            array_push($leavesArrays, $headerArray);

            if (is_array($this->dataSetArray) && !empty($this->dataSetArray)) {
                foreach ($this->dataSetArray as $key => $dataSet) {
                    $dataSet = (array)$dataSet;
                    $reportArray =[];

                    foreach ($headerIndexArr as $indexkey => $headerIndex) {
                        $value = isset($dataSet[$headerIndex]) ? $dataSet[$headerIndex] : '';
                        array_push($reportArray, $value);
                    }

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
                $event->sheet->getDelegate()->getStyle('A1:EZ1')
                    ->getFont()
                    ->setBold(true);

                $event->sheet->getDelegate()->getStyle('A2:EZ' . count($this->dataSetArray))->getNumberFormat()->setFormatCode('0');

                $length = sizeof($this->dataSetArray) == 0 ? 1 : sizeof($this->dataSetArray) + 2;
                $event->sheet->autoSize(true);
                for ($i=0; $i <= $length; $i++) {
                    if ($i !== 0){
                        $event->sheet->getRowDimension($i)->setRowHeight(16);
                    }
                }

            },
        ];
    }
}

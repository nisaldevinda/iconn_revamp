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

Sheet::macro('styleCells', function (Sheet $sheet, string $cellRange, array $style) {
    $sheet->getDelegate()->getStyle($cellRange)->applyFromArray($style);
});

class DocumentManagerAcknowledgedReport implements WithEvents, FromArray
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
            $documentManagerArrays = [];
            array_push($documentManagerArrays, $this->headerArray);

            foreach ($this->dataSetArray as $key => $dataSet) {
               
                $reportArray = [
                      $dataSet->employeeName,
                      $dataSet->documentName,
                      $dataSet->documentDescription ,
                      $dataSet->name ,
                      $dataSet->size ,
                      $dataSet->isAcknowledged ? 'Yes' : 'No',
                      $dataSet->isAcknowledged ? $dataSet->acknowledgedDate : ''
                  ];
                
                array_push($documentManagerArrays, $reportArray);
            }

            return [
                $documentManagerArrays
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

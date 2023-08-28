<?php

namespace App\Library;

use App\Traits\JsonModelReader;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalaryIncrementExcelProcessor implements WithMultipleSheets
{
    private $data;

    use Exportable;
    use JsonModelReader;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->data as $sheet) {
            $sheets[] = new PayGradeSheet($sheet);
        }

        return $sheets;
    }
}

class PayGradeSheet implements WithTitle, WithHeadings, WithColumnFormatting, FromArray, ShouldAutoSize
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return $this->data['name'];
    }

    public function headings(): array
    {
        $headers = [
            'Employee Number',
            'Employee Name',
            'Effective Date'
        ];

        foreach ($this->data['salaryComponents'] as $salaryComponent) {
            $headers[] = $salaryComponent['name'];
        }

        return $headers;
    }

    public function columnFormats(): array
    {
        $colRange = 'C1:C'.(sizeof($this->data['employees']) + 2000);
        return [
            $colRange => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->data['employees'] as $employee) {
            $data[] = [
                $employee['employeeNumber'],
                $employee['employeeName'],
                date("Y-m-d")
            ];
        }

        return $data;
    }
}
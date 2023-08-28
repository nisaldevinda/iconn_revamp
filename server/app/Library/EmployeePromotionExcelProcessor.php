<?php

namespace App\Library;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeePromotionExcelProcessor implements WithHeadings, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    private $data;
    private $count;

    public function __construct($data)
    {
        $this->data = $data;
        $this->count = $this->data['employeeCounts'] ?? 10;
    }

    public function headings(): array
    {
        $headers = [
            'Employee Number', // A
            'Organization', // B
            'New Job Category', // C
            'New Job Title', // D
            'New Pay Grade', // E
            'Calendar Type', // F
            'Reporting Person', // G
            'Functional Reporting Person', // H
            'Location', // I
            'Promotion Type', // J
            'Promotion Reason', // K
            'Promotion Effective Date' // L
        ];

        return $headers;
    }

    public function columnFormats(): array
    {
        $colRange = 'L1:L' . $this->count;
        return [
            $colRange => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $afterSheet) {
                $sheet = $afterSheet->getSheet();

                // date sample data
                $sheet->setCellValue('L2', date('Y-m-d'));

                // change org column size
                $sheet->getColumnDimension('B')->setAutoSize(false);
                $sheet->getColumnDimension('B')->setWidth('100');

                // set org entities options
                $optionCount = 1;
                $sheet->setCellValue('AB1', 'Org Entities');
                $options = $this->data['orgEntities'] ?? [];
                $validation = $afterSheet->sheet->getCell('B2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AB' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AB$2:$AB$' . $optionCount);
                    $validation->setSqref('B2:B' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AB')->setVisible(false);

                // set job categories options
                $optionCount = 1;
                $sheet->setCellValue('AC1', 'Job Categories');
                $options = $this->data['jobCategories'] ?? [];
                $validation = $afterSheet->sheet->getCell('C2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AC' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AC$2:$AC$' . $optionCount);
                    $validation->setSqref('C2:C' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AC')->setVisible(false);

                // set job title options
                $optionCount = 1;
                $sheet->setCellValue('AD1', 'Job Titles');
                $options = $this->data['jobTitles'] ?? [];
                $validation = $afterSheet->sheet->getCell('D2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AD' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AD$2:$AD$' . $optionCount);
                    $validation->setSqref('D2:D' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AD')->setVisible(false);

                // set pay grade options
                $optionCount = 1;
                $sheet->setCellValue('AE1', 'Pay Grades');
                $options = $this->data['payGrades'] ?? [];
                $validation = $afterSheet->sheet->getCell('E2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AE' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AE$2:$AE$' . $optionCount);
                    $validation->setSqref('E2:E' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AE')->setVisible(false);

                // set calender type options
                $optionCount = 1;
                $sheet->setCellValue('AF1', 'Calender Types');
                $options = $this->data['workCalendars'] ?? [];
                $validation = $afterSheet->sheet->getCell('F2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AF' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AF$2:$AF$' . $optionCount);
                    $validation->setSqref('F2:F' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AF')->setVisible(false);

                // set reporting person options
                $optionCount = 1;
                $sheet->setCellValue('AG1', 'Reporting Persons');
                $options = $this->data['reportingPersons'] ?? [];
                $validation = $afterSheet->sheet->getCell('G2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AG' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AG$2:$AG$' . $optionCount);
                    $validation->setSqref('G2:G' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AG')->setVisible(false);

                // set functional reporting person options
                $optionCount = 1;
                $sheet->setCellValue('AH1', 'Functional Reporting Persons');
                $options = $this->data['reportingPersons'] ?? [];
                $validation = $afterSheet->sheet->getCell('H2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AH' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AH$2:$AH$' . $optionCount);
                    $validation->setSqref('H2:H' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AH')->setVisible(false);

                // set location options
                $optionCount = 1;
                $sheet->setCellValue('AI1', 'Locations');
                $options = $this->data['locations'] ?? [];
                $validation = $afterSheet->sheet->getCell('I2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AI' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AI$2:$AI$' . $optionCount);
                    $validation->setSqref('I2:I' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AI')->setVisible(false);

                // set promotion type options
                $optionCount = 1;
                $sheet->setCellValue('AJ1', 'Promotion Types');
                $options = $this->data['promotionTypes'] ?? [];
                $validation = $afterSheet->sheet->getCell('J2')
                    ->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                if (!empty($options)) {
                    foreach ($options as $option) {
                        $optionCount++;
                        $sheet->setCellValue('AJ' . $optionCount, $option->name);
                    }
                    $validation->setFormula1('Worksheet!$AJ$2:$AJ$' . $optionCount);
                    $validation->setSqref('J2:J' . ($this->count + 1));
                }
                $sheet->getColumnDimension('AJ')->setVisible(false);
            }
        ];
    }
}

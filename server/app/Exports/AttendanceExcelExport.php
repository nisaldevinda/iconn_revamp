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

class AttendanceExcelExport implements WithEvents, FromArray
{
    protected $employeeId;
    protected $fromDate;
    protected $toDate;
    protected $permittedEmployeeIds;
    public function __construct($employeeId, $fromDate, $toDate, $permittedEmployeeIds)
    {
        $this->employeeId = $employeeId;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->permittedEmployeeIds = $permittedEmployeeIds;
    }

    public function array(): array
    {
        try {
            $whereQuery = '';
            $whereInQuery = '';
            $attendanceArrays = [];
            $attendanceHeaders = [
                "Date",
                "Employee Name",
                "Shift Name",
                "Leave",
                "In Time",
                "Out Time",
                "Worked (Mins)",
                "Break (Mins)",
            ];
            array_push($attendanceArrays, $attendanceHeaders);

            if (count($this->permittedEmployeeIds) > 0) {
                $whereInQuery = "WHERE attendance_summary.employeeId IN (" . implode(",", $this->permittedEmployeeIds) . ")";
            } else {
                return [
                    $attendanceArrays
                ];
            }

            if ($this->employeeId && $this->fromDate) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $this->employeeId . " AND  attendance_summary.date >= '" . $this->fromDate . "' AND attendance_summary.date <= '" . $this->toDate . "'";
            } else if ($this->employeeId) {
                $whereQuery = "WHERE attendance_summary.employeeId = " . $this->employeeId;
            } else if ($this->fromDate) {
                $clause = "attendance_summary.date >= '" . $this->fromDate . "' AND attendance_summary.date <= '" . $this->toDate . "'";
                $whereQuery = empty($whereInQuery) ? "WHERE " . $clause : $whereInQuery . " AND " . $clause;
            } else {
                $whereQuery =  $whereInQuery;
            }

            $query = "
        SELECT 
            attendance_summary.*,
            workShifts.name as shiftName,
            CONCAT(employee.firstName, ' ', employee.lastName) as employeeName,
            workPattern.name as workPatternName
        FROM attendance_summary
            LEFT JOIN employee on attendance_summary.employeeId = employee.id
            LEFT JOIN workShifts ON workShifts.id = attendance_summary.shiftId
            LEFT JOIN workPatternWeekDay ON workShifts.id = workPatternWeekDay.workShiftId
            LEFT JOIN workPatternWeek ON workPatternWeekDay.workPatternWeekId = workPatternWeek.id
            LEFT JOIN workPattern ON workPatternWeek.workPatternId = workPattern.id
        {$whereQuery}
        GROUP BY attendance_summary.id
        ;";

            $attendanceSheets = DB::select($query);

            foreach ($attendanceSheets as $key => $attendance) {
                $attendanceArray = [
                    DateTime::createFromFormat('Y-m-d', $attendance->date)->format('d-m-Y'),
                    $attendance->employeeName,
                    is_null($attendance->shiftName) ? $attendance->workPatternName : $attendance->shiftName,
                    '-',
                    $attendance->actualIn,
                    $attendance->actualOut,
                    gmdate("H:i", $attendance->workTime * 60),
                    gmdate("H:i", $attendance->breakTime * 60)
                ];

                array_push($attendanceArrays, $attendanceArray);
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

                $event->sheet->getDelegate()->getStyle('A1:H1')
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('DD4B39');

                $event->sheet->getDelegate()->getStyle('A1:H1')
                    ->getFont()
                    ->setBold(true);
            },
        ];
    }
}

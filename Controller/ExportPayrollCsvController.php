<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Payroll\Service\PayrollService;
use OrangeHRM\Pim\Service\EmployeeService;

class ExportPayrollCsvController
{
    public function handle(Request $request)
    {
        $month = $this->getOptionalInt(
            $request->query->get('month')
        );
        $year = $this->getOptionalInt(
            $request->query->get('year')
        );

        $payrollService = new PayrollService();
        $employeeService = new EmployeeService();

        $runs = $payrollService->getPayrollRunsByPeriod(
            $month,
            $year
        );

        $period = implode(
            '-',
            array_filter([
                $year,
                $month !== null
                    ? str_pad((string)$month, 2, '0', STR_PAD_LEFT)
                    : null,
            ])
        );
        $filename = $period === ''
            ? 'payroll-report.csv'
            : "payroll-report-$period.csv";

        header('Content-Type: text/csv');
        header(
            "Content-Disposition: attachment; filename=$filename"
        );

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Employee',
            'Month',
            'Year',
            'Gross',
            'Deductions',
            'Taxes',
            'Net',
        ]);

        foreach ($runs as $run) {

            $employee = $employeeService
                ->getEmployeeByEmpNumber(
                    $run->getEmpNumber()
                );

            fputcsv($output, [
                trim(
                    $employee->getFirstName() . ' ' .
                    $employee->getLastName()
                ),
                $run->getPayrollMonth(),
                $run->getPayrollYear(),
                $run->getGrossSalary(),
                $run->getTotalDeductions(),
                $run->getTotalTaxes(),
                $run->getNetSalary(),
            ]);
        }

        fclose($output);

        exit;
    }

    private function getOptionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }
}

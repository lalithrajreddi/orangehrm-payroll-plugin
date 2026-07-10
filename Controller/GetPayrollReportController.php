<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollService;
use OrangeHRM\Pim\Service\EmployeeService;

class GetPayrollReportController
{
    public function handle(Request $request): Response
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

        $response = [];

        foreach ($runs as $run) {

            $employee = $employeeService
                ->getEmployeeByEmpNumber(
                    $run->getEmpNumber()
                );

            $response[] = [
                'employeeName' => trim(
                    $employee->getFirstName() . ' ' .
                    $employee->getLastName()
                ),
                'month' => $run->getPayrollMonth(),
                'year' => $run->getPayrollYear(),
                'grossSalary' => $run->getGrossSalary(),
                'deductions' => $run->getTotalDeductions(),
                'taxes' => $run->getTotalTaxes(),
                'netSalary' => $run->getNetSalary(),
            ];
        }

        $httpResponse = new Response();
        $httpResponse->headers->set(
            'Content-Type',
            'application/json'
        );
        $httpResponse->setContent(json_encode($response));

        return $httpResponse;
    }

    private function getOptionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }
}

<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Payroll\Service\PayrollService;
use OrangeHRM\Pim\Service\EmployeeService;

class GetPayrollRunsController
{
    public function handle(Request $request)
    {
        $payrollService = new PayrollService();

        $runs = $payrollService
            ->getPayrollRuns();

        $response = [];

        $employeeService =
            new EmployeeService();

        foreach ($runs as $run) {
            $employee =
                $employeeService
                    ->getEmployeeByEmpNumber(
                        $run->getEmpNumber()
                    );
            $response[] = [
                'id' => $run->getId(),
                'empNumber' => $run->getEmpNumber(),
                'employeeName' => trim(
                    $employee->getFirstName() . ' ' .
                    $employee->getLastName()
                ),
                'month' => $run->getPayrollMonth(),
                'year' => $run->getPayrollYear(),
                'grossSalary' => $run->getGrossSalary(),
                'netSalary' => $run->getNetSalary(),
                'status' => $run->getStatus(),
            ];
        }

        header('Content-Type: application/json');

        echo json_encode($response);

        exit;
    }
}
<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Payroll\Service\PayrollDraftService;
use OrangeHRM\Pim\Service\EmployeeService;

class GetPayrollDraftsController
{
    public function handle()
    {
        $service =
            new PayrollDraftService();

        $drafts =
            $service->getPayrollDrafts();

        $response = [];

        $employeeService = new EmployeeService();

        foreach ($drafts as $draft) {
            $employee = $employeeService->getEmployeeByEmpNumber(
                $draft->getEmpNumber()
            );
            $response[] = [
                'id' => $draft->getId(),
                'empNumber' => $draft->getEmpNumber(),
                'employeeName' => trim(
                    $employee->getFirstName() . ' ' .
                    $employee->getLastName()
                ),
                'month' => $draft->getPayrollMonth(),
                'year' => $draft->getPayrollYear(),
                'status' => $draft->getStatus(),
            ];
        }

        header(
            'Content-Type: application/json'
        );

        echo json_encode(
            $response
        );

        exit;
    }
}
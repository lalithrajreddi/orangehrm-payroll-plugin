<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Payroll\Service\PayrollService;

class GetEmployeePayrollPeriodsController
{
    public function handle(Request $request)
    {
        $empNumber = (int)$request->attributes->get('empNumber');

        $payrollService = new PayrollService();

        $runs = $payrollService
            ->getPayrollRunsByEmployee($empNumber);

        $response = [];

        $uniquePeriods = [];

        foreach ($runs as $run) {

            $key =
                $run->getPayrollMonth()
                . '-'
                . $run->getPayrollYear();

            if (isset($uniquePeriods[$key])) {
                continue;
            }

            $uniquePeriods[$key] = [
                'id' => $key,
                'month' => $run->getPayrollMonth(),
                'year' => $run->getPayrollYear(),
                'label' =>
                    date(
                        'F',
                        mktime(
                            0,
                            0,
                            0,
                            $run->getPayrollMonth(),
                            1
                        )
                    )
                    . ' '
                    . $run->getPayrollYear(),
            ];
        }

        $response = array_values($uniquePeriods);
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
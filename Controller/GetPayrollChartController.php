<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollService;

class GetPayrollChartController
{
    public function handle(Request $request): Response
    {
        $month = $this->getOptionalInt(
            $request->query->get('month')
        );
        $year = $this->getOptionalInt(
            $request->query->get('year')
        );

        $service = new PayrollService();

        $runs = $service->getPayrollRunsByPeriod(
            $month,
            $year
        );

        $monthly = [];

        foreach ($runs as $run) {

            $key =
                $run->getPayrollYear() .
                '-' .
                str_pad(
                    $run->getPayrollMonth(),
                    2,
                    '0',
                    STR_PAD_LEFT
                );

            if (!isset($monthly[$key])) {
                $monthly[$key] = 0;
            }

            $monthly[$key] +=
                (float)$run->getNetSalary();
        }

        ksort($monthly);

        $response = [];

        foreach ($monthly as $month => $cost) {

            $response[] = [
                'month' => $month,
                'payrollCost' => round(
                    $cost,
                    2
                ),
            ];
        }

        $httpResponse = new Response();
        $httpResponse->headers->set(
            'Content-Type',
            'application/json'
        );
        $httpResponse->setContent(
            json_encode($response)
        );

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

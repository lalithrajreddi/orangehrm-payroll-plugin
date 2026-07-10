<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollDashboardService;

class GetPayrollDashboardController
{
    public function handle(Request $request): Response
    {
        $month = $this->getOptionalInt(
            $request->query->get('month')
        );
        $year = $this->getOptionalInt(
            $request->query->get('year')
        );

        $service = new PayrollDashboardService();
        $response = new Response();
        $response->headers->set(
            'Content-Type',
            'application/json'
        );
        $response->setContent(
            json_encode(
                $service->getDashboardData($month, $year)
            )
        );

        return $response;
    }

    private function getOptionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }
}

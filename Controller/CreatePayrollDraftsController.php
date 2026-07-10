<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollDraftService;

class CreatePayrollDraftsController
{
    public function handle(
        Request $request
    ): Response {

        $data = json_decode(
            $request->getContent(),
            true
        );

        $service =
            new PayrollDraftService();

        $count =
            $service
                ->createDraftsForAllEmployees(
                    (int)$data['month'],
                    (int)$data['year']
                );

        $response =
            new Response();

        $response->setContent(
            json_encode([
                'success' => true,
                'count' => $count,
            ])
        );

        return $response;
    }
}
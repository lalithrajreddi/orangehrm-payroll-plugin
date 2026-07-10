<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollDraftService;

class DeletePayrollDraftItemController
{
    public function handle(
        Request $request
    ): Response {

        $service = new PayrollDraftService();

        $draftItem =
            $service->getPayrollDraftItemById(
                (int)$request->attributes->get('id')
            );

        if ($draftItem) {

            $service->deletePayrollDraftItem(
                $draftItem
            );
        }

        $response = new Response();

        $response->setContent(
            json_encode([
                'success' => true,
            ])
        );

        return $response;
    }
}
<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollDraftService;
use OrangeHRM\Entity\PayrollDraftItem;

class AddPayrollDraftItemController
{
    public function handle(
        Request $request
    ): Response {

        $service = new PayrollDraftService();

        $draftItem = new PayrollDraftItem();

        $draftItem->setDraftId(
            (int)$request->request->get('draftId')
        );

        $draftItem->setComponentName(
            $request->request->get('componentName')
        );

        $draftItem->setComponentType(
            $request->request->get('componentType')
        );

        $draftItem->setAmount(
            $request->request->get('amount')
        );

        $service->savePayrollDraftItem(
            $draftItem
        );

        $response = new Response();

        $response->setContent(
            json_encode([
                'success' => true,
            ])
        );

        return $response;
    }
}
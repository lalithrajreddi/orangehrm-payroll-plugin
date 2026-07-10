<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollDraftService;

class SavePayrollDraftController
{
    public function handle(Request $request): Response
    {
        $draftId = (int)$request->attributes->get('draftId');
        $service = new PayrollDraftService();
        $draft = $service->getPayrollDraftById($draftId);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if (!$draft) {
            $response->setStatusCode(404);
            $response->setContent(json_encode([
                'success' => false,
                'error' => 'Draft not found',
            ]));

            return $response;
        }

        if ($draft->getStatus() === 'GENERATED') {
            $response->setStatusCode(409);
            $response->setContent(json_encode([
                'success' => false,
                'error' => 'Generated payroll drafts cannot be edited',
            ]));

            return $response;
        }

        $draft->setStatus('EDITED');
        $service->savePayrollDraft($draft);

        $response->setContent(json_encode([
            'success' => true,
            'status' => $draft->getStatus(),
        ]));

        return $response;
    }
}

<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollDraftService;

class DeleteSelectedPayrollDraftsController
{
    public function handle(Request $request): Response
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if (
            !is_array($data) ||
            !isset($data['draftIds']) ||
            !is_array($data['draftIds'])
        ) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'success' => false,
                'error' => 'Invalid request payload',
            ]));

            return $response;
        }

        $service = new PayrollDraftService();
        $deleted = 0;

        foreach ($data['draftIds'] as $draftId) {
            $draftId = (int)$draftId;

            if ($draftId <= 0 || !$service->getPayrollDraftById($draftId)) {
                continue;
            }

            $service->deleteDraft($draftId);
            $deleted++;
        }

        $response->setContent(json_encode([
            'success' => true,
            'deleted' => $deleted,
        ]));

        return $response;
    }
}

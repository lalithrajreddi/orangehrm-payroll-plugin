<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollService;

class GenerateSelectedPayrollsController
{
    public function handle(Request $request): Response
    {
        $data = json_decode(
            $request->getContent(),
            true
        );

        if (
            !is_array($data) ||
            !isset($data['draftIds']) ||
            !is_array($data['draftIds'])
        ) {
            $response = new Response();
            $response->setStatusCode(400);
            $response->headers->set('Content-Type', 'application/json');

            $response->setContent(json_encode([
                'success' => false,
                'error' => 'Invalid request payload',
            ]));

            return $response;
        }

        $service = new PayrollService();

        $generated = 0;

        foreach ($data['draftIds'] as $draftId) {
            $service->generatePayrollFromDraft((int)$draftId);
            $generated++;
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        $response->setContent(json_encode([
            'success' => true,
            'generated' => $generated,
        ]));

        return $response;
    }
}

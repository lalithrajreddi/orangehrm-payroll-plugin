<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\Response;
use OrangeHRM\Payroll\Service\PayrollDraftImportService;
use RuntimeException;

class ImportPayrollDraftItemsController
{
    public function handle(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        if (!is_array($data) || !is_array($data['attachment'] ?? null)) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'success' => false,
                'error' => 'A spreadsheet attachment is required',
            ]));

            return $response;
        }

        try {
            $result = (new PayrollDraftImportService())
                ->import($data['attachment']);
            $response->setContent(json_encode([
                'success' => true,
                'result' => $result,
            ]));
        } catch (RuntimeException $exception) {
            $response->setStatusCode(400);
            $response->setContent(json_encode([
                'success' => false,
                'error' => $exception->getMessage(),
            ]));
        }

        return $response;
    }
}

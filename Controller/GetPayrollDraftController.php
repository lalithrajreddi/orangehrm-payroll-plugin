<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Payroll\Service\PayrollDraftService;

class GetPayrollDraftController
{
    public function handle(Request $request)
    {
        $draftId = (int)$request->attributes->get(
            'draftId'
        );

        $draftService =
            new PayrollDraftService();

        $draft =
            $draftService->getPayrollDraftById(
                $draftId
            );

        if (!$draft) {

            header(
                'Content-Type: application/json'
            );

            echo json_encode([
                'error' => 'Draft not found',
            ]);

            exit;
        }

        $items =
            $draftService->getPayrollDraftItems(
                $draftId
            );

        $grossSalary = 0;
        $totalAllowances = 0;
        $totalBonuses = 0;
        $totalDeductions = 0;
        $totalTaxes = 0;

        $response = [
            'id' => $draft->getId(),
            'empNumber' => $draft->getEmpNumber(),
            'month' => $draft->getPayrollMonth(),
            'year' => $draft->getPayrollYear(),
            'status' => $draft->getStatus(),
            'items' => [],
        ];

        foreach ($items as $item) {

            $amount =
                (float)$item->getAmount();

            switch (
                $item->getComponentType()
            ) {

                case 'BASE':
                    $grossSalary += $amount;
                    break;

                case 'ALLOWANCE':
                    $totalAllowances += $amount;
                    break;

                case 'BONUS':
                    $totalBonuses += $amount;
                    break;

                case 'DEDUCTION':
                    $totalDeductions += $amount;
                    break;

                case 'TAX':
                    $totalTaxes += $amount;
                    break;
            }

            $response['items'][] = [
                'id' => $item->getId(),
                'name' => $item->getComponentName(),
                'type' => $item->getComponentType(),
                'amount' => $item->getAmount(),
                'isSystemGenerated' => $item->getIsSystemGenerated(),
            ];
        }

        $netSalary =
            $grossSalary +
            $totalAllowances +
            $totalBonuses -
            $totalDeductions -
            $totalTaxes;

        $response['summary'] = [
            'grossSalary' => number_format(
                $grossSalary,
                2,
                '.',
                ''
            ),
            'allowances' => number_format(
                $totalAllowances,
                2,
                '.',
                ''
            ),
            'bonuses' => number_format(
                $totalBonuses,
                2,
                '.',
                ''
            ),
            'deductions' => number_format(
                $totalDeductions,
                2,
                '.',
                ''
            ),
            'taxes' => number_format(
                $totalTaxes,
                2,
                '.',
                ''
            ),
            'netSalary' => number_format(
                $netSalary,
                2,
                '.',
                ''
            ),
        ];

        header(
            'Content-Type: application/json'
        );

        echo json_encode(
            $response
        );

        exit;
    }
}
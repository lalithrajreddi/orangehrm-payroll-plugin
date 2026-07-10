<?php

namespace OrangeHRM\Payroll\Service;

use OrangeHRM\Pim\Service\EmployeeService;
use OrangeHRM\Pim\Dto\EmployeeSearchFilterParams;

class PayrollDashboardService
{
    public function getDashboardData(
        ?int $month = null,
        ?int $year = null
    ): array
    {
        $employeeService = new EmployeeService();
        $payrollService = new PayrollService();
        $draftService = new PayrollDraftService();

        $employees = $employeeService->getEmployeeList(
            new EmployeeSearchFilterParams()
        );

        $drafts = $draftService->getPayrollDraftsByPeriod(
            $month,
            $year
        );
        $runs = $payrollService->getPayrollRunsByPeriod(
            $month,
            $year
        );

        $payrollCost = 0;
        $totalGross = 0;
        $totalDeductions = 0;
        $totalTaxes = 0;
        $totalNet = 0;

        foreach ($runs as $run) {
            $payrollCost +=
                (float)$run->getNetSalary();

            $totalGross +=
                (float)$run->getGrossSalary();

            $totalDeductions +=
                (float)$run->getTotalDeductions();

            $totalTaxes +=
                (float)$run->getTotalTaxes();

            $totalNet +=
                (float)$run->getNetSalary();
        }

        $pendingDrafts = array_filter(
            $drafts,
            static fn ($draft): bool =>
                in_array(
                    $draft->getStatus(),
                    ['DRAFT', 'EDITED'],
                    true
                )
        );

        $coveredPayrollPeriods = [];

        foreach ($drafts as $draft) {
            $key = implode('-', [
                $draft->getEmpNumber(),
                $draft->getPayrollMonth(),
                $draft->getPayrollYear(),
            ]);
            $coveredPayrollPeriods[$key] = true;
        }

        foreach ($runs as $run) {
            $key = implode('-', [
                $run->getEmpNumber(),
                $run->getPayrollMonth(),
                $run->getPayrollYear(),
            ]);
            $coveredPayrollPeriods[$key] = true;
        }

        $expectedDrafts = $year !== null
            ? count($employees) * ($month === null ? 12 : 1)
            : 0;
        $draftsNeeded = max(
            $expectedDrafts - count($coveredPayrollPeriods),
            0
        );

        return [
            'employees' => count($employees),
            'drafts' => count($drafts),
            'generated' => count($runs),
            'pendingDrafts' => count($pendingDrafts),
            'draftsNeeded' => $draftsNeeded,
            'payrollCost' => round(
                $payrollCost,
                2
            ),
            'totalGross' => round($totalGross, 2),
            'totalDeductions' => round($totalDeductions, 2),
            'totalTaxes' => round($totalTaxes, 2),
            'totalNet' => round($totalNet, 2),
        ];
    }
}

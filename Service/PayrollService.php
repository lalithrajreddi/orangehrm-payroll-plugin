<?php

namespace OrangeHRM\Payroll\Service;

use OrangeHRM\Entity\PayrollRun;
use OrangeHRM\Entity\PayrollItem;
use OrangeHRM\Payroll\Dao\PayrollDao;

class PayrollService
{
    private ?PayrollDao $payrollDao = null;

    public function getPayrollDao(): PayrollDao
    {
        if (!$this->payrollDao instanceof PayrollDao) {
            $this->payrollDao = new PayrollDao();
        }

        return $this->payrollDao;
    }

    public function savePayrollRun(
        PayrollRun $payrollRun
    ): PayrollRun {
        return $this->getPayrollDao()
            ->savePayrollRun($payrollRun);
    }

    public function savePayrollItem(
        PayrollItem $payrollItem
    ): PayrollItem {
        return $this->getPayrollDao()
            ->savePayrollItem($payrollItem);
    }

    public function getPayrollItems(
        int $payrollRunId
    ): array {

        return $this->getPayrollDao()
            ->getPayrollItemsByRunId($payrollRunId);
    }

    public function getPayrollRuns(): array
    {
        return $this->getPayrollDao()
            ->getPayrollRuns();
    }

    public function getPayrollRunsByPeriod(
        ?int $month = null,
        ?int $year = null
    ): array {
        return $this->getPayrollDao()
            ->getPayrollRunsByPeriod($month, $year);
    }

    public function getPayrollRunByEmployeeAndPeriod(
        int $empNumber,
        int $month,
        int $year
    ): ?PayrollRun {

        return $this->getPayrollDao()
            ->getPayrollRunByEmployeeAndPeriod(
                $empNumber,
                $month,
                $year
            );
    }
    
    public function getPayrollRunsByEmployee(
        int $empNumber
    ): array {

        return $this->getPayrollDao()
            ->getPayrollRunsByEmployee(
                $empNumber
            );
    }
    
    public function generatePayrollFromDraft(
        int $draftId
    ): PayrollRun {

        $draftService =
            new PayrollDraftService();

        $draft =
            $draftService
                ->getPayrollDraftById(
                    $draftId
                );

        if (!$draft) {
            throw new \Exception(
                'Draft not found'
            );
        }

        $items =
            $draftService
                ->getPayrollDraftItems(
                    $draftId
                );

        $grossSalary = 0;
        $allowances = 0;
        $bonuses = 0;
        $deductions = 0;
        $taxes = 0;

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
                    $allowances += $amount;
                    break;

                case 'BONUS':
                    $bonuses += $amount;
                    break;

                case 'DEDUCTION':
                    $deductions += $amount;
                    break;

                case 'TAX':
                    $taxes += $amount;
                    break;
            }
        }

        $netSalary =
            $grossSalary +
            $allowances +
            $bonuses -
            $deductions -
            $taxes;

        $payrollRun =
            new PayrollRun();

        $payrollRun->setEmpNumber(
            $draft->getEmpNumber()
        );

        $payrollRun->setPayrollMonth(
            $draft->getPayrollMonth()
        );

        $payrollRun->setPayrollYear(
            $draft->getPayrollYear()
        );

        $payrollRun->setStatus(
            'Generated'
        );

        $payrollRun->setGrossSalary(
            number_format(
                $grossSalary,
                2,
                '.',
                ''
            )
        );

        $payrollRun->setTotalAllowances(
            number_format(
                $allowances,
                2,
                '.',
                ''
            )
        );

        $payrollRun->setTotalBonuses(
            number_format(
                $bonuses,
                2,
                '.',
                ''
            )
        );

        $payrollRun->setTotalTaxes(
            number_format(
                $taxes,
                2,
                '.',
                ''
            )
        );

        $payrollRun->setTotalAdditions(
            number_format(
                $allowances +
                $bonuses,
                2,
                '.',
                ''
            )
        );

        $payrollRun->setTotalDeductions(
            number_format(
                $deductions +
                $taxes,
                2,
                '.',
                ''
            )
        );

        $payrollRun->setNetSalary(
            number_format(
                $netSalary,
                2,
                '.',
                ''
            )
        );

        $this
            ->savePayrollRun(
                $payrollRun
            );

        foreach ($items as $draftItem) {

            $item =
                new PayrollItem();

            $item->setPayrollRunId(
                $payrollRun->getId()
            );

            $item->setComponentName(
                $draftItem
                    ->getComponentName()
            );

            $item->setComponentType(
                $draftItem
                    ->getComponentType()
            );

            $item->setAmount(
                $draftItem
                    ->getAmount()
            );

            $this
                ->savePayrollItem(
                    $item
                );
        }

        $draft->setStatus(
            'GENERATED'
        );

        $draftService->savePayrollDraft(
            $draft
        );

        return $payrollRun;
    }

}

<?php

namespace OrangeHRM\Payroll\Service;

use OrangeHRM\Payroll\Dao\PayrollDao;
use OrangeHRM\Pim\Service\EmployeeService;
use OrangeHRM\Pim\Dto\EmployeeSearchFilterParams;
use OrangeHRM\Pim\Service\EmployeeSalaryService;
use OrangeHRM\Entity\PayrollDraft;
use OrangeHRM\Entity\PayrollDraftItem;
use OrangeHRM\Payroll\Service\PayrollSettingsService;

class PayrollDraftService
{
    private ?PayrollDao $payrollDao = null;

    public function getPayrollDao(): PayrollDao
    {
        if (!$this->payrollDao instanceof PayrollDao) {
            $this->payrollDao = new PayrollDao();
        }

        return $this->payrollDao;
    }

    public function savePayrollDraft(
        PayrollDraft $payrollDraft
    ): PayrollDraft {

        return $this->getPayrollDao()
            ->savePayrollDraft($payrollDraft);
    }

    public function savePayrollDraftItem(
        PayrollDraftItem $payrollDraftItem
    ): PayrollDraftItem {

        return $this->getPayrollDao()
            ->savePayrollDraftItem($payrollDraftItem);
    }

    public function getPayrollDraftById(
        int $id
    ): ?PayrollDraft {

        return $this->getPayrollDao()
            ->getPayrollDraftById($id);
    }

    public function getPayrollDraftItems(
        int $draftId
    ): array {

        return $this->getPayrollDao()
            ->getPayrollDraftItems($draftId);
    }

    public function getPayrollDraftItemById(
        int $id
    ): ?PayrollDraftItem {

        return $this->getPayrollDao()
            ->getPayrollDraftItemById($id);
    }

    public function deletePayrollDraftItem(
        PayrollDraftItem $payrollDraftItem
    ): void {

        $this->getPayrollDao()
            ->deletePayrollDraftItem(
                $payrollDraftItem
            );
    }

    public function getPayrollDraftByEmployeeAndPeriod(
        int $empNumber,
        int $month,
        int $year
    ): ?PayrollDraft {

        return $this->getPayrollDao()
            ->getPayrollDraftByEmployeeAndPeriod(
                $empNumber,
                $month,
                $year
            );
    }

    public function getPayrollDrafts(): array
    {
        return $this->getPayrollDao()
            ->getPayrollDrafts();
    }

    public function getPayrollDraftsByPeriod(
        ?int $month = null,
        ?int $year = null,
        ?string $status = null
    ): array {
        return $this->getPayrollDao()
            ->getPayrollDraftsByPeriod(
                $month,
                $year,
                $status
            );
    }

    public function createDraftsForAllEmployees(
        int $month,
        int $year
    ): int {

        $settingsService =
            new PayrollSettingsService();

        $pfPercentage =
            (float)$settingsService->getSettingValue(
                'PF_PERCENTAGE'
            );

        $esiPercentage =
            (float)$settingsService->getSettingValue(
                'ESI_PERCENTAGE'
            );

        $tdsPercentage =
            (float)$settingsService->getSettingValue(
                'TDS_PERCENTAGE'
            );

        $professionalTax =
            (float)$settingsService->getSettingValue(
                'PROFESSIONAL_TAX'
            );

        $employeeService =
            new EmployeeService();

        $salaryService =
            new EmployeeSalaryService();

        $employees =
            $employeeService->getEmployeeList(
                new EmployeeSearchFilterParams()
            );

        $count = 0;

        foreach ($employees as $employee) {

            $empNumber =
                $employee->getEmpNumber();

            $existingDraft =
                $this->getPayrollDraftByEmployeeAndPeriod(
                    $empNumber,
                    $month,
                    $year
                );

            if ($existingDraft) {
                continue;
            }

            $draft =
                new PayrollDraft();

            $draft->setEmpNumber(
                $empNumber
            );

            $draft->setPayrollMonth(
                $month
            );

            $draft->setPayrollYear(
                $year
            );

            $draft->setStatus(
                'DRAFT'
            );

            $draft->setCreatedAt(
                new \DateTime()
            );

            $this->savePayrollDraft(
                $draft
            );

            $salaryComponents =
                $salaryService
                    ->getEmployeeSalaryList(
                        $empNumber
                    );

            $totalBaseSalary = 0;

            foreach (
                $salaryComponents as $salary
            ) {

                $item =
                    new PayrollDraftItem();

                $item->setDraftId(
                    $draft->getId()
                );

                $item->setComponentName(
                    $salary->getSalaryName()
                );

                $item->setComponentType(
                    'BASE'
                );

                $item->setAmount(
                    $salary->getAmount()
                );

                $item->setIsSystemGenerated(true);

                $this->savePayrollDraftItem(
                    $item
                );

                $totalBaseSalary +=
                    (float)$salary->getAmount();
            }

            // PF Deduction

            $pfItem =
                new PayrollDraftItem();

            $pfItem->setDraftId(
                $draft->getId()
            );

            $pfItem->setComponentName(
                'PF Deduction'
            );

            $pfItem->setComponentType(
                'DEDUCTION'
            );

            $pfItem->setAmount(
                round(
                    $totalBaseSalary *
                    ($pfPercentage / 100),
                    2
                )
            );

            $pfItem->setIsSystemGenerated(true);

            $this->savePayrollDraftItem(
                $pfItem
            );

            // ESI Deduction

            $esiItem =
                new PayrollDraftItem();

            $esiItem->setDraftId(
                $draft->getId()
            );

            $esiItem->setComponentName(
                'ESI Deduction'
            );

            $esiItem->setComponentType(
                'DEDUCTION'
            );

            $esiItem->setAmount(
                round(
                    $totalBaseSalary *
                    ($esiPercentage / 100),
                    2
                )
            );

            $esiItem->setIsSystemGenerated(true);

            $this->savePayrollDraftItem(
                $esiItem
            );

            // TDS

            $tdsItem =
                new PayrollDraftItem();

            $tdsItem->setDraftId(
                $draft->getId()
            );

            $tdsItem->setComponentName(
                'TDS'
            );

            $tdsItem->setComponentType(
                'TAX'
            );

            $tdsItem->setAmount(
                round(
                    $totalBaseSalary *
                    ($tdsPercentage / 100),
                    2
                )
            );

            $tdsItem->setIsSystemGenerated(true);

            $this->savePayrollDraftItem(
                $tdsItem
            );

            // Professional Tax

            $taxItem =
                new PayrollDraftItem();

            $taxItem->setDraftId(
                $draft->getId()
            );

            $taxItem->setComponentName(
                'Professional Tax'
            );

            $taxItem->setComponentType(
                'TAX'
            );

            $taxItem->setAmount(
                $professionalTax
            );

            $taxItem->setIsSystemGenerated(true);

            $this->savePayrollDraftItem(
                $taxItem
            );

            $count++;
        }

        return $count;
    }

    public function deleteDraft(
        int $draftId
    ): void {

        $items =
            $this->getPayrollDraftItems(
                $draftId
            );

        foreach ($items as $item) {

            $this->deletePayrollDraftItem(
                $item
            );
        }

        $draft =
            $this->getPayrollDraftById(
                $draftId
            );

        if ($draft) {

            $this->getPayrollDao()
                ->deletePayrollDraft(
                    $draft
                );
        }
    }

}

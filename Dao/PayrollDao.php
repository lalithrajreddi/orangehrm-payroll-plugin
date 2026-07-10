<?php

namespace OrangeHRM\Payroll\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\PayrollRun;
use OrangeHRM\Entity\PayrollItem;
use OrangeHRM\Entity\PayrollDraft;
use OrangeHRM\Entity\PayrollDraftItem;

class PayrollDao extends BaseDao
{
    /**
     * Save Payroll Run
     */
    public function savePayrollRun(PayrollRun $payrollRun): PayrollRun
    {
        $this->persist($payrollRun);
        return $payrollRun;
    }

    /**
     * Save Payroll Item
     */
    public function savePayrollItem(PayrollItem $payrollItem): PayrollItem
    {
        $this->persist($payrollItem);
        return $payrollItem;
    }

    /**
     * Get Payroll Items By Run Id
     */
    public function getPayrollItemsByRunId(int $payrollRunId): array
    {
        return $this->getRepository(PayrollItem::class)
            ->findBy([
                'payrollRunId' => $payrollRunId,
            ]);
    }

    public function getPayrollRuns(): array
    {
        return $this->getPayrollRunsByPeriod();
    }

    public function getPayrollRunsByPeriod(
        ?int $month = null,
        ?int $year = null
    ): array {
        $query = $this->createQueryBuilder(
            PayrollRun::class,
            'pr'
        );

        if ($month !== null) {
            $query
                ->andWhere('pr.payrollMonth = :month')
                ->setParameter('month', $month);
        }

        if ($year !== null) {
            $query
                ->andWhere('pr.payrollYear = :year')
                ->setParameter('year', $year);
        }

        return $query
            ->orderBy('pr.payrollYear', 'DESC')
            ->addOrderBy('pr.payrollMonth', 'DESC')
            ->addOrderBy('pr.id', 'DESC')
            ->getQuery()
            ->execute();
    }

    public function getPayrollRunByEmployeeAndPeriod(
        int $empNumber,
        int $month,
        int $year
    ): ?PayrollRun {

        return $this->getRepository(
            PayrollRun::class
        )->findOneBy([
            'empNumber' => $empNumber,
            'payrollMonth' => $month,
            'payrollYear' => $year,
        ]);
    }

    public function getPayrollRunsByEmployee(
        int $empNumber
    ): array {

        return $this->createQueryBuilder(
            PayrollRun::class,
            'pr'
        )
            ->andWhere(
                'pr.empNumber = :empNumber'
            )
            ->setParameter(
                'empNumber',
                $empNumber
            )
            ->orderBy(
                'pr.payrollYear',
                'DESC'
            )
            ->addOrderBy(
                'pr.payrollMonth',
                'DESC'
            )
            ->getQuery()
            ->execute();
    }

    public function savePayrollDraft(
        PayrollDraft $payrollDraft
    ): PayrollDraft {

        $this->persist($payrollDraft);

        return $payrollDraft;
    }

    public function savePayrollDraftItem(
        PayrollDraftItem $payrollDraftItem
    ): PayrollDraftItem {

        $this->persist($payrollDraftItem);

        return $payrollDraftItem;
    }

    public function getPayrollDraftById(
        int $id
    ): ?PayrollDraft {

        return $this->getRepository(
            PayrollDraft::class
        )->find($id);
    }

    public function getPayrollDraftByEmployeeAndPeriod(
        int $empNumber,
        int $month,
        int $year
    ): ?PayrollDraft {

        return $this->getRepository(
            PayrollDraft::class
        )->findOneBy([
            'empNumber' => $empNumber,
            'payrollMonth' => $month,
            'payrollYear' => $year,
        ]);
    }

    public function getPayrollDraftItems(
        int $draftId
    ): array {

        return $this->createQueryBuilder(
            PayrollDraftItem::class,
            'pdi'
        )
            ->andWhere(
                'pdi.draftId = :draftId'
            )
            ->setParameter(
                'draftId',
                $draftId
            )
            ->getQuery()
            ->execute();
    }

    public function getPayrollDraftItemById(
        int $id
    ): ?PayrollDraftItem {

        return $this->getRepository(
            PayrollDraftItem::class
        )->find($id);
    }

    public function deletePayrollDraftItem(
        PayrollDraftItem $payrollDraftItem
    ): void {

        $this->remove($payrollDraftItem);
    }

    public function getPayrollDrafts(): array
    {
        return $this->createQueryBuilder(
            PayrollDraft::class,
            'pd'
        )
            ->andWhere('pd.status IN (:statuses)')
            ->setParameter('statuses', ['DRAFT', 'EDITED'])
            ->orderBy('pd.id', 'DESC')
            ->getQuery()
            ->execute();
    }

    public function getPayrollDraftsByPeriod(
        ?int $month = null,
        ?int $year = null,
        ?string $status = null
    ): array {
        $query = $this->createQueryBuilder(
            PayrollDraft::class,
            'pd'
        );

        if ($month !== null) {
            $query
                ->andWhere('pd.payrollMonth = :month')
                ->setParameter('month', $month);
        }

        if ($year !== null) {
            $query
                ->andWhere('pd.payrollYear = :year')
                ->setParameter('year', $year);
        }

        if ($status !== null) {
            $query
                ->andWhere('pd.status = :status')
                ->setParameter('status', $status);
        }

        return $query
            ->orderBy('pd.id', 'DESC')
            ->getQuery()
            ->execute();
    }

    public function deletePayrollDraft(
        PayrollDraft $draft
    ): void {

        $this->remove($draft);
    }
}

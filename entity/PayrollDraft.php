<?php

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_payroll_draft")
 * @ORM\Entity
 */
class PayrollDraft
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="emp_number", type="integer")
     */
    private int $empNumber;

    /**
     * @ORM\Column(name="payroll_month", type="integer")
     */
    private int $payrollMonth;

    /**
     * @ORM\Column(name="payroll_year", type="integer")
     */
    private int $payrollYear;

    /**
     * @ORM\Column(name="status", type="string", length=20)
     */
    private string $status = 'DRAFT';

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private ?\DateTime $createdAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmpNumber(): int
    {
        return $this->empNumber;
    }

    public function setEmpNumber(int $empNumber): void
    {
        $this->empNumber = $empNumber;
    }

    public function getPayrollMonth(): int
    {
        return $this->payrollMonth;
    }

    public function setPayrollMonth(int $payrollMonth): void
    {
        $this->payrollMonth = $payrollMonth;
    }

    public function getPayrollYear(): int
    {
        return $this->payrollYear;
    }

    public function setPayrollYear(int $payrollYear): void
    {
        $this->payrollYear = $payrollYear;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
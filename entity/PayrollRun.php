<?php

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_payroll_run")
 * @ORM\Entity
 */
class PayrollRun
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
    private string $status = 'Draft';

    /**
     * @ORM\Column(name="generated_at", type="datetime", nullable=true)
     */
    private ?\DateTime $generatedAt = null;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private ?\DateTime $createdAt = null;

    /**
     * @ORM\Column(name="gross_salary", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $grossSalary = null;

    /**
     * @ORM\Column(name="total_additions", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $totalAdditions = null;

    /**
     * @ORM\Column(name="total_deductions", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $totalDeductions = null;

    /**
     * @ORM\Column(name="net_salary", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $netSalary = null;

    /**
     * @ORM\Column(name="total_allowances", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $totalAllowances = null;

    /**
     * @ORM\Column(name="total_bonuses", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $totalBonuses = null;

    /**
     * @ORM\Column(name="total_taxes", type="decimal", precision=12, scale=2, nullable=true)
     */
    private ?string $totalTaxes = null;

    
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
    public function getGrossSalary(): ?string
    {
        return $this->grossSalary;
    }

    public function setGrossSalary(?string $grossSalary): void
    {
        $this->grossSalary = $grossSalary;
    }

    public function getTotalAdditions(): ?string
    {
        return $this->totalAdditions;
    }

    public function setTotalAdditions(?string $totalAdditions): void
    {
        $this->totalAdditions = $totalAdditions;
    }

    public function getTotalDeductions(): ?string
    {
        return $this->totalDeductions;
    }

    public function setTotalDeductions(?string $totalDeductions): void
    {
        $this->totalDeductions = $totalDeductions;
    }

    public function getNetSalary(): ?string
    {
        return $this->netSalary;
    }

    public function setNetSalary(?string $netSalary): void
    {
        $this->netSalary = $netSalary;
    }

    public function getTotalAllowances(): ?string
    {
        return $this->totalAllowances;
    }

    public function setTotalAllowances(?string $totalAllowances): void
    {
        $this->totalAllowances = $totalAllowances;
    }

    public function getTotalBonuses(): ?string
    {
        return $this->totalBonuses;
    }

    public function setTotalBonuses(?string $totalBonuses): void
    {
        $this->totalBonuses = $totalBonuses;
    }

    public function getTotalTaxes(): ?string
    {
        return $this->totalTaxes;
    }

    public function setTotalTaxes(?string $totalTaxes): void
    {
        $this->totalTaxes = $totalTaxes;
    }
}
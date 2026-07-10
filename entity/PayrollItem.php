<?php

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_payroll_item")
 * @ORM\Entity
 */
class PayrollItem
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="payroll_run_id", type="integer")
     */
    private int $payrollRunId;

    /**
     * @ORM\Column(name="component_name", type="string", length=100)
     */
    private string $componentName;

    /**
     * @ORM\Column(name="component_type", type="string", length=20)
     */
    private string $componentType;

    /**
     * @ORM\Column(name="amount", type="decimal", precision=12, scale=2)
     */
    private string $amount;

    public function getId(): int
    {
        return $this->id;
    }

    public function getPayrollRunId(): int
    {
        return $this->payrollRunId;
    }

    public function setPayrollRunId(int $payrollRunId): void
    {
        $this->payrollRunId = $payrollRunId;
    }

    public function getComponentName(): string
    {
        return $this->componentName;
    }

    public function setComponentName(string $componentName): void
    {
        $this->componentName = $componentName;
    }

    public function getComponentType(): string
    {
        return $this->componentType;
    }

    public function setComponentType(string $componentType): void
    {
        $this->componentType = $componentType;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }
}
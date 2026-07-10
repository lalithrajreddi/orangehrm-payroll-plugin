<?php

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_payroll_draft_item")
 * @ORM\Entity
 */
class PayrollDraftItem
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="draft_id", type="integer")
     */
    private int $draftId;

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


    /**
     * @ORM\Column(name="is_system_generated", type="boolean")
     */
    private bool $isSystemGenerated = false;


    public function getId(): int
    {
        return $this->id;
    }

    public function getDraftId(): int
    {
        return $this->draftId;
    }

    public function setDraftId(int $draftId): void
    {
        $this->draftId = $draftId;
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

    public function getIsSystemGenerated(): bool
    {
        return $this->isSystemGenerated;
    }

    public function setIsSystemGenerated(bool $isSystemGenerated): void
    {
        $this->isSystemGenerated = $isSystemGenerated;
    }    
    
}
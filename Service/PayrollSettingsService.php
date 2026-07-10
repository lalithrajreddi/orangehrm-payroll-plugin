<?php

namespace OrangeHRM\Payroll\Service;

use OrangeHRM\Payroll\Dao\PayrollSettingsDao;

class PayrollSettingsService
{
    private ?PayrollSettingsDao $dao = null;

    public function getDao(): PayrollSettingsDao
    {
        if (!$this->dao instanceof PayrollSettingsDao) {
            $this->dao = new PayrollSettingsDao();
        }

        return $this->dao;
    }

    public function getSettingValue(
        string $key
    ): string {

        $setting =
            $this->getDao()->getSetting($key);

        return $setting
            ? $setting->getSettingValue()
            : '0';
    }

    public function getAllSettings(): array
    {
        return $this->getDao()
            ->getAllSettings();
    }
}
<?php

namespace OrangeHRM\Payroll\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\PayrollSetting;

class PayrollSettingsDao extends BaseDao
{
    public function getSetting(string $key): ?PayrollSetting
    {
        return $this->getRepository(
            PayrollSetting::class
        )->findOneBy([
            'settingKey' => $key,
        ]);
    }

    public function getAllSettings(): array
    {
        return $this->getRepository(
            PayrollSetting::class
        )->findAll();
    }

    public function saveSetting(
        PayrollSetting $setting
    ): PayrollSetting {

        $this->persist($setting);

        return $setting;
    }
}
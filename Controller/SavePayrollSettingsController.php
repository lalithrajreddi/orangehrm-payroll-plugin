<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Payroll\Service\PayrollSettingsService;
use OrangeHRM\Entity\PayrollSetting;

class SavePayrollSettingsController
{
    public function handle()
    {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $service = new PayrollSettingsService();

        foreach ($data as $key => $value) {

            $setting = $service
                ->getDao()
                ->getSetting($key);

            if (!$setting) {
                $setting = new PayrollSetting();
                $setting->setSettingKey($key);
            }

            $setting->setSettingValue(
                (string)$value
            );

            $service
                ->getDao()
                ->saveSetting($setting);
        }

        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'message' => 'Settings saved successfully',
        ]);

        exit;
    }
}
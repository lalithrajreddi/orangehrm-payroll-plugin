<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Payroll\Service\PayrollSettingsService;

class GetPayrollSettingsController
{
    public function handle()
    {
        $service = new PayrollSettingsService();

        $settings = [];

        foreach ($service->getAllSettings() as $setting) {
            $settings[$setting->getSettingKey()] =
                $setting->getSettingValue();
        }

        header('Content-Type: application/json');

        echo json_encode($settings);

        exit;
    }
}
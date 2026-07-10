<?php

use OrangeHRM\Core\Traits\EventDispatcherTrait;
use OrangeHRM\Core\Traits\ServiceContainerTrait;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\PluginConfigurationInterface;
use OrangeHRM\Framework\Services;
use OrangeHRM\Payroll\Installer\PayrollInstaller;

class PayrollPluginConfiguration implements PluginConfigurationInterface
{
    use ServiceContainerTrait;
    use EventDispatcherTrait;

    /**
     * @inheritDoc
     */
    public function initialize(Request $request): void
    {
        $entityManager = $this->getContainer()->get(Services::DOCTRINE);
        $connection = $entityManager->getConnection();
        
        try {
            $schemaManager = $connection->createSchemaManager();
            if (!$schemaManager->tablesExist(['ohrm_payroll_settings'])) {
                $installer = new PayrollInstaller($connection);
                $installer->install();
            }
        } catch (\Exception $e) {
            // Silence exceptions (e.g. during a fresh installation if database is not fully connected yet)
        }
    }
}

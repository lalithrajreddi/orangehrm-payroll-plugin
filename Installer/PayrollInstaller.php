<?php

namespace OrangeHRM\Payroll\Installer;

use Doctrine\DBAL\Connection;

class PayrollInstaller
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function install(): void
    {
        // Auto-create/update the database schema tables for the payroll entities
        try {
            $entityManager = \OrangeHRM\ORM\Doctrine::getEntityManager();
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($entityManager);
            $classes = [
                $entityManager->getClassMetadata(\OrangeHRM\Entity\PayrollRun::class),
                $entityManager->getClassMetadata(\OrangeHRM\Entity\PayrollDraft::class),
                $entityManager->getClassMetadata(\OrangeHRM\Entity\PayrollDraftItem::class),
                $entityManager->getClassMetadata(\OrangeHRM\Entity\PayrollItem::class),
                $entityManager->getClassMetadata(\OrangeHRM\Entity\PayrollSetting::class),
            ];
            $schemaTool->updateSchema($classes, true);
        } catch (\Exception $e) {
            // Ignore database connection failures if the wizard is not fully completed yet
        }

        $moduleId = $this->createModule();

        // 1. Create Screens
        $viewPayrollModuleScreenId = $this->getOrCreateScreen('View Payroll Module', 'viewPayrollModule', $moduleId);
        $dashboardScreenId         = $this->getOrCreateScreen('Payroll Dashboard', 'viewPayrollDashboard', $moduleId);
        $managementScreenId        = $this->getOrCreateScreen('Payroll Management', 'viewPayrollManagement', $moduleId);
        $settingsScreenId          = $this->getOrCreateScreen('Payroll Settings', 'viewPayrollSettings', $moduleId);
        $reportsScreenId           = $this->getOrCreateScreen('Payroll Reports', 'viewPayrollReports', $moduleId);
        $payslipsScreenId          = $this->getOrCreateScreen('Employee Salary Slips', 'viewEmployeeSalarySlips', $moduleId);
        $historyScreenId           = $this->getOrCreateScreen('Payroll History', 'viewPayrollHistory', $moduleId);

        // 2. Authorize Screens for Roles
        // Role 1 (Admin) Screen Mappings
        $this->authorizeScreen(1, $viewPayrollModuleScreenId, 1, 0, 0, 0);
        $this->authorizeScreen(1, $dashboardScreenId, 1, 1, 1, 1);
        $this->authorizeScreen(1, $managementScreenId, 1, 1, 1, 1);
        $this->authorizeScreen(1, $settingsScreenId, 1, 1, 1, 1);
        $this->authorizeScreen(1, $reportsScreenId, 1, 1, 1, 1);
        $this->authorizeScreen(1, $payslipsScreenId, 1, 1, 1, 1);
        $this->authorizeScreen(1, $historyScreenId, 1, 0, 0, 0);

        // Role 2 (ESS/Employee) Screen Mappings
        $this->authorizeScreen(2, $viewPayrollModuleScreenId, 1, 0, 0, 0);
        $this->authorizeScreen(2, $payslipsScreenId, 1, 1, 1, 1);

        // Role 3 Screen Mappings
        $this->authorizeScreen(3, $viewPayrollModuleScreenId, 1, 0, 0, 0);
        $this->authorizeScreen(3, $dashboardScreenId, 1, 1, 1, 1);
        $this->authorizeScreen(3, $managementScreenId, 1, 1, 1, 1);
        $this->authorizeScreen(3, $reportsScreenId, 1, 1, 1, 1);
        $this->authorizeScreen(3, $payslipsScreenId, 1, 1, 1, 1);

        // 3. Create Navigation Menu (Nested layout)
        $parentMenuId = $this->getOrCreateMenu('Payroll', $dashboardScreenId, null, 1, 1600, '{"icon":"briefcase"}');
        
        $this->getOrCreateMenu('Dashboard', $dashboardScreenId, $parentMenuId, 2, 100);
        $this->getOrCreateMenu('Payroll Management', $managementScreenId, $parentMenuId, 2, 200);
        $this->getOrCreateMenu('Payroll Settings', $settingsScreenId, $parentMenuId, 2, 300);
        $this->getOrCreateMenu('Reports', $reportsScreenId, $parentMenuId, 2, 400);
        $this->getOrCreateMenu('Payslips', $payslipsScreenId, $parentMenuId, 2, 500);
        $this->getOrCreateMenu('History', $historyScreenId, $parentMenuId, 2, 600);

        // 4. Create Default Pages
        $this->createDefaultPage($moduleId, 1, 'payroll/viewPayrollDashboard', 20);
        $this->createDefaultPage($moduleId, 3, 'payroll/viewPayrollDashboard', 10);
        $this->createDefaultPage($moduleId, 2, 'payroll/viewEmployeeSalarySlips', 0);

        // 5. Populate Default Setting Seeds
        try {
            $this->createSettingSeed('PF_PERCENTAGE', '20');
            $this->createSettingSeed('ESI_PERCENTAGE', '2');
            $this->createSettingSeed('PROFESSIONAL_TAX', '500');
            $this->createSettingSeed('TDS_PERCENTAGE', '20');
        } catch (\Exception $e) {
            // Ignore if settings table is not created yet
        }
    }

    private function createModule(): int
    {
        $existing = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module')
            ->where('name = :name')
            ->setParameter('name', 'payroll')
            ->executeQuery()
            ->fetchOne();

        if ($existing) {
            return (int)$existing;
        }

        $this->connection->insert(
            'ohrm_module',
            [
                'name' => 'payroll',
                'status' => 1,
                'display_name' => 'Payroll'
            ]
        );

        return (int)$this->connection->lastInsertId();
    }

    private function getOrCreateScreen(string $name, string $actionUrl, int $moduleId): int
    {
        $existing = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('action_url = :url')
            ->setParameter('url', $actionUrl)
            ->executeQuery()
            ->fetchOne();

        if ($existing) {
            return (int)$existing;
        }

        $this->connection->insert(
            'ohrm_screen',
            [
                'name' => $name,
                'action_url' => $actionUrl,
                'module_id' => $moduleId
            ]
        );

        return (int)$this->connection->lastInsertId();
    }

    private function getOrCreateMenu(
        string $title,
        int $screenId,
        ?int $parentId,
        int $level,
        int $orderHint,
        ?string $additionalParams = null
    ): int {
        $query = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title');
            
        if ($parentId !== null) {
            $query->andWhere('parent_id = :parentId')
                ->setParameter('parentId', $parentId);
        } else {
            $query->andWhere('parent_id IS NULL');
        }
        
        $existing = $query->setParameter('title', $title)
            ->executeQuery()
            ->fetchOne();

        if ($existing) {
            $this->connection->update(
                'ohrm_menu_item',
                ['additional_params' => $additionalParams],
                ['id' => (int)$existing]
            );
            return (int)$existing;
        }

        $this->connection->insert(
            'ohrm_menu_item',
            [
                'menu_title' => $title,
                'screen_id' => $screenId,
                'parent_id' => $parentId,
                'level' => $level,
                'order_hint' => $orderHint,
                'status' => 1,
                'additional_params' => $additionalParams
            ]
        );

        return (int)$this->connection->lastInsertId();
    }

    private function authorizeScreen(
        int $userRoleId,
        int $screenId,
        int $canRead = 1,
        int $canCreate = 1,
        int $canUpdate = 1,
        int $canDelete = 1
    ): void {
        $existing = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('ohrm_user_role_screen')
            ->where('user_role_id = :roleId')
            ->andWhere('screen_id = :screenId')
            ->setParameter('roleId', $userRoleId)
            ->setParameter('screenId', $screenId)
            ->executeQuery()
            ->fetchOne();

        if ($existing) {
            $this->connection->update(
                'ohrm_user_role_screen',
                [
                    'can_read' => $canRead,
                    'can_create' => $canCreate,
                    'can_update' => $canUpdate,
                    'can_delete' => $canDelete
                ],
                ['id' => $existing]
            );
            return;
        }

        $this->connection->insert(
            'ohrm_user_role_screen',
            [
                'user_role_id' => $userRoleId,
                'screen_id' => $screenId,
                'can_read' => $canRead,
                'can_create' => $canCreate,
                'can_update' => $canUpdate,
                'can_delete' => $canDelete
            ]
        );
    }

    private function createDefaultPage(int $moduleId, int $userRoleId, string $action, int $priority): void
    {
        $existing = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('ohrm_module_default_page')
            ->where('module_id = :moduleId')
            ->andWhere('user_role_id = :userRoleId')
            ->setParameter('moduleId', $moduleId)
            ->setParameter('userRoleId', $userRoleId)
            ->executeQuery()
            ->fetchOne();

        if ($existing) {
            $this->connection->update(
                'ohrm_module_default_page',
                [
                    'action' => $action,
                    'priority' => $priority
                ],
                ['id' => $existing]
            );
            return;
        }

        $this->connection->insert(
            'ohrm_module_default_page',
            [
                'module_id' => $moduleId,
                'user_role_id' => $userRoleId,
                'action' => $action,
                'priority' => $priority
            ]
        );
    }

    private function createSettingSeed(string $key, string $value): void
    {
        $existing = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('ohrm_payroll_settings')
            ->where('setting_key = :key')
            ->setParameter('key', $key)
            ->executeQuery()
            ->fetchOne();

        if ($existing) {
            return;
        }

        $this->connection->insert(
            'ohrm_payroll_settings',
            [
                'setting_key' => $key,
                'setting_value' => $value
            ]
        );
    }
}
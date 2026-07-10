<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Core\Vue\Prop;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Pim\Controller\BaseViewEmployeeController;

class EmployeeSalarySlipsController extends BaseViewEmployeeController
{
    public function preRender(Request $request): void
    {
        $empNumber = $request->attributes->get('empNumber');

        if ($empNumber) {
            $component = new Component('employee-salary-slips');

            $component->addProp(
                new Prop(
                    'emp-number',
                    Prop::TYPE_NUMBER,
                    $empNumber
                )
            );

            $this->setComponent($component);

            $this->setPermissionsForEmployee(
                ['salary_details'],
                $empNumber
            );
        } else {
            $this->handleBadRequest();
        }
    }

    protected function getDataGroupsForCapabilityCheck(): array
    {
        return ['salary_details'];
    }
}
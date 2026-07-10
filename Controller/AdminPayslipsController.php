<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Framework\Http\Request;

class AdminPayslipsController extends AbstractVueController
{
    public function preRender(Request $request): void
    {
        $this->setComponent(
            new Component('admin-payslips')
        );
    }
}
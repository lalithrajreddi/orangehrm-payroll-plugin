<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Pim\Service\EmployeeService;
use OrangeHRM\Pim\Service\EmployeeSalaryService;
use OrangeHRM\Framework\Http\BinaryFileResponse;
use OrangeHRM\Payroll\Service\PayrollService;

class DownloadSalarySlipController
{
    public function handle(Request $request)
    {
        $empNumber = (int)$request->attributes->get('empNumber');
        $month = (int)$request->attributes->get('month');
        $year = (int)$request->attributes->get('year');

        $employeeService = new EmployeeService();
        $employee = $employeeService->getEmployeeByEmpNumber($empNumber);

        $payrollService = new PayrollService();

        $payrollRun = $payrollService
            ->getPayrollRunByEmployeeAndPeriod(
                $empNumber,
                $month,
                $year
            );

        if (!$payrollRun) {
            die(
                'Salary slip not available for the selected month.'
            );
        }

        $items = $payrollService
            ->getPayrollItems(
                $payrollRun->getId()
            );


        // Fetch Company General Information
        $orgService = new \OrangeHRM\Admin\Service\OrganizationService();
        $orgInfo = $orgService->getOrganizationGeneralInformation();
        $companyName = $orgInfo ? $orgInfo->getName() : 'SRI TECHNOLOGY SOLUTIONS INDIA PRIVATE LIMITED (OPC)';
        $companyAddress = $orgInfo ? trim($orgInfo->getStreet1() . ' ' . $orgInfo->getStreet2() . ', ' . $orgInfo->getCity() . ' - ' . $orgInfo->getZipCode(), " ,\t\n\r\0\x0B") : 'D.No. 6-30, Gurukulam Street, Marikavalasa, Visakhapatnam - 530048';

        // Fetch Client Logo from Theme Service if available
        $companyLogo = null;
        if (class_exists('OrangeHRM\CorporateBranding\Service\ThemeService')) {
            $themeService = new \OrangeHRM\CorporateBranding\Service\ThemeService();
            $logoImage = $themeService->getImage('client_logo');
            if ($logoImage && !$logoImage->isEmpty()) {
                $logoContent = $logoImage->getContent();
                if ($logoContent) {
                    $tempLogoFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'client_logo_' . time() . '.png';
                    file_put_contents($tempLogoFile, $logoContent);
                    $companyLogo = $tempLogoFile;
                }
            }
        }

        // Fall back to default system logo on disk if no custom logo is uploaded
        if (!$companyLogo) {
            $defaultLogoPath = realpath(__DIR__ . '/../../../../web/images/logo.png');
            if (!$defaultLogoPath || !file_exists($defaultLogoPath)) {
                $defaultLogoPath = realpath(__DIR__ . '/../../../../web/images/orange.png');
            }
            if ($defaultLogoPath && file_exists($defaultLogoPath)) {
                $companyLogo = $defaultLogoPath;
            }
        }

        // Fetch Employee Custom Fields for PAN, PF No, PF UAN, Aadhaar No
        $pan = '';
        $pfNo = '';
        $pfUan = '';
        $aadhaar = '';
        try {
            $customFields = \OrangeHRM\ORM\Doctrine::getEntityManager()->getRepository(\OrangeHRM\Entity\CustomField::class)->findAll();
            foreach ($customFields as $customField) {
                $fieldName = strtolower(trim((string)$customField->getName()));
                $fieldNum = $customField->getFieldNum();
                $getter = 'getCustom' . $fieldNum;
                if (method_exists($employee, $getter)) {
                    $val = trim((string)($employee->$getter() ?? ''));
                    if ($fieldName === 'pan' || $fieldName === 'pan no' || $fieldName === 'pan_no' || $fieldName === 'pan number') {
                        $pan = $val;
                    } elseif ($fieldName === 'pf no' || $fieldName === 'pf number' || $fieldName === 'pf_no' || $fieldName === 'pf') {
                        $pfNo = $val;
                    } elseif ($fieldName === 'pf uan' || $fieldName === 'pf_uan' || $fieldName === 'uan') {
                        $pfUan = $val;
                    } elseif ($fieldName === 'aadhaar no' || $fieldName === 'aadhaar' || $fieldName === 'aadhar no' || $fieldName === 'aadhar') {
                        $aadhaar = $val;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore custom field errors if database not updated
        }

        $data = [
            'company' => [
                'name' => $companyName,
                'address' => $companyAddress,
                'logo' => $companyLogo,
            ],
            'employee' => [
                'empNumber' => $employee->getEmpNumber(),
                'employeeId' => $employee->getEmployeeId(),
                'name' => trim(
                    $employee->getFirstName() . ' ' .
                    $employee->getLastName()
                ),
                'designation' => $employee->getJobTitle()
                    ? $employee->getJobTitle()->getJobTitleName()
                    : '',
                'department' => $employee->getJobCategory()
                    ? $employee->getJobCategory()->getName()
                    : '',
                'joiningDate' => $employee->getDecorator()->getJoinedDate(),
                'pan' => $pan,
                'pfNo' => $pfNo,
                'pfUan' => $pfUan,
                'aadhaar' => $aadhaar,
            ],
            'payPeriod' => [
                'month' => $month,
                'year' => $year,
            ],
            'earnings' => [],
            'deductions' => [],
        ];

        foreach ($items as $item) {

            $row = [
                'name' => $item->getComponentName(),
                'amount' => $item->getAmount(),
            ];

            switch ($item->getComponentType()) {

                case 'BASE':
                case 'ALLOWANCE':
                case 'BONUS':
                    $data['earnings'][] = $row;
                    break;

                case 'DEDUCTION':
                case 'TAX':
                    $data['deductions'][] = $row;
                    break;
            }
        }
        
        $data['summary'] = [
            'grossSalary' => $payrollRun->getGrossSalary(),
            'totalAllowances' => $payrollRun->getTotalAllowances(),
            'totalBonuses' => $payrollRun->getTotalBonuses(),
            'totalDeductions' => $payrollRun->getTotalDeductions(),
            'totalTaxes' => $payrollRun->getTotalTaxes(),
            'netSalary' => $payrollRun->getNetSalary(),
        ];

        $jsonFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "payslip_{$empNumber}.json";
        $pdfFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "payslip_{$empNumber}.pdf";

        file_put_contents(
            $jsonFile,
            json_encode($data, JSON_PRETTY_PRINT)
        );
        $script = realpath(__DIR__ . '/../python/generate_payslip.py');

        $command =
            'python3 '
            . escapeshellarg($script)
            . ' '
            . escapeshellarg($jsonFile)
            . ' '
            . escapeshellarg($pdfFile);

        exec($command, $output, $returnCode);

        $file = trim(end($output));

        $response = new BinaryFileResponse($file);

        $response->headers->set(
            'Content-Type',
            'application/pdf'
        );

        $monthName = date("F", mktime(0, 0, 0, $month, 10));
        $empId = $employee->getEmployeeId();
        $safeEmpId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empId);
        $fileName = "Payslip_{$monthName}_{$year}_{$safeEmpId}.pdf";

        $response->setContentDisposition(
            'attachment',
            $fileName
        );

        $data['summary'] = [
            'grossSalary' => $payrollRun->getGrossSalary(),
            'totalAllowances' => $payrollRun->getTotalAllowances(),
            'totalBonuses' => $payrollRun->getTotalBonuses(),
            'totalDeductions' => $payrollRun->getTotalDeductions(),
            'totalTaxes' => $payrollRun->getTotalTaxes(),
            'netSalary' => $payrollRun->getNetSalary(),
        ];

        return $response;
    }
}
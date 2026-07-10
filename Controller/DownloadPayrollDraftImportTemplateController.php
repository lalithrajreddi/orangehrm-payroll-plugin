<?php

namespace OrangeHRM\Payroll\Controller;

class DownloadPayrollDraftImportTemplateController
{
    public function handle(): void
    {
        header('Content-Type: text/csv');
        header(
            'Content-Disposition: attachment; filename=payroll-draft-import-template.csv'
        );

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'employee_id',
            'month',
            'year',
            'component_name',
            'component_type',
            'amount',
        ]);
        fputcsv($output, [
            '0001',
            date('n'),
            date('Y'),
            'Performance Bonus',
            'BONUS',
            '5000.00',
        ]);

        fclose($output);
    }
}

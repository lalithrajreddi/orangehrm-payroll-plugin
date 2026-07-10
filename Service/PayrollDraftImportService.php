<?php

namespace OrangeHRM\Payroll\Service;

use DOMDocument;
use DOMElement;
use DOMXPath;
use OrangeHRM\Entity\PayrollDraftItem;
use OrangeHRM\Pim\Dto\EmployeeSearchFilterParams;
use OrangeHRM\Pim\Service\EmployeeService;
use RuntimeException;
use ZipArchive;

class PayrollDraftImportService
{
    private const REQUIRED_COLUMNS = [
        'employee_id',
        'month',
        'year',
        'component_name',
        'component_type',
        'amount',
    ];

    private const COMPONENT_TYPES = [
        'ALLOWANCE',
        'BONUS',
        'DEDUCTION',
        'TAX',
    ];

    public function import(array $attachment): array
    {
        $filename = (string)($attachment['filename'] ?? '');
        $content = base64_decode(
            (string)($attachment['base64'] ?? ''),
            true
        );

        if ($filename === '' || $content === false) {
            throw new RuntimeException('Invalid spreadsheet attachment');
        }

        if (strlen($content) > 5 * 1024 * 1024) {
            throw new RuntimeException('The spreadsheet must be 5 MB or smaller');
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('Only CSV and XLSX files are supported');
        }

        $rows = $extension === 'xlsx'
            ? $this->readXlsx($content)
            : $this->readCsv($content);

        return $this->importRows($rows);
    }

    private function importRows(array $rows): array
    {
        if (count($rows) < 2) {
            throw new RuntimeException('The spreadsheet does not contain data rows');
        }

        $headers = array_map(
            fn ($header): string => $this->normalizeHeader((string)$header),
            array_shift($rows)
        );
        $missingColumns = array_diff(self::REQUIRED_COLUMNS, $headers);

        if ($missingColumns !== []) {
            throw new RuntimeException(
                'Missing columns: ' . implode(', ', $missingColumns)
            );
        }

        $employees = $this->getEmployeeMap();
        $draftService = new PayrollDraftService();
        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = array_combine(
                $headers,
                array_pad($row, count($headers), '')
            );

            try {
                $employeeId = trim((string)$data['employee_id']);
                $month = (int)$data['month'];
                $year = (int)$data['year'];
                $componentName = trim((string)$data['component_name']);
                $componentType = strtoupper(
                    trim((string)$data['component_type'])
                );
                $amount = filter_var(
                    $data['amount'],
                    FILTER_VALIDATE_FLOAT
                );

                if (!isset($employees[$employeeId])) {
                    throw new RuntimeException("Employee ID '$employeeId' was not found");
                }

                if ($month < 1 || $month > 12) {
                    throw new RuntimeException('Month must be between 1 and 12');
                }

                if ($year < 2000 || $year > 2100) {
                    throw new RuntimeException('Year must be between 2000 and 2100');
                }

                if ($componentName === '') {
                    throw new RuntimeException('Component name is required');
                }

                if (!in_array($componentType, self::COMPONENT_TYPES, true)) {
                    throw new RuntimeException(
                        'Component type must be ALLOWANCE, BONUS, DEDUCTION, or TAX'
                    );
                }

                if ($amount === false || $amount <= 0) {
                    throw new RuntimeException('Amount must be greater than zero');
                }

                $draft = $draftService->getPayrollDraftByEmployeeAndPeriod(
                    $employees[$employeeId],
                    $month,
                    $year
                );

                if (!$draft) {
                    throw new RuntimeException('No payroll draft exists for this period');
                }

                if ($draft->getStatus() === 'GENERATED') {
                    throw new RuntimeException('Payroll has already been generated');
                }

                $item = new PayrollDraftItem();
                $item->setDraftId($draft->getId());
                $item->setComponentName($componentName);
                $item->setComponentType($componentType);
                $item->setAmount(number_format((float)$amount, 2, '.', ''));
                $item->setIsSystemGenerated(false);
                $draftService->savePayrollDraftItem($item);

                $draft->setStatus('EDITED');
                $draftService->savePayrollDraft($draft);
                $success++;
            } catch (RuntimeException $exception) {
                $failed++;
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'total' => $success + $failed,
            'errors' => $errors,
        ];
    }

    private function getEmployeeMap(): array
    {
        $employeeService = new EmployeeService();
        $employees = $employeeService->getEmployeeList(
            new EmployeeSearchFilterParams()
        );
        $map = [];

        foreach ($employees as $employee) {
            $map[$employee->getEmployeeId()] = $employee->getEmpNumber();
        }

        return $map;
    }

    private function readCsv(string $content): array
    {
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Unable to read the CSV file');
        }

        fwrite($stream, $content);
        rewind($stream);
        $rows = [];

        while (($row = fgetcsv($stream)) !== false) {
            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }

    private function readXlsx(string $content): array
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'payroll-import-');

        if ($temporaryFile === false) {
            throw new RuntimeException('Unable to process the XLSX file');
        }

        file_put_contents($temporaryFile, $content);
        $archive = new ZipArchive();
        $opened = false;

        try {
            if ($archive->open($temporaryFile) !== true) {
                throw new RuntimeException('The XLSX file is invalid');
            }
            $opened = true;

            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');

            if ($worksheet === false) {
                throw new RuntimeException('The XLSX file has no first worksheet');
            }

            $sharedStrings = $this->readSharedStrings($archive);

            return $this->readWorksheet($worksheet, $sharedStrings);
        } finally {
            if ($opened) {
                $archive->close();
            }
            unlink($temporaryFile);
        }
    }

    private function readSharedStrings(ZipArchive $archive): array
    {
        $xml = $archive->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $document = new DOMDocument();
        $document->loadXML($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace(
            'x',
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
        );
        $strings = [];

        foreach ($xpath->query('//x:si') as $item) {
            $value = '';

            foreach ($xpath->query('.//x:t', $item) as $text) {
                $value .= $text->textContent;
            }

            $strings[] = $value;
        }

        return $strings;
    }

    private function readWorksheet(
        string $xml,
        array $sharedStrings
    ): array {
        $document = new DOMDocument();
        $document->loadXML($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace(
            'x',
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
        );
        $rows = [];

        foreach ($xpath->query('//x:sheetData/x:row') as $rowNode) {
            $row = [];

            foreach ($xpath->query('./x:c', $rowNode) as $cell) {
                if (!$cell instanceof DOMElement) {
                    continue;
                }

                $reference = $cell->getAttribute('r');
                $columnIndex = $this->columnIndex($reference);
                $type = $cell->getAttribute('t');
                $valueNode = $xpath->query('./x:v', $cell)->item(0);
                $value = $valueNode?->textContent ?? '';

                if ($type === 's') {
                    $value = $sharedStrings[(int)$value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $textNode = $xpath->query('./x:is/x:t', $cell)->item(0);
                    $value = $textNode?->textContent ?? '';
                }

                $row[$columnIndex] = $value;
            }

            if ($row !== []) {
                ksort($row);
                $lastColumn = max(array_keys($row));
                $rows[] = array_replace(
                    array_fill(0, $lastColumn + 1, ''),
                    $row
                );
            }
        }

        return $rows;
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = strtolower(trim((string)$header));

        return preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';
    }

    private function isEmptyRow(array $row): bool
    {
        return count(array_filter(
            $row,
            static fn ($value): bool => trim((string)$value) !== ''
        )) === 0;
    }
}

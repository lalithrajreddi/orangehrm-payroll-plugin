# OrangeHRM Payroll Plugin Deployment Guide

Instructions for deploying, installing, and validating the custom Payroll Plugin on a clean or existing OrangeHRM installation.

---

## Key Features

The custom OrangeHRM Payroll Plugin offers a fully integrated suite of tools to manage company payrolls, configure allowances, and issue high-fidelity salary slips to employees:

- **Interactive Payroll Dashboard**:
  - High-performance dashboard utilizing Chart.js to visualize payroll expenses and trends over time.
  - Quick summaries showing Gross Salary, Allowances, Deductions, and Net Salary metrics.
- **Bulk Payroll Management**:
  - Manage draft calculations, add draft items, and edit or delete draft items.
  - CSV Import/Export capability: import bulk payroll runs using a clean template, and export drafts to CSV.
  - Generate payroll runs for selected months and years.
- **Flexible Calculation Settings**:
  - Customizable percentage values for standard Indian statutory deductions (EPF, ESI, TDS, Professional Tax, etc.).
  - Automatic Net Pay calculation based on base pay, allowances, and statutory deductions.
- **Personalized Salary Slips (ESS & Admin)**:
  - Role-specific access allows employees (ESS) to view and download their historical payslips from their personal PIM profiles.
  - Admins can query and generate slips for any employee and run historical reports.
- **High-Fidelity PDF Generation**:
  - ReportLab-based vector layout drawing clean headers, transactions table, net pay card, and legal disclaimers.
  - Dynamic branding: automatically retrieves the company name and branding logo from the database (falls back to default system branding if not uploaded).
  - Indian Currency Words Converter: translates Net Pay numbers into text format (e.g. *"Rupees Fourteen Thousand and Fifty Paise Only"*).
  - PIM Custom Fields Mapping: automatically extracts and displays employee PAN No, PF No, PF UAN, and Aadhaar No from standard PIM custom fields.
  - Smart File Naming: downloaded PDF files are automatically named like `Payslip_August_2025_EDGE0140.pdf`.
- **Self-Healing Installation**:
  - Bootstrapper automatically creates tables, registers core side-navigation menus, grants user role permissions (Admin, ESS, Manager), and seeds default configuration constants on first system visit.

---

## I. Deployment Architecture

The Payroll Plugin integrates both server-side PHP components (controllers, database entities, and calculations) and client-side Vue components (charts and interfaces). To make installation completely plug-and-play, a custom Node helper script automates file copies and dependency registration, while a self-healing backend bootstrapper generates the database tables dynamically on first request.

---

## II. Installation Workflow

Follow these steps on the host machine to deploy the plugin:

### 1. Drop in the Plugin Files
Copy the entire `orangehrmPayrollPlugin` directory into the main application plugin directory:
```bash
# Destination folder
src/plugins/orangehrmPayrollPlugin
```

### 2. Run the Automated Installer
Execute the installer helper script from the plugin folder. This script automatically handles routing registrations inside `pages.ts`, installs `chart.js` inside `package.json`, maps autoloader namespaces in `composer.json`, and automatically patches core PIM services (injecting the profile "Salary Slips" submenu inside `PIMLeftMenuService.php` and the `getEmployeeSalaryList` method in `EmployeeSalaryService.php`):
```bash
node src/plugins/orangehrmPayrollPlugin/install.js
```

### 3. Update Composer Autoloader
Re-generate the Composer class map on the host to register the new namespaces:
```bash
cd src
composer install
```

### 4. Compile Client Vue Assets
Navigate to the client folder, download dependencies (including the newly added Chart.js library), and compile the Vue static assets:
```bash
cd client
yarn install
yarn build
```

### 5. Reload the Container
Rebuild and restart the container stack to flush the PHP OPcache and load the updated autoloader:
```bash
cd ../..
docker compose -f docker-compose.fast.yml build orangehrm
docker compose -f docker-compose.fast.yml up -d orangehrm
```

---

## III. Activation & Verification

Once the containers are rebooted, the installation is verified as follows:

### 1. Automatic Database Generation
Open your browser and navigate to **`http://localhost:8080`**. Log in with your admin user. On this first page hit, the plugin bootstrapper detects that the payroll tables are missing and invokes Doctrine's `SchemaTool` to generate them automatically:
- `ohrm_payroll_run`
- `ohrm_payroll_draft`
- `ohrm_payroll_draft_item`
- `ohrm_payroll_item`
- `ohrm_payroll_settings`

### 2. Check Sidebar Menu & Layout
The **Payroll** tab will appear in the main navigation sidebar. Clicking on it opens the interactive payroll sheets, charts, salary history, and default configuration items automatically.

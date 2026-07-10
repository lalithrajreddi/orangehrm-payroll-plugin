# OrangeHRM Payroll Plugin Deployment Guide

Instructions for deploying, installing, and validating the custom Payroll Plugin on a clean or existing OrangeHRM installation.

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

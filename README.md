# OrangeHRM Payroll Plugin Installation Guide

This guide details how to install and configure the payroll plugin on a standard OrangeHRM installation.

---

## 1. Directory Structure Setup

The plugin consists of two separate components: a PHP/Symfony backend plugin and a Vue.js frontend module. Copy the folders to the locations below:

1. **Backend Plugin**: Copy the `orangehrmPayrollPlugin` folder to `src/plugins/`
   - Target path: `src/plugins/orangehrmPayrollPlugin`
2. **Frontend Plugin**: Copy the `orangehrmPayrollPlugin` folder to `src/client/src/`
   - Target path: `src/client/src/orangehrmPayrollPlugin`

---

## 2. Register Backend Classes & Entities

OrangeHRM uses Composer for autoloading PHP namespaces and Doctrine for database mapping.

1. Open `src/composer.json`.
2. Locate the `"autoload"` block, and add the payroll namespace mapping under `"psr-4"`:
   ```json
   "OrangeHRM\\Payroll\\": "plugins/orangehrmPayrollPlugin"
   ```
3. Locate the `"OrangeHRM\\Entity\\"` entry (which lists entity mapping paths) and add your entity folder to the array:
   ```json
   "./plugins/orangehrmPayrollPlugin/entity"
   ```
4. Regenerate the Composer autoloader classes and clean the caching proxies by running the following command from the `src/` directory:
   ```bash
   composer dump-autoload
   ```

---

## 3. Register Frontend Routes

We need to register the payroll Vue pages and routes with the core Vue bundle.

1. Open `src/client/src/pages.ts`.
2. Import the payroll pages at the top of the file:
   ```typescript
   import PayrollPages from '@/orangehrmPayrollPlugin';
   ```
3. Register the pages by spreading them inside the `export default` block:
   ```typescript
   export default {
     ...PayrollPages,
     // ... other plugins
   };
   ```
4. Build the compiled production assets. Change your terminal path to `src/client` and run the build:
   ```bash
   yarn build
   # or: npm run build
   ```

---

## 4. Database Setup & Initialization

The database integration consists of creating the tables and loading configuration menus/roles.

1. **Create Tables**: Sync your Doctrine entities to automatically generate the database schema tables. Run this from the root directory:
   ```bash
   php bin/console doctrine:schema:update --force
   ```

2. **Automated Metadata Setup**:
   The plugin comes with a configuration bootstrap (`PayrollPluginConfiguration.php`). As soon as you open OrangeHRM or load the dashboard for the first time, it will automatically register:
   - All 7 payroll screens.
   - User role permissions (read/write access for admin, ESS/employees, and managers).
   - Sidebar menus and navigation tabs.
   - Role-specific default landing pages (e.g. Admin landing on the Dashboard, employees landing on Salary Slips).
   - Initial calculation settings seeds (e.g., ESI, PF, TDS, Professional Tax percentages).

---

## 5. System Dependencies

The payslip PDF rendering relies on a python utility. Ensure the host server has:
1. `python3` installed.
2. The `reportlab` library installed for PDF canvas drawing:
   ```bash
   pip install reportlab
   ```

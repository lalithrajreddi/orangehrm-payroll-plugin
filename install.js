const fs = require('fs');
const path = require('path');

console.log("=========================================");
console.log("OrangeHRM Payroll Plugin Local Installer");
console.log("=========================================");

const rootDir = path.resolve(__dirname, '../../..');

// Paths
const pluginClientDir = path.join(__dirname, 'client');
const targetClientDir = path.join(rootDir, 'src/client/src/orangehrmPayrollPlugin');
const pagesTsPath = path.join(rootDir, 'src/client/src/pages.ts');

if (!fs.existsSync(pluginClientDir)) {
  console.error(`Error: Cannot find plugin frontend assets folder at: ${pluginClientDir}`);
  process.exit(1);
}

// 1. Copy frontend files
console.log("Copying frontend assets...");
function copyFolderSync(from, to) {
  if (!fs.existsSync(to)) {
    fs.mkdirSync(to, { recursive: true });
  }
  fs.readdirSync(from).forEach(element => {
    const fromPath = path.join(from, element);
    const toPath = path.join(to, element);
    if (fs.lstatSync(fromPath).isDirectory()) {
      copyFolderSync(fromPath, toPath);
    } else {
      fs.copyFileSync(fromPath, toPath);
    }
  });
}

try {
  copyFolderSync(pluginClientDir, targetClientDir);
  console.log(`Frontend assets copied to: ${targetClientDir}`);
} catch (err) {
  console.error("Error copying frontend files:", err);
  process.exit(1);
}

// 2. Modify pages.ts
if (fs.existsSync(pagesTsPath)) {
  console.log("Registering plugin routes in pages.ts...");
  let content = fs.readFileSync(pagesTsPath, 'utf8');

  if (!content.includes('@/orangehrmPayrollPlugin')) {
    const importStatement = "import PayrollPages from '@/orangehrmPayrollPlugin';\n";
    const exportIndex = content.indexOf('export default {');

    if (exportIndex !== -1) {
      content = content.slice(0, exportIndex) + importStatement + content.slice(exportIndex);
      
      const insertIndex = content.indexOf('export default {') + 'export default {'.length;
      content = content.slice(0, insertIndex) + '\n  ...PayrollPages,' + content.slice(insertIndex);

      fs.writeFileSync(pagesTsPath, content, 'utf8');
      console.log("Routes successfully registered in pages.ts!");
    } else {
      console.error("Error: Could not find 'export default {' inside pages.ts");
    }
  } else {
    console.log("Plugin routes already registered in pages.ts.");
  }
} else {
  console.error(`Error: pages.ts not found at: ${pagesTsPath}`);
}

// 3. Modify package.json to add chart.js dependency
const packageJsonPath = path.join(rootDir, 'src/client/package.json');
if (fs.existsSync(packageJsonPath)) {
  console.log("Checking chart.js dependency in package.json...");
  try {
    const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    pkg.dependencies = pkg.dependencies || {};
    if (!pkg.dependencies['chart.js']) {
      pkg.dependencies['chart.js'] = '^4.5.1';
      fs.writeFileSync(packageJsonPath, JSON.stringify(pkg, null, 2) + '\n', 'utf8');
      console.log("chart.js successfully added to dependencies in package.json!");
    } else {
      console.log("chart.js is already registered in package.json.");
    }
  } catch (err) {
    console.error("Error reading/updating package.json:", err);
  }
} else {
  console.error(`Error: package.json not found at: ${packageJsonPath}`);
}

// 4. Modify composer.json to add PSR-4 namespaces
const composerJsonPath = path.join(rootDir, 'src/composer.json');
if (fs.existsSync(composerJsonPath)) {
  console.log("Registering PHP namespace mappings in composer.json...");
  try {
    const composer = JSON.parse(fs.readFileSync(composerJsonPath, 'utf8'));
    composer.autoload = composer.autoload || {};
    composer.autoload["psr-4"] = composer.autoload["psr-4"] || {};
    
    let modified = false;

    if (!composer.autoload["psr-4"]["OrangeHRM\\Payroll\\"]) {
      composer.autoload["psr-4"]["OrangeHRM\\Payroll\\"] = "plugins/orangehrmPayrollPlugin";
      modified = true;
    }

    const entities = composer.autoload["psr-4"]["OrangeHRM\\Entity\\"];
    if (Array.isArray(entities)) {
      if (!entities.includes("./plugins/orangehrmPayrollPlugin/entity")) {
        entities.push("./plugins/orangehrmPayrollPlugin/entity");
        modified = true;
      }
    }

    if (modified) {
      fs.writeFileSync(composerJsonPath, JSON.stringify(composer, null, 4) + '\n', 'utf8');
      console.log("Namespace mappings successfully added to composer.json!");
    } else {
      console.log("Namespace mappings already registered in composer.json.");
    }
  } catch (err) {
    console.error("Error reading/updating composer.json:", err);
  }
} else {
  console.error(`Error: composer.json not found at: ${composerJsonPath}`);
}

// 5. Modify PIMLeftMenuService.php to register 'viewSalarySlips' submenu
const pimLeftMenuServicePath = path.join(rootDir, 'src/plugins/orangehrmPimPlugin/Service/PIMLeftMenuService.php');
if (fs.existsSync(pimLeftMenuServicePath)) {
  console.log("Checking PIM Left Menu registrations...");
  let content = fs.readFileSync(pimLeftMenuServicePath, 'utf8');
  if (!content.includes('viewSalarySlips')) {
    const targetString = `        'viewSalaryList' => [
            'module' => 'pim',
            'data_groups' => ['salary_details', 'salary_attachment', 'salary_custom_fields'],
            'label' => 'Salary'
        ],`;
    
    const insertion = `\n        'viewSalarySlips' => [
            'module' => 'payroll',
            'data_groups' => ['salary_details'],
            'label' => 'Salary Slips'
        ],`;
        
    if (content.includes(targetString)) {
      content = content.replace(targetString, targetString + insertion);
      fs.writeFileSync(pimLeftMenuServicePath, content, 'utf8');
      console.log("Salary Slips successfully added to PIM Left Menu!");
    } else {
      console.error("Error: Could not locate 'viewSalaryList' inside PIMLeftMenuService.php");
    }
  } else {
    console.log("Salary Slips already registered in PIM Left Menu.");
  }
} else {
  console.error(`Error: PIMLeftMenuService.php not found at: ${pimLeftMenuServicePath}`);
}

// 6. Modify EmployeeSalaryService.php to add getEmployeeSalaryList method
const employeeSalaryServicePath = path.join(rootDir, 'src/plugins/orangehrmPimPlugin/Service/EmployeeSalaryService.php');
if (fs.existsSync(employeeSalaryServicePath)) {
  console.log("Checking EmployeeSalaryService helper methods...");
  let content = fs.readFileSync(employeeSalaryServicePath, 'utf8');
  let modified = false;

  // Add the use statement if missing
  if (!content.includes('use OrangeHRM\\Admin\\Dto\\EmployeeSalarySearchFilterParams;')) {
    const namespaceLine = 'namespace OrangeHRM\\Pim\\Service;\n';
    const importStatement = '\nuse OrangeHRM\\Admin\\Dto\\EmployeeSalarySearchFilterParams;';
    if (content.includes(namespaceLine)) {
      content = content.replace(namespaceLine, namespaceLine + importStatement);
      modified = true;
    }
  }

  // Add the getEmployeeSalaryList method if missing
  if (!content.includes('public function getEmployeeSalaryList')) {
    const lastBraceIndex = content.lastIndexOf('}');
    if (lastBraceIndex !== -1) {
      const methodCode = `\n    public function getEmployeeSalaryList(int $empNumber): array\n    {\n        $params = new EmployeeSalarySearchFilterParams();\n        $params->setEmpNumber($empNumber);\n\n        return $this->getEmployeeSalaryDao()\n            ->getEmployeeSalaries($params);\n    }\n`;
      content = content.slice(0, lastBraceIndex) + methodCode + content.slice(lastBraceIndex);
      modified = true;
    }
  }

  if (modified) {
    fs.writeFileSync(employeeSalaryServicePath, content, 'utf8');
    console.log("EmployeeSalaryService successfully patched!");
  } else {
    console.log("EmployeeSalaryService is already up to date.");
  }
} else {
  console.error(`Error: EmployeeSalaryService.php not found at: ${employeeSalaryServicePath}`);
}

console.log("\nInstallation finished successfully!");
console.log("Please run 'composer install' to update the autoloader mappings.");
console.log("You can now build the frontend using: yarn build");

<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          Payroll History
        </oxd-text>
      </div>

      <oxd-form>
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="searchText"
                label="Employee"
                placeholder="Search employee"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="selectedMonth"
                type="select"
                label="Month"
                :options="monthOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="selectedYear"
                type="select"
                label="Year"
                :options="yearOptions"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-actions>
          <oxd-button
            display-type="ghost"
            label="Reset"
            @click="resetFilters"
          />
        </oxd-form-actions>
      </oxd-form>

      <oxd-divider />

      <div class="orangehrm-container payroll-table-wrap">
        <table class="payroll-table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Period</th>
              <th>Gross</th>
              <th>Net</th>
              <th>Status</th>
              <th>Payslip</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="run in filteredRuns" :key="run.id">
              <td>{{ run.employeeName }}</td>
              <td>{{ getMonthName(run.month) }} {{ run.year }}</td>
              <td>{{ formatCurrency(run.grossSalary) }}</td>
              <td>{{ formatCurrency(run.netSalary) }}</td>
              <td>{{ run.status }}</td>
              <td>
                <oxd-icon-button
                  name="download"
                  title="Download Payslip"
                  @click="downloadPayslip(run)"
                />
              </td>
            </tr>
            <tr v-if="filteredRuns.length === 0">
              <td colspan="6" class="empty-state">No payroll records found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PayrollHistory',

  data() {
    return {
      payrollRuns: [],
      searchText: '',
      monthOptions: [
        {id: '', label: 'All Months'},
        ...[
          'January',
          'February',
          'March',
          'April',
          'May',
          'June',
          'July',
          'August',
          'September',
          'October',
          'November',
          'December',
        ].map((label, index) => ({id: index + 1, label})),
      ],
      selectedMonth: {id: '', label: 'All Months'},
      selectedYear: {id: '', label: 'All Years'},
    };
  },

  computed: {
    yearOptions() {
      return [
        {id: '', label: 'All Years'},
        ...[...new Set(this.payrollRuns.map((run) => run.year))]
          .sort((a, b) => b - a)
          .map((year) => ({id: year, label: String(year)})),
      ];
    },

    filteredRuns() {
      return this.payrollRuns.filter((run) => {
        const employeeMatch =
          !this.searchText ||
          run.employeeName
            .toLowerCase()
            .includes(this.searchText.toLowerCase());
        const monthMatch =
          !this.selectedMonth?.id || run.month == this.selectedMonth.id;
        const yearMatch =
          !this.selectedYear?.id || run.year == this.selectedYear.id;

        return employeeMatch && monthMatch && yearMatch;
      });
    },
  },

  async mounted() {
    const response = await axios.get('/web/index.php/payroll/runs');
    this.payrollRuns = response.data;
  },

  methods: {
    getMonthName(month) {
      return this.monthOptions[Number(month)]?.label || month;
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
      }).format(Number(value) || 0);
    },

    downloadPayslip(run) {
      window.open(
        `/web/index.php/payroll/downloadSalarySlip/empNumber/${run.empNumber}/${run.month}/${run.year}`,
        '_blank',
      );
    },

    resetFilters() {
      this.searchText = '';
      this.selectedMonth = this.monthOptions[0];
      this.selectedYear = this.yearOptions[0];
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-paper-container {
  padding: 24px;
}

.orangehrm-header-container {
  padding: 0 0 20px 0;
}

.payroll-table-wrap {
  overflow-x: auto;
}

.payroll-table {
  width: 100%;
  border-spacing: 0 0.5rem;
  border-collapse: separate;

  th {
    color: $oxd-interface-gray-color;
    font-size: 0.75rem;
    padding: 0.75rem 1rem;
    text-align: left;
  }

  td {
    padding: 0.9rem 1rem;
    background: $oxd-background-pastel-white-color;
    border-top: 1px solid $oxd-interface-gray-lighten-1-color;
    border-bottom: 1px solid $oxd-interface-gray-lighten-1-color;
  }
}

.empty-state {
  color: $oxd-interface-gray-color;
  text-align: center;
}
</style>

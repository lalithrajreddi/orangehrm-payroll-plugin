<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <div>
          <oxd-text tag="h6" class="orangehrm-main-title">
            Payroll Reports
          </oxd-text>
          <oxd-text tag="p" class="orangehrm-input-hint">
            Filter payroll data by month and year before downloading it.
          </oxd-text>
        </div>

        <oxd-button
          display-type="secondary"
          label="Download Filtered CSV"
          icon-name="download"
          :disabled="reports.length === 0"
          @click="exportCsv"
        />
      </div>

      <oxd-form @submit-valid="loadReports">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
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
            label="Clear"
            @click="clearFilters"
          />
          <oxd-button
            type="submit"
            display-type="secondary"
            label="Apply Filters"
          />
        </oxd-form-actions>
      </oxd-form>

      <oxd-divider />

      <div class="summary-grid">
        <div>
          <span>Payroll Records</span>
          <strong>{{ reports.length }}</strong>
        </div>
        <div>
          <span>Total Gross</span>
          <strong>{{ formatCurrency(totals.gross) }}</strong>
        </div>
        <div>
          <span>Total Deductions</span>
          <strong>{{ formatCurrency(totals.deductions) }}</strong>
        </div>
        <div>
          <span>Total Net</span>
          <strong>{{ formatCurrency(totals.net) }}</strong>
        </div>
      </div>

      <div class="table-container">
        <table v-if="reports.length > 0">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Period</th>
              <th>Gross</th>
              <th>Deductions</th>
              <th>Taxes</th>
              <th>Net</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in reports"
              :key="row.employeeName + row.month + row.year"
            >
              <td>{{ row.employeeName }}</td>
              <td>{{ getMonthName(row.month) }} {{ row.year }}</td>
              <td>{{ formatCurrency(row.grossSalary) }}</td>
              <td>{{ formatCurrency(row.deductions) }}</td>
              <td>{{ formatCurrency(row.taxes) }}</td>
              <td>{{ formatCurrency(row.netSalary) }}</td>
            </tr>
          </tbody>
        </table>

        <div v-else class="empty-state">
          No payroll data was found for the selected period.
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PayrollReports',

  data() {
    const currentYear = new Date().getFullYear();

    return {
      reports: [],
      monthOptions: [
        {id: '', label: 'All Months'},
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
      ].map((value, index) =>
        typeof value === 'string' ? {id: index, label: value} : value,
      ),
      selectedMonth: {id: '', label: 'All Months'},
      yearOptions: [
        {id: '', label: 'All Years'},
        ...Array.from({length: 7}, (_, index) => currentYear - 5 + index)
          .reverse()
          .map((year) => ({id: year, label: String(year)})),
      ],
      selectedYear: {id: currentYear, label: String(currentYear)},
    };
  },

  computed: {
    month() {
      return this.selectedMonth?.id;
    },
    year() {
      return this.selectedYear?.id;
    },
    totals() {
      return this.reports.reduce(
        (totals, report) => ({
          gross: totals.gross + Number(report.grossSalary),
          deductions: totals.deductions + Number(report.deductions),
          net: totals.net + Number(report.netSalary),
        }),
        {gross: 0, deductions: 0, net: 0},
      );
    },
  },

  async mounted() {
    await this.loadReports();
  },

  methods: {
    getParams() {
      return {
        month: this.month || undefined,
        year: this.year || undefined,
      };
    },

    async loadReports() {
      const response = await axios.get('/web/index.php/payroll/report', {
        params: this.getParams(),
      });

      this.reports = response.data;
    },

    async clearFilters() {
      this.selectedMonth = this.monthOptions[0];
      this.selectedYear = this.yearOptions[0];
      await this.loadReports();
    },

    exportCsv() {
      const query = new URLSearchParams();

      if (this.month) {
        query.set('month', this.month);
      }

      if (this.year) {
        query.set('year', this.year);
      }

      const queryString = query.toString();
      window.location.href =
        '/web/index.php/payroll/report/export/csv' +
        (queryString ? `?${queryString}` : '');
    },

    getMonthName(month) {
      return this.monthOptions[Number(month)]?.label || month;
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
      }).format(Number(value) || 0);
    },
  },
};
</script>

<style scoped>
.orangehrm-paper-container {
  padding: 24px;
}

.orangehrm-header-container {
  padding: 0 0 20px 0;
}

.report-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.report-header p {
  color: #64728c;
  margin-top: 4px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
  margin-bottom: 20px;
}

.summary-grid div {
  background: #fff;
  border: 1px solid #e8eaef;
  border-radius: 10px;
  padding: 16px;
}

.summary-grid span {
  color: #64728c;
  display: block;
  font-size: 13px;
  margin-bottom: 8px;
}

.summary-grid strong {
  font-size: 20px;
}

.table-container {
  overflow-x: auto;
}

table {
  border-collapse: collapse;
  width: 100%;
}

th,
td {
  border-bottom: 1px solid #e8eaef;
  padding: 12px;
  text-align: left;
}

th {
  background: #f7f8fa;
}

.empty-state {
  background: #f7f8fa;
  border-radius: 10px;
  color: #64728c;
  padding: 32px;
  text-align: center;
}

@media (max-width: 720px) {
  .report-header {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>

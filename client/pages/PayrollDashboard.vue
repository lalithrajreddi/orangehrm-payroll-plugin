<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <div>
          <oxd-text tag="h6" class="orangehrm-main-title">
            Payroll Dashboard
          </oxd-text>
          <oxd-text tag="p" class="orangehrm-input-hint">
            Payroll activity and cost for the selected period.
          </oxd-text>
        </div>
      </div>

      <oxd-form @submit-valid="loadDashboard">
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
          <oxd-button type="submit" display-type="secondary" label="Apply" />
        </oxd-form-actions>
      </oxd-form>

      <oxd-divider />

      <div class="status-grid">
        <div class="card">
          <span>Total Employees</span>
          <strong>{{ dashboard.employees }}</strong>
        </div>

        <div class="card">
          <span>Drafts Payrolls</span>
          <strong>{{ dashboard.drafts }}</strong>
        </div>

        <div class="card success">
          <span>Generated Payrolls</span>
          <strong>{{ dashboard.generated }}</strong>
        </div>

        <div class="card warning">
          <span>Pending Payrolls</span>
          <strong>{{ dashboard.pendingDrafts }}</strong>
        </div>
      </div>

      <div class="financial-grid">
        <div class="metric">
          <span>Gross Salary</span>
          <strong>{{ formatCurrency(dashboard.totalGross) }}</strong>
        </div>

        <div class="metric">
          <span>Deductions</span>
          <strong>{{ formatCurrency(dashboard.totalDeductions) }}</strong>
        </div>

        <div class="metric">
          <span>Taxes</span>
          <strong>{{ formatCurrency(dashboard.totalTaxes) }}</strong>
        </div>

        <div class="metric primary">
          <span>Net Payroll Cost</span>
          <strong>{{ formatCurrency(dashboard.payrollCost) }}</strong>
        </div>
      </div>

      <div class="chart-card">
        <div class="chart-header">
          <h3>Payroll Cost Trend</h3>
          <span>Monthly trend for {{ year }}</span>
        </div>
        <div class="chart-container">
          <canvas ref="payrollChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from 'chart.js';

Chart.register(
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
);

export default {
  name: 'PayrollDashboard',

  data() {
    const currentYear = new Date().getFullYear();

    return {
      chart: null,
      monthOptions: [
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
      selectedMonth: null,
      yearOptions: Array.from(
        {length: 7},
        (_, index) => currentYear - 5 + index,
      )
        .reverse()
        .map((year) => ({id: year, label: String(year)})),
      selectedYear: {id: currentYear, label: String(currentYear)},
      dashboard: {
        employees: 0,
        drafts: 0,
        generated: 0,
        pendingDrafts: 0,
        draftsNeeded: 0,
        payrollCost: 0,
        totalGross: 0,
        totalDeductions: 0,
        totalTaxes: 0,
        totalNet: 0,
      },
    };
  },

  computed: {
    month() {
      return this.selectedMonth?.id;
    },
    year() {
      return this.selectedYear?.id;
    },
  },

  created() {
    this.selectedMonth = this.monthOptions[new Date().getMonth()];
  },

  async mounted() {
    await this.loadDashboard();
  },

  beforeUnmount() {
    this.chart?.destroy();
  },

  methods: {
    getParams() {
      return {
        month: this.month || undefined,
        year: this.year || undefined,
      };
    },

    async loadDashboard() {
      const params = this.getParams();
      const [dashboardResponse, chartResponse] = await Promise.all([
        axios.get('/web/index.php/payroll/dashboard', {params}),
        axios.get('/web/index.php/payroll/dashboard/chart', {
          params: {year: this.year || undefined},
        }),
      ]);

      this.dashboard = dashboardResponse.data;
      this.renderChart(chartResponse.data);
    },

    renderChart(data) {
      this.chart?.destroy();

      this.chart = new Chart(this.$refs.payrollChart, {
        type: 'bar',
        data: {
          labels: data.map((item) => item.month),
          datasets: [
            {
              label: 'Net Payroll Cost',
              data: data.map((item) => item.payrollCost),
              backgroundColor: '#ff7b1c',
              borderRadius: 6,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
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

.page-header,
.chart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.page-header {
  margin-bottom: 20px;
}

.page-header p {
  color: #64728c;
  margin-top: 4px;
}

.status-grid,
.financial-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.card,
.metric,
.chart-card {
  background: #fff;
  border: 1px solid #e8eaef;
  border-radius: 10px;
  padding: 18px;
}

.card {
  border-left: 4px solid #64728c;
}

.card.success {
  border-left-color: #34a853;
}

.card.warning {
  border-left-color: #ff9800;
}

.card.danger {
  border-left-color: #e74c3c;
}

.card span,
.metric span {
  color: #64728c;
  display: block;
  font-size: 13px;
  margin-bottom: 8px;
}

.card strong {
  font-size: 28px;
}

.metric strong {
  font-size: 20px;
}

.metric.primary {
  background: #fff5ed;
  border-color: #ffb27c;
}

.chart-card {
  height: auto;
}

.chart-container {
  position: relative;
  height: 280px;
}

.chart-header {
  margin-bottom: 16px;
}

.chart-header span {
  color: #64728c;
}

@media (max-width: 720px) {
  .page-header {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>

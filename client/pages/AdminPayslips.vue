<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        Employee Payslips
      </oxd-text>
      <oxd-divider />

      <oxd-form>
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <employee-autocomplete v-model="selectedEmployee" />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="selectedPeriod"
                type="select"
                label="Available Payslips"
                :options="periods"
                :disabled="!selectedEmployee || periods.length === 0"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-text
          v-if="selectedEmployee && periods.length === 0"
          class="no-payslip"
        >
          No payslips are available for this employee.
        </oxd-text>

        <oxd-divider />
        <oxd-form-actions>
          <oxd-button
            display-type="secondary"
            label="Download Payslip"
            icon-name="download"
            :disabled="!selectedPeriod"
            @click="downloadPayslip"
          />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import EmployeeAutocomplete from '@/core/components/inputs/EmployeeAutocomplete';

export default {
  name: 'AdminPayslips',

  components: {
    'employee-autocomplete': EmployeeAutocomplete,
  },

  data() {
    return {
      selectedEmployee: null,
      periods: [],
      selectedPeriod: null,
    };
  },

  watch: {
    selectedEmployee() {
      this.loadPeriods();
    },
  },

  methods: {
    async loadPeriods() {
      if (!this.selectedEmployee) {
        this.periods = [];
        this.selectedPeriod = null;
        return;
      }

      const response = await axios.get(
        `/web/index.php/payroll/periods/${this.selectedEmployee.id}`,
      );
      this.periods = response.data;
      this.selectedPeriod = this.periods[0] || null;
    },

    downloadPayslip() {
      window.open(
        `/web/index.php/payroll/downloadSalarySlip/empNumber/${this.selectedEmployee.id}/${this.selectedPeriod.month}/${this.selectedPeriod.year}`,
        '_blank',
      );
    },
  },
};
</script>

<style lang="scss" scoped>
.no-payslip {
  color: $oxd-feedback-danger-color;
}
</style>

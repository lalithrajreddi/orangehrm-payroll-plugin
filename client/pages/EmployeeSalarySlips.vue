<template>
  <div class="orangehrm-card-container">
    <oxd-text tag="h6" class="orangehrm-main-title">Salary Slips</oxd-text>

    <oxd-form class="orangehrm-form">
      <oxd-form-row>
        <oxd-input-field
          v-model="selectedPeriod"
          label="Payroll Period"
          type="select"
          :options="availablePeriods"
        />
      </oxd-form-row>

      <oxd-form-actions>
        <oxd-button
          display-type="secondary"
          label="Download Salary Slip"
          @click="downloadSlip"
        />
      </oxd-form-actions>
    </oxd-form>
  </div>
</template>

<script>
export default {
  props: {
    empNumber: {
      type: Number,
      required: true,
    },
  },

  data() {
    return {
      availablePeriods: [],
      selectedPeriod: null,
    };
  },

  async mounted() {
    const response = await fetch(
      `${window.appGlobal.baseUrl}/payroll/periods/${this.empNumber}`,
    );

    this.availablePeriods = await response.json();

    if (this.availablePeriods.length > 0) {
      this.selectedPeriod = this.availablePeriods[0];
    }
  },

  methods: {
    downloadSlip() {
      if (!this.selectedPeriod) {
        return;
      }

      const [month, year] = this.selectedPeriod.id.split('-');

      window.location.href =
        `${window.appGlobal.baseUrl}` +
        `/payroll/downloadSalarySlip/empNumber/${this.empNumber}` +
        `/${month}/${year}`;
    },
  },
};
</script>

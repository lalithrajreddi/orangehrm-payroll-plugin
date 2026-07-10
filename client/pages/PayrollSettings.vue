<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        Payroll Settings
      </oxd-text>
      <oxd-divider />

      <oxd-form :loading="loading" @submit-valid="saveSettings">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="settings.PF_PERCENTAGE"
                label="PF Percentage"
                step="0.01"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="settings.ESI_PERCENTAGE"
                label="ESI Percentage"
                step="0.01"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="settings.PROFESSIONAL_TAX"
                label="Professional Tax"
                step="0.01"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="settings.TDS_PERCENTAGE"
                label="TDS Percentage"
                step="0.01"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-divider />
        <oxd-form-actions>
          <oxd-button type="submit" display-type="secondary" label="Save" />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'PayrollSettings',

  data() {
    return {
      loading: false,
      settings: {
        PF_PERCENTAGE: '',
        ESI_PERCENTAGE: '',
        PROFESSIONAL_TAX: '',
        TDS_PERCENTAGE: '',
      },
    };
  },

  async mounted() {
    const response = await axios.get('/web/index.php/payroll/settings');
    Object.assign(this.settings, response.data);
  },

  methods: {
    async saveSettings() {
      this.loading = true;

      try {
        await axios.post('/web/index.php/payroll/settings/save', this.settings);
        this.$toast.saveSuccess();
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

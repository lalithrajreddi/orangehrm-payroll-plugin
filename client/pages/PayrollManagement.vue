<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          Payroll Management
        </oxd-text>
      </div>

      <div class="summary-cards">
        <div
          v-for="card in summaryCards"
          :key="card.label"
          class="summary-card"
          :class="card.className"
        >
          <span class="summary-card__label">{{ card.label }}</span>
          <strong class="summary-card__value">{{ card.value }}</strong>
        </div>
      </div>

      <oxd-divider />

      <oxd-text tag="h6" class="orangehrm-sub-title">
        Create Payroll Drafts
      </oxd-text>
      <oxd-form :loading="loading" @submit-valid="createAllDrafts">
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
                v-model="year"
                label="Year"
                min="2000"
                max="2100"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-actions>
          <oxd-button
            type="submit"
            display-type="secondary"
            label="Create Drafts for All Employees"
            icon-name="plus"
          />
        </oxd-form-actions>
      </oxd-form>

      <oxd-divider />

      <div class="orangehrm-header-container">
        <div>
          <oxd-text tag="h6" class="orangehrm-sub-title">
            Bulk Draft Adjustments
          </oxd-text>
          <oxd-text tag="p" class="orangehrm-input-hint">
            Import bonuses, allowances, deductions, or taxes from CSV/XLSX.
          </oxd-text>
        </div>
        <oxd-button
          display-type="ghost"
          label="Download Template"
          icon-name="download"
          @click="downloadImportTemplate"
        />
      </div>

      <div class="orangehrm-information-card-container">
        <oxd-text tag="p">
          Required columns: employee_id, month, year, component_name,
          component_type, amount. Component type must be BONUS, ALLOWANCE,
          DEDUCTION, or TAX.
        </oxd-text>
      </div>

      <oxd-form :loading="importing" @submit-valid="importDraftItems">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="importAttachment"
                type="file"
                label="Select Spreadsheet"
                button-label="Browse"
                placeholder="No file selected"
                accept=".csv,.xlsx"
                required
              />
              <oxd-text class="orangehrm-input-hint" tag="p">
                Accepts CSV or XLSX files up to 5 MB.
              </oxd-text>
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-actions>
          <oxd-button
            type="submit"
            display-type="secondary"
            label="Import Draft Adjustments"
            icon-name="upload"
            :disabled="!importAttachment"
          />
        </oxd-form-actions>
      </oxd-form>

      <oxd-divider />

      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-sub-title">
          Payroll Drafts
        </oxd-text>
      </div>

      <oxd-grid :cols="3" class="orangehrm-full-width-grid bulk-actions">
        <oxd-grid-item>
          <oxd-input-field
            v-model="searchText"
            label="Employee"
            placeholder="Search by employee name"
          />
        </oxd-grid-item>
        <oxd-grid-item>
          <oxd-input-field
            v-model="selectedAction"
            type="select"
            label="Bulk Action"
            :options="actionOptions"
          />
        </oxd-grid-item>
        <oxd-grid-item class="bulk-actions__button">
          <oxd-button
            display-type="secondary"
            label="Apply"
            :disabled="selectedDrafts.length === 0 || !selectedAction"
            @click="applyAction"
          />
        </oxd-grid-item>
      </oxd-grid>

      <div class="orangehrm-container payroll-table-wrap">
        <table class="payroll-table">
          <thead>
            <tr>
              <th>
                <input
                  type="checkbox"
                  :checked="allVisibleSelected"
                  @change="toggleSelectAll"
                />
              </th>
              <th>Employee</th>
              <th>Period</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payrollDraft in filteredDrafts" :key="payrollDraft.id">
              <td>
                <input
                  v-model="selectedDrafts"
                  type="checkbox"
                  :value="payrollDraft.id"
                />
              </td>
              <td>{{ payrollDraft.employeeName }}</td>
              <td>
                {{ getMonthName(payrollDraft.month) }} {{ payrollDraft.year }}
              </td>
              <td>
                <span
                  class="status-badge"
                  :class="`status-badge--${payrollDraft.status.toLowerCase()}`"
                >
                  {{ payrollDraft.status }}
                </span>
              </td>
              <td>
                <oxd-icon-button
                  name="pencil-fill"
                  title="Edit Draft"
                  @click="loadDraft(payrollDraft.id)"
                />
              </td>
            </tr>
            <tr v-if="filteredDrafts.length === 0">
              <td colspan="5" class="empty-state">No payroll drafts found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <oxd-dialog
      v-if="showDraftModal && draft"
      class="payroll-dialog"
      @update:show="closeDraftModal"
    >
      <div class="orangehrm-modal-header">
        <div>
          <oxd-text tag="h6">Payroll Draft #{{ draft.id }}</oxd-text>
          <oxd-text tag="p" class="orangehrm-input-hint">
            Employee {{ draft.empNumber }} · {{ getMonthName(draft.month) }}
            {{ draft.year }}
          </oxd-text>
        </div>
      </div>

      <div v-if="draftModified" class="draft-warning">
        Draft modified. Save it before closing the editor.
      </div>

      <oxd-form @submit-valid="addDraftItem">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="newItemName"
                label="Component Name"
                placeholder="Performance Bonus"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="newItemType"
                type="select"
                label="Component Type"
                :options="componentTypeOptions"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="newItemAmount"
                label="Amount"
                min="0.01"
                step="0.01"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>
        <oxd-form-actions>
          <oxd-button
            type="submit"
            display-type="secondary"
            label="Add Item"
            icon-name="plus"
          />
        </oxd-form-actions>
      </oxd-form>

      <div class="payroll-table-wrap">
        <table class="payroll-table">
          <thead>
            <tr>
              <th>Component</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in draftItems" :key="item.id">
              <td>{{ item.name }}</td>
              <td>{{ item.type }}</td>
              <td>{{ formatCurrency(item.amount) }}</td>
              <td>
                <oxd-icon-button
                  v-if="!item.isSystemGenerated"
                  name="trash"
                  title="Delete Item"
                  @click="deleteDraftItem(item.id)"
                />
                <oxd-text v-else tag="span" class="system-component">
                  System Component
                </oxd-text>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="draft-summary">
        <div>
          <span>Gross</span>
          <strong>{{ formatCurrency(draft.summary.grossSalary) }}</strong>
        </div>
        <div>
          <span>Allowances</span>
          <strong>{{ formatCurrency(draft.summary.allowances) }}</strong>
        </div>
        <div>
          <span>Bonuses</span>
          <strong>{{ formatCurrency(draft.summary.bonuses) }}</strong>
        </div>
        <div>
          <span>Deductions</span>
          <strong>{{ formatCurrency(draft.summary.deductions) }}</strong>
        </div>
        <div>
          <span>Taxes</span>
          <strong>{{ formatCurrency(draft.summary.taxes) }}</strong>
        </div>
        <div class="draft-summary__net">
          <span>Net Salary</span>
          <strong>{{ formatCurrency(draft.summary.netSalary) }}</strong>
        </div>
      </div>

      <div class="orangehrm-modal-footer">
        <oxd-button
          display-type="ghost"
          label="Close"
          @click="closeDraftModal"
        />
        <oxd-button
          display-type="secondary"
          label="Save Draft"
          :disabled="!draftModified"
          @click="saveDraft"
        />
      </div>
    </oxd-dialog>

    <oxd-dialog
      v-if="importResult"
      class="import-result-dialog"
      @update:show="importResult = null"
    >
      <div class="orangehrm-modal-header">
        <oxd-text tag="h6">Import Results</oxd-text>
      </div>
      <div class="import-result-summary">
        <oxd-text class="import-success">
          {{ importResult.success }} rows imported successfully.
        </oxd-text>
        <oxd-text v-if="importResult.failed" class="import-error">
          {{ importResult.failed }} rows failed.
        </oxd-text>
      </div>
      <div v-if="importResult.errors.length" class="import-errors">
        <div v-for="error in importResult.errors" :key="error.row">
          Row {{ error.row }}: {{ error.message }}
        </div>
      </div>
      <div class="orangehrm-modal-footer">
        <oxd-button
          display-type="secondary"
          label="OK"
          @click="importResult = null"
        />
      </div>
    </oxd-dialog>
  </div>
</template>

<script>
import axios from 'axios';
import {OxdDialog} from '@ohrm/oxd';

export default {
  name: 'PayrollManagement',

  components: {
    'oxd-dialog': OxdDialog,
  },

  data() {
    const monthOptions = [
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
    ].map((label, index) => ({id: index + 1, label}));

    return {
      loading: false,
      importing: false,
      monthOptions,
      selectedMonth: monthOptions[new Date().getMonth()],
      year: new Date().getFullYear(),
      searchText: '',
      selectedDrafts: [],
      selectedAction: null,
      actionOptions: [
        {id: 'generate', label: 'Generate Payroll'},
        {id: 'delete', label: 'Delete Draft'},
      ],
      draft: null,
      draftItems: [],
      drafts: [],
      draftModified: false,
      showDraftModal: false,
      newItemName: '',
      newItemType: {id: 'ALLOWANCE', label: 'Allowance'},
      newItemAmount: '',
      componentTypeOptions: [
        {id: 'ALLOWANCE', label: 'Allowance'},
        {id: 'BONUS', label: 'Bonus'},
        {id: 'DEDUCTION', label: 'Deduction'},
        {id: 'TAX', label: 'Tax'},
      ],
      importAttachment: null,
      importResult: null,
      summary: {
        employees: 0,
        drafts: 0,
        generated: 0,
        pendingDrafts: 0,
        draftsNeeded: 0,
      },
    };
  },

  computed: {
    month() {
      return this.selectedMonth?.id;
    },

    summaryCards() {
      return [
        {
          label: 'Total Employees',
          value: this.summary.employees,
        },
        {
          label: 'Draft Payrolls',
          value: this.summary.drafts,
        },
        {
          label: 'Generated Payrolls',
          value: this.summary.generated,
          className: 'summary-card--success',
        },
        {
          label: 'Pending Payrolls',
          value: this.summary.pendingDrafts,
          className: 'summary-card--warning',
        },
      ];
    },

    filteredDrafts() {
      const query = this.searchText.trim().toLowerCase();

      if (!query) {
        return this.drafts;
      }

      return this.drafts.filter((draft) =>
        draft.employeeName.toLowerCase().includes(query),
      );
    },

    allVisibleSelected() {
      return (
        this.filteredDrafts.length > 0 &&
        this.filteredDrafts.every((draft) =>
          this.selectedDrafts.includes(draft.id),
        )
      );
    },
  },

  watch: {
    selectedMonth() {
      this.loadSummary();
    },
    year() {
      this.loadSummary();
    },
  },

  async mounted() {
    await Promise.all([this.loadDrafts(), this.loadSummary()]);
  },

  methods: {
    getMonthName(month) {
      return this.monthOptions[Number(month) - 1]?.label || month;
    },

    formatCurrency(value) {
      return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
      }).format(Number(value) || 0);
    },

    async loadDrafts() {
      const response = await axios.get('/web/index.php/payroll/drafts');
      this.drafts = response.data;
    },

    async loadSummary() {
      const response = await axios.get('/web/index.php/payroll/dashboard', {
        params: {month: this.month, year: this.year},
      });
      this.summary = response.data;
    },

    async createAllDrafts() {
      this.loading = true;

      try {
        const response = await axios.post(
          '/web/index.php/payroll/draft/create-all',
          {month: this.month, year: this.year},
        );
        this.$toast.success({
          title: 'Success',
          message: `${response.data.count} payroll drafts created`,
        });
        await Promise.all([this.loadDrafts(), this.loadSummary()]);
      } finally {
        this.loading = false;
      }
    },

    async importDraftItems() {
      if (!this.importAttachment) return;
      this.importing = true;

      try {
        const response = await axios.post(
          '/web/index.php/payroll/draft/import',
          {attachment: this.importAttachment},
        );
        this.importResult = response.data.result;
        this.importAttachment = null;
        await Promise.all([this.loadDrafts(), this.loadSummary()]);
      } catch (error) {
        this.$toast.unexpectedError(
          error.response?.data?.error || 'Unable to import spreadsheet',
        );
      } finally {
        this.importing = false;
      }
    },

    downloadImportTemplate() {
      window.open('/web/index.php/payroll/draft/import-template', '_blank');
    },

    async loadDraft(draftId) {
      const response = await axios.get(
        `/web/index.php/payroll/draft/${draftId}`,
      );
      this.draft = response.data;
      this.draftItems = response.data.items;
      this.draftModified = false;
      this.showDraftModal = true;
    },

    closeDraftModal() {
      this.showDraftModal = false;
    },

    async addDraftItem() {
      await axios.post('/web/index.php/payroll/draft/item/add', {
        draftId: this.draft.id,
        componentName: this.newItemName,
        componentType: this.newItemType.id,
        amount: this.newItemAmount,
      });
      await this.refreshOpenDraft();
      this.draftModified = true;
      this.newItemName = '';
      this.newItemAmount = '';
      this.newItemType = this.componentTypeOptions[0];
    },

    async deleteDraftItem(itemId) {
      await axios.get(`/web/index.php/payroll/draft/item/delete/${itemId}`);
      await this.refreshOpenDraft();
      this.draftModified = true;
    },

    async refreshOpenDraft() {
      const response = await axios.get(
        `/web/index.php/payroll/draft/${this.draft.id}`,
      );
      this.draft = response.data;
      this.draftItems = response.data.items;
    },

    async saveDraft() {
      const response = await axios.post(
        `/web/index.php/payroll/draft/save/${this.draft.id}`,
      );
      this.draft.status = response.data.status;
      this.draftModified = false;
      this.$toast.saveSuccess();
      await Promise.all([this.loadDrafts(), this.loadSummary()]);
    },

    async applyAction() {
      const isGenerate = this.selectedAction.id === 'generate';
      const endpoint = isGenerate
        ? '/web/index.php/payroll/draft/generate-selected'
        : '/web/index.php/payroll/draft/delete-selected';
      const response = await axios.post(endpoint, {
        draftIds: this.selectedDrafts,
      });

      this.$toast.success({
        title: 'Success',
        message: isGenerate
          ? `${response.data.generated} payrolls generated`
          : `${response.data.deleted} drafts deleted`,
      });
      this.selectedDrafts = [];
      this.selectedAction = null;
      await Promise.all([this.loadDrafts(), this.loadSummary()]);
    },

    toggleSelectAll(event) {
      if (event.target.checked) {
        this.selectedDrafts = this.filteredDrafts.map((draft) => draft.id);
      } else {
        this.selectedDrafts = [];
      }
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

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  padding: 1rem 0;
}

.summary-card {
  background: #fff;
  border: 1px solid #e8eaef;
  border-left: 0.25rem solid $oxd-interface-gray-color;
  border-radius: 1rem;
  padding: 1rem;

  &--success {
    border-left-color: $oxd-feedback-success-color;
  }

  &--warning {
    border-left-color: $oxd-feedback-warn-color;
  }

  &--danger {
    border-left-color: $oxd-feedback-danger-color;
  }

  &__label {
    color: #64728c;
    display: block;
    font-size: 13px;
    margin-bottom: 8px;
  }

  &__value {
    font-size: 1.75rem;
    font-weight: 700;
  }
}

.orangehrm-information-card-container {
  padding: 1rem;
  margin-bottom: 1rem;
  border-radius: 1rem;
  background: $oxd-interface-gray-lighten-2-color;
}

.bulk-actions {
  align-items: end;
  margin-bottom: 1rem;

  &__button {
    padding-bottom: 0.2rem;
  }
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

  td:first-child {
    border-left: 1px solid $oxd-interface-gray-lighten-1-color;
    border-radius: 0.75rem 0 0 0.75rem;
  }

  td:last-child {
    border-right: 1px solid $oxd-interface-gray-lighten-1-color;
    border-radius: 0 0.75rem 0.75rem 0;
  }
}

.status-badge {
  display: inline-flex;
  padding: 0.25rem 0.65rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: $oxd-interface-gray-darken-1-color;
  background: $oxd-interface-gray-lighten-2-color;

  &--edited {
    color: $oxd-feedback-warn-color;
    background: rgba(255, 153, 0, 0.12);
  }
}

.draft-warning {
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
  border-radius: 0.75rem;
  color: $oxd-feedback-warn-color;
  background: rgba(255, 153, 0, 0.12);
}

.draft-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 0.75rem;
  margin-top: 1rem;

  div {
    padding: 0.75rem;
    border-radius: 0.75rem;
    background: $oxd-interface-gray-lighten-2-color;
  }

  span,
  strong {
    display: block;
  }

  span {
    color: $oxd-interface-gray-color;
    font-size: 0.75rem;
  }

  &__net {
    color: $oxd-primary-one-color;
  }
}

.orangehrm-modal-header {
  margin-bottom: 1rem;
}

.orangehrm-modal-footer {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.system-component {
  color: $oxd-primary-one-color;
  font-size: 0.75rem;
  font-weight: 700;
}

.import-result-summary {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.import-success {
  color: $oxd-feedback-success-color;
}

.import-error {
  color: $oxd-feedback-danger-color;
}

.import-errors {
  max-height: 15rem;
  padding: 1rem;
  overflow-y: auto;
  border-radius: 0.75rem;
  color: $oxd-feedback-danger-color;
  background: $oxd-interface-gray-lighten-2-color;
}

.empty-state {
  text-align: center;
  color: $oxd-interface-gray-color;
}

::v-deep(.payroll-dialog) {
  width: min(70rem, 95vw);
}

::v-deep(.import-result-dialog) {
  width: min(32rem, 95vw);
}
</style>

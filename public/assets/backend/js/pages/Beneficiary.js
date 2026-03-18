import CrudPage from "https://skeletor.greenfriends.systems/skeletorjs/src/Page/CrudPage.js";
import Loader from "https://skeletor.greenfriends.systems/skeletorjs/src/Loader/Loader.js";

export default class Beneficiary extends CrudPage {
    #data;
    #formTabs;
    #formAction;
    #rowIndex = 0;

    constructor() {
        super();
        this.dataTableOptions = {
            enableCheckboxes: true,
            shiftCheckboxModifier: true
        };
        this.modalOptions = {
            createModalWidth: '70%',
            createModalHeight: '70%',
            editModalWidth: '70%',
            editModalHeight: '70%'
        }
    }

    onFormReady(data) {
        this.#rowIndex = 0;
        this.#initRegisteredPeriods();
    }

    onFormSubmitStart() {
        // Inject registered periods data as hidden inputs inside the form before submission
        const form = this.modal.getForm();
        if (!form) return;

        // Remove any previously injected hidden inputs
        form.querySelectorAll('.rp-hidden-input').forEach(el => el.remove());

        const tbody = document.getElementById('registered-periods-body');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, idx) => {
            const project = row.querySelector('select[name*="[project]"]');
            const period = row.querySelector('select[name*="[period]"]');
            const amount = row.querySelector('input[name*="[amount]"]');

            if (project) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `registeredPeriods[${idx}][project]`;
                input.value = project.value;
                input.className = 'rp-hidden-input';
                form.appendChild(input);
            }
            if (period) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `registeredPeriods[${idx}][period]`;
                input.value = period.value;
                input.className = 'rp-hidden-input';
                form.appendChild(input);
            }
            if (amount) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `registeredPeriods[${idx}][amount]`;
                input.value = amount.value;
                input.className = 'rp-hidden-input';
                form.appendChild(input);
            }
        });
    }

    #initRegisteredPeriods() {
        const container = document.getElementById('registered-periods-container');
        if (!container) return;

        const periods = JSON.parse(container.dataset.periods || '{}');
        const projects = JSON.parse(container.dataset.projects || '{}');
        const existing = JSON.parse(container.dataset.existing || '[]');
        const tbody = document.getElementById('registered-periods-body');
        const addBtn = document.getElementById('add-period-row');

        if (!tbody || !addBtn) return;

        const self = this;

        const buildOptions = (items, selectedValue, placeholder) => {
            let options = `<option value="">${placeholder}</option>`;
            for (const [id, label] of Object.entries(items)) {
                const selected = (String(id) === String(selectedValue)) ? 'selected' : '';
                options += `<option value="${id}" ${selected}>${label}</option>`;
            }
            return options;
        };

        const addRow = (projectId = '', periodId = '', amount = '') => {
            const tr = document.createElement('tr');
            const idx = self.#rowIndex++;
            tr.innerHTML = `
                <td style="padding: 4px;">
                    <select name="registeredPeriods[${idx}][project]" class="form-control" style="width: 100%;">
                        ${buildOptions(projects, projectId, '-- Select Project --')}
                    </select>
                </td>
                <td style="padding: 4px;">
                    <select name="registeredPeriods[${idx}][period]" class="form-control" style="width: 100%;">
                        ${buildOptions(periods, periodId, '-- Select Period --')}
                    </select>
                </td>
                <td style="padding: 4px;">
                    <input type="number" name="registeredPeriods[${idx}][amount]" value="${amount}" class="form-control" min="1" style="width: 100%;" />
                </td>
                <td style="padding: 4px; text-align: center;">
                    <button type="button" class="btn btn-sm btn-danger remove-period-row">\u2715</button>
                </td>
            `;
            tr.querySelector('.remove-period-row').addEventListener('click', () => tr.remove());
            tbody.appendChild(tr);
        };

        // Populate existing rows on edit, or one empty row on create
        if (existing.length > 0) {
            existing.forEach(rp => addRow(rp.project, rp.period, rp.amount));
        } else {
            addRow();
        }

        addBtn.addEventListener('click', () => addRow());
    }

    actionFilter = (action, entity) => {
        const role = document.getElementById('navigation').dataset.role;
        if (action.getName() === 'delete' && role != 1) {
            return false;
        }
        return action;
    }

    tdStyler = (td, columnName, columnValue, entity) => {
        if (columnName === 'delegateVerified') {
            switch (columnValue) {
                case 'Da':
                    this.makeTDValueToBadge(td, columnValue, CrudPage.BADGE_TYPES.GREEN);
                    break;
                case 'Ne':
                    this.makeTDValueToBadge(td, columnValue, CrudPage.BADGE_TYPES.RED);
                    break;
            }
        }
        if (columnName === 'status') {
            switch (columnValue) {
                case 'Ok':
                    this.makeTDValueToBadge(td, columnValue, CrudPage.BADGE_TYPES.GREEN);
                    break;
                case 'Problem':
                    this.makeTDValueToBadge(td, columnValue, CrudPage.BADGE_TYPES.GRAY);
                    break;
                case 'Gave up':
                    this.makeTDValueToBadge(td, columnValue, CrudPage.BADGE_TYPES.GRAY);
                    break;
            }
        }
        return td;
    }
}

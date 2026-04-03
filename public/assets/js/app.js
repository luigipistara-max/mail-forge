'use strict';

// ─── CSRF Helper ─────────────────────────────────────────────────────────────
const Csrf = {
  token() {
    return document.querySelector('meta[name="csrf-token"]')?.content
      || window.csrfToken
      || '';
  },
  header() {
    return { 'X-CSRF-Token': this.token() };
  },
};

// ─── Fetch Wrapper ────────────────────────────────────────────────────────────
const Http = {
  async get(url, options = {}) {
    const res = await fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json', ...options.headers },
      ...options,
    });
    return Http._handle(res);
  },

  async post(url, data = {}, options = {}) {
    const isFormData = data instanceof FormData;
    const headers = {
      'Accept': 'application/json',
      ...Csrf.header(),
      ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
      ...options.headers,
    };
    const res = await fetch(url, {
      method: 'POST',
      headers,
      body: isFormData ? data : JSON.stringify(data),
      ...options,
    });
    return Http._handle(res);
  },

  async _handle(res) {
    if (!res.ok) {
      let msg = `HTTP ${res.status}`;
      try { const j = await res.json(); msg = j.message || msg; } catch (_) {}
      throw new Error(msg);
    }
    const ct = res.headers.get('Content-Type') || '';
    return ct.includes('application/json') ? res.json() : res.text();
  },
};

// ─── Flash / Alert Auto-dismiss ───────────────────────────────────────────────
function initAlertAutoDismiss(delayMs = 5000) {
  document.querySelectorAll('.alert-dismissible').forEach((el) => {
    setTimeout(() => {
      el.classList.remove('show');
      el.addEventListener('transitionend', () => el.remove(), { once: true });
    }, delayMs);
  });
}

// ─── Sidebar Collapse Toggle (desktop) ───────────────────────────────────────
const SidebarToggle = {
  init() {
    const btn = document.getElementById('sidebarCollapseBtn');
    if (!btn) return;
    btn.addEventListener('click', () => {
      document.body.classList.toggle('sidebar-collapsed');
      localStorage.setItem(
        'mf-sidebar-collapsed',
        document.body.classList.contains('sidebar-collapsed') ? '1' : '0',
      );
    });
    if (localStorage.getItem('mf-sidebar-collapsed') === '1') {
      document.body.classList.add('sidebar-collapsed');
    }
  },
};

// ─── Delete Confirmation Modal ────────────────────────────────────────────────
const DeleteModal = {
  init() {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;

    const bsModal   = new bootstrap.Modal(modal);
    const nameEl    = modal.querySelector('[data-delete-name-placeholder]');
    const confirmEl = modal.querySelector('#deleteConfirmBtn');

    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('[data-delete-url]');
      if (!trigger) return;
      e.preventDefault();

      const url  = trigger.dataset.deleteUrl;
      const name = trigger.dataset.deleteName || 'this item';

      if (nameEl) nameEl.textContent = name;

      if (confirmEl) {
        const clone = confirmEl.cloneNode(true);
        confirmEl.replaceWith(clone);
        clone.addEventListener('click', () => {
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = url;
          form.innerHTML = `
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="csrf_token" value="${Csrf.token()}">
          `;
          document.body.appendChild(form);
          form.submit();
        });
      }

      bsModal.show();
    });
  },
};

// ─── Form Dirty Check ─────────────────────────────────────────────────────────
const DirtyCheck = {
  init() {
    document.querySelectorAll('form[data-dirty-check]').forEach((form) => {
      let dirty = false;
      form.addEventListener('change', () => { dirty = true; });
      form.addEventListener('input',  () => { dirty = true; });
      form.addEventListener('submit', () => { dirty = false; });

      window.addEventListener('beforeunload', (e) => {
        if (dirty) {
          e.preventDefault();
          e.returnValue = '';
        }
      });
    });
  },
};

// ─── Segment Rule Builder ─────────────────────────────────────────────────────
const SegmentBuilder = {
  container: null,
  fieldOptions: [],
  operatorMap: {
    text:   [
      { value: 'equals',      label: 'equals' },
      { value: 'not_equals',  label: 'does not equal' },
      { value: 'contains',    label: 'contains' },
      { value: 'not_contains',label: 'does not contain' },
      { value: 'starts_with', label: 'starts with' },
      { value: 'ends_with',   label: 'ends with' },
      { value: 'is_empty',    label: 'is empty' },
      { value: 'is_not_empty',label: 'is not empty' },
    ],
    number: [
      { value: 'eq',  label: '= equals' },
      { value: 'neq', label: '≠ not equals' },
      { value: 'gt',  label: '> greater than' },
      { value: 'gte', label: '≥ greater than or equal' },
      { value: 'lt',  label: '< less than' },
      { value: 'lte', label: '≤ less than or equal' },
    ],
    date: [
      { value: 'before',        label: 'before' },
      { value: 'after',         label: 'after' },
      { value: 'on',            label: 'on' },
      { value: 'in_last_days',  label: 'in the last N days' },
      { value: 'not_in_last_days', label: 'not in the last N days' },
    ],
  },

  init(containerId, fieldOptions) {
    this.container    = document.getElementById(containerId);
    this.fieldOptions = fieldOptions || [];
    if (!this.container) return;

    const form = this.container.closest('form');
    if (form) {
      form.addEventListener('submit', () => this._serialize());
    }
  },

  addRule() {
    if (!this.container) return;
    const idx  = this.container.querySelectorAll('.rule-row').length;
    const row  = document.createElement('div');
    row.className = 'rule-row';
    row.dataset.ruleIdx = idx;

    row.innerHTML = `
      <select class="form-select form-select-sm rule-field" aria-label="Field">
        ${this.fieldOptions.map(f => `<option value="${f.value}" data-type="${f.type || 'text'}">${f.label}</option>`).join('')}
      </select>
      <select class="form-select form-select-sm rule-operator" aria-label="Operator"></select>
      <input type="text" class="form-control form-control-sm rule-value" placeholder="Value">
      <button type="button" class="btn btn-sm btn-outline-danger rule-remove" aria-label="Remove rule">
        <i class="bi bi-x-lg"></i>
      </button>
    `;

    this.container.appendChild(row);

    const fieldSel = row.querySelector('.rule-field');
    this._populateOperators(row, fieldSel.options[0]?.dataset.type || 'text');

    fieldSel.addEventListener('change', (e) => {
      const type = e.target.selectedOptions[0]?.dataset.type || 'text';
      this._populateOperators(row, type);
    });

    row.querySelector('.rule-remove').addEventListener('click', () => this.removeRule(row));
  },

  removeRule(rowOrBtn) {
    const row = rowOrBtn.closest ? rowOrBtn.closest('.rule-row') : rowOrBtn;
    if (row) row.remove();
  },

  _populateOperators(row, type) {
    const opMap  = this.operatorMap;
    const ops    = opMap[type] || opMap.text;
    const select = row.querySelector('.rule-operator');
    select.innerHTML = ops.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
  },

  _serialize() {
    if (!this.container) return;
    const rules = [];
    this.container.querySelectorAll('.rule-row').forEach((row) => {
      rules.push({
        field:    row.querySelector('.rule-field')?.value    || '',
        operator: row.querySelector('.rule-operator')?.value || '',
        value:    row.querySelector('.rule-value')?.value    || '',
      });
    });
    let hidden = this.container.closest('form').querySelector('input[name="rules_json"]');
    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'rules_json';
      this.container.closest('form').appendChild(hidden);
    }
    hidden.value = JSON.stringify(rules);
  },
};

// ─── Password Strength Meter ──────────────────────────────────────────────────
const PasswordMeter = {
  labels: ['Too short', 'Weak', 'Fair', 'Good', 'Strong'],

  init(inputId, meterId) {
    const input = document.getElementById(inputId);
    const meter = document.getElementById(meterId);
    if (!input || !meter) return;

    const bar   = meter.querySelector('.bar-fill');
    const label = meter.querySelector('.password-strength-label');

    input.addEventListener('input', () => {
      const score = this._score(input.value);
      meter.className = `password-strength-wrap strength-${score}`;
      if (bar)   bar.style.width = `${score * 20}%`;
      if (label) label.textContent = input.value.length ? this.labels[score - 1] || '' : '';
    });
  },

  _score(pw) {
    if (!pw || pw.length < 8) return pw.length ? 1 : 0;
    let s = 2;
    if (pw.length >= 12)          s++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) s++;
    if (/[0-9]/.test(pw))         s++;
    if (/[^A-Za-z0-9]/.test(pw))  s++;
    return Math.min(5, s);
  },
};

// ─── Clipboard Copy ───────────────────────────────────────────────────────────
function copyToClipboard(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    if (!btn) return;
    const icon = btn.querySelector('i') || btn;
    const orig = icon.className;
    icon.className = icon.className.replace(/bi-\S+/, 'bi-check-lg');
    btn.classList.add('copied');
    setTimeout(() => {
      icon.className = orig;
      btn.classList.remove('copied');
    }, 2000);
  }).catch(() => {
    Toast.show('Failed to copy to clipboard', 'error');
  });
}

function initCopyButtons() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    const text = btn.dataset.copy
      || document.getElementById(btn.dataset.copyTarget)?.value
      || '';
    copyToClipboard(text, btn);
  });
}

// ─── Toast Notifications ──────────────────────────────────────────────────────
const Toast = {
  _container: null,

  _getContainer() {
    if (!this._container) {
      this._container = document.createElement('div');
      this._container.id = 'mf-toast-container';
      document.body.appendChild(this._container);
    }
    return this._container;
  },

  show(message, type = 'info', durationMs = 4000) {
    const iconMap = {
      success: 'bi-check-circle-fill text-success',
      error:   'bi-exclamation-circle-fill text-danger',
      warning: 'bi-exclamation-triangle-fill text-warning',
      info:    'bi-info-circle-fill text-info',
    };
    const icon = iconMap[type] || iconMap.info;

    const toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center border-0 show';
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
      <div class="d-flex">
        <div class="toast-body d-flex align-items-center gap-2">
          <i class="bi ${icon} fs-5 flex-shrink-0"></i>
          <span>${message}</span>
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;

    this._getContainer().appendChild(toastEl);

    toastEl.querySelector('[data-bs-dismiss="toast"]').addEventListener('click', () => {
      this._dismiss(toastEl);
    });

    setTimeout(() => this._dismiss(toastEl), durationMs);
    return toastEl;
  },

  _dismiss(el) {
    el.classList.remove('show');
    el.addEventListener('transitionend', () => el.remove(), { once: true });
    setTimeout(() => el.remove(), 400);
  },
};

// ─── Table Search ─────────────────────────────────────────────────────────────
const TableSearch = {
  init(tableId, searchInputId) {
    const table = document.getElementById(tableId);
    const input = document.getElementById(searchInputId);
    if (!table || !input) return;

    input.addEventListener('input', () => {
      const q = input.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach((row) => {
        row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
      });
    });
  },
};

// ─── Chart.js Helpers ─────────────────────────────────────────────────────────
const Charts = {
  _defaults: {
    font:  { family: "'Segoe UI', system-ui, sans-serif", size: 12 },
    color: '#6c757d',
  },

  line(canvasId, labels, datasets, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === 'undefined') return null;

    return new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: datasets.map((ds, i) => ({
          tension: .4,
          fill: false,
          pointRadius: 3,
          pointHoverRadius: 5,
          borderWidth: 2,
          borderColor: ds.color || `hsl(${i * 60 + 210}, 80%, 55%)`,
          backgroundColor: ds.color || `hsla(${i * 60 + 210}, 80%, 55%, .1)`,
          ...ds,
        })),
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { labels: { ...this._defaults } },
          tooltip: { mode: 'index', intersect: false },
        },
        scales: {
          x: { grid: { display: false }, ticks: { ...this._defaults } },
          y: { beginAtZero: true, ticks: { ...this._defaults } },
        },
        ...options,
      },
    });
  },

  doughnut(canvasId, labels, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === 'undefined') return null;

    const defaultColors = [
      '#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#20c997',
    ];

    return new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data,
          backgroundColor: defaultColors.slice(0, data.length),
          borderWidth: 2,
          borderColor: '#fff',
          hoverOffset: 6,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '65%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { padding: 16, ...this._defaults },
          },
        },
        ...options,
      },
    });
  },
};

// ─── Auto-resize Textareas ────────────────────────────────────────────────────
function initAutoResize() {
  function resize(el) {
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight}px`;
  }
  document.querySelectorAll('textarea[data-autoresize]').forEach((el) => {
    resize(el);
    el.addEventListener('input', () => resize(el));
  });
}

// ─── DateTime-local Minimum ───────────────────────────────────────────────────
function initDateTimeMin() {
  document.querySelectorAll('input[type="datetime-local"][data-min-now]').forEach((el) => {
    const now = new Date();
    now.setSeconds(0, 0);
    el.min = now.toISOString().slice(0, 16);
  });
}

// ─── Bulk Action Helpers ──────────────────────────────────────────────────────
const BulkActions = {
  init() {
    document.querySelectorAll('[data-bulk-table]').forEach((wrapper) => {
      const tableId    = wrapper.dataset.bulkTable;
      const table      = document.getElementById(tableId);
      const selectAll  = wrapper.querySelector('[data-select-all]');
      const bulkForm   = wrapper.querySelector('[data-bulk-form]');
      const bulkBtn    = wrapper.querySelector('[data-bulk-submit]');
      const countLabel = wrapper.querySelector('[data-bulk-count]');

      if (!table) return;

      const getCheckboxes = () => table.querySelectorAll('tbody [data-bulk-check]');

      function updateState() {
        const all     = getCheckboxes();
        const checked = [...all].filter(c => c.checked);
        if (selectAll) {
          selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
          selectAll.checked       = checked.length === all.length && all.length > 0;
        }
        if (countLabel) countLabel.textContent = checked.length;
        if (bulkBtn)    bulkBtn.disabled = checked.length === 0;
      }

      if (selectAll) {
        selectAll.addEventListener('change', () => {
          getCheckboxes().forEach(c => { c.checked = selectAll.checked; });
          updateState();
        });
      }

      table.addEventListener('change', (e) => {
        if (e.target.matches('[data-bulk-check]')) updateState();
      });

      if (bulkForm && bulkBtn) {
        bulkBtn.addEventListener('click', () => {
          const checked = [...getCheckboxes()].filter(c => c.checked);
          bulkForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
          checked.forEach(c => {
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = 'ids[]';
            hidden.value = c.value;
            bulkForm.appendChild(hidden);
          });
          bulkForm.submit();
        });
      }

      updateState();
    });
  },
};

// ─── PWA Install Button Enhancement ──────────────────────────────────────────
// (Primary logic lives in the layout inline script; this handles any extra UI.)
function initPwaEnhancements() {
  window.addEventListener('appinstalled', () => {
    const btn = document.getElementById('pwaInstallBtn');
    if (btn) btn.classList.add('d-none');
  });
}

// ─── Bootstrap-aware Tooltip Init ────────────────────────────────────────────
function initTooltips() {
  if (typeof bootstrap === 'undefined') return;
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
    new bootstrap.Tooltip(el, { trigger: 'hover focus' });
  });
}

// ─── DOM Ready Bootstrap ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initAlertAutoDismiss(5000);
  SidebarToggle.init();
  DeleteModal.init();
  DirtyCheck.init();
  initCopyButtons();
  initAutoResize();
  initDateTimeMin();
  BulkActions.init();
  initPwaEnhancements();
  initTooltips();
});

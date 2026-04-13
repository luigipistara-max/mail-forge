<?php
/** @var array|null $segment */
/** @var array $errors */
/** @var array $filterFields */
$segment      = $segment      ?? null;
$errors       = $errors       ?? [];
$filterFields = $filterFields ?? [];
$isEdit = !empty($segment['id']);

$existingRules = [];
if (!empty($segment['rules']) && is_string($segment['rules'])) {
    $existingRules = json_decode($segment['rules'], true) ?: [];
} elseif (!empty($segment['rules']) && is_array($segment['rules'])) {
    $existingRules = $segment['rules'];
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0"><?= $isEdit ? 'Edit Segment' : 'Create Segment' ?></h6>
    <a href="<?= BASE_PATH ?>/segments" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-4">
    <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= $isEdit ? '/segments/' . (int)$segment['id'] . '/update' : '/segments' ?>">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Segment Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                            class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($segment['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="2"><?= htmlspecialchars($segment['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Match Type</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="match_type" id="match_all" value="all"
                                    <?= ($segment['match_type'] ?? 'all') === 'all' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="match_all">
                                    <strong>All</strong> rules must match
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="match_type" id="match_any" value="any"
                                    <?= ($segment['match_type'] ?? '') === 'any' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="match_any">
                                    <strong>Any</strong> rule must match
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                    <span class="fw-semibold">Filter Rules</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addRuleBtn">
                        <i class="bi bi-plus-lg me-1"></i>Add Rule
                    </button>
                </div>
                <div class="card-body">
                    <div id="rulesContainer">
                        <!-- Rules injected by JS -->
                    </div>
                    <div id="noRulesMsg" class="text-center text-muted py-3 <?= !empty($existingRules) ? 'd-none' : '' ?>">
                        <i class="bi bi-funnel fs-3 d-block mb-1 opacity-25"></i>
                        No rules yet. Click <strong>Add Rule</strong> to begin.
                    </div>
                    <input type="hidden" name="rules" id="rulesInput" value="<?= htmlspecialchars(json_encode($existingRules), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Preview</div>
                <div class="card-body text-center">
                    <div class="mb-3 text-muted small">Estimate how many contacts match these rules.</div>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="previewBtn">
                        <span class="btn-label"><i class="bi bi-people me-1"></i>Preview Count</span>
                        <span id="previewBadge" class="badge bg-primary ms-2 d-none"></span>
                    </button>
                    <div id="previewError" class="text-danger small mt-2 d-none"></div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Segment' ?>
                </button>
                <a href="<?= BASE_PATH ?>/segments" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    const filterFields = <?= json_encode(array_values($filterFields)) ?>;
    const operatorsByType = {
        text: [
            { value: 'equals',        label: 'Equals' },
            { value: 'not_equals',    label: 'Not Equals' },
            { value: 'contains',      label: 'Contains' },
            { value: 'not_contains',  label: 'Not Contains' },
            { value: 'starts_with',   label: 'Starts With' },
            { value: 'ends_with',     label: 'Ends With' },
            { value: 'is_empty',      label: 'Is Empty' },
            { value: 'is_not_empty',  label: 'Is Not Empty' },
        ],
        number: [
            { value: 'equals',        label: 'Equals' },
            { value: 'not_equals',    label: 'Not Equals' },
            { value: 'greater_than',  label: 'Greater Than' },
            { value: 'less_than',     label: 'Less Than' },
            { value: 'is_empty',      label: 'Is Empty' },
            { value: 'is_not_empty',  label: 'Is Not Empty' },
        ],
    };
    const noValueOps = ['is_empty', 'is_not_empty'];

    const container  = document.getElementById('rulesContainer');
    const rulesInput = document.getElementById('rulesInput');
    const noRulesMsg = document.getElementById('noRulesMsg');
    let rules = [];

    try { rules = JSON.parse(rulesInput.value) || []; } catch(e) { rules = []; }

    function buildFieldOptions(selected) {
        return filterFields.map(f =>
            `<option value="${esc(f.value || f)}" ${(f.value || f) === selected ? 'selected' : ''}>${esc(f.label || f)}</option>`
        ).join('');
    }

    function buildOperatorOptions(fieldType, selectedOp) {
        const ops = operatorsByType[fieldType] || operatorsByType.text;
        return ops.map(o =>
            `<option value="${esc(o.value)}" ${o.value === selectedOp ? 'selected' : ''}>${esc(o.label)}</option>`
        ).join('');
    }

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function getFieldType(fieldValue) {
        const f = filterFields.find(f => (f.value || f) === fieldValue);
        return (f && f.type) ? f.type : 'text';
    }

    function renderRules() {
        container.innerHTML = '';
        noRulesMsg.classList.toggle('d-none', rules.length > 0);
        rules.forEach((rule, idx) => {
            const fieldType = getFieldType(rule.field || '');
            const hideValue = noValueOps.includes(rule.operator || '');
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-center mb-2 rule-row';
            row.dataset.index = idx;
            row.innerHTML = `
                <div class="col-sm-4">
                    <select class="form-select form-select-sm rule-field" data-index="${idx}">
                        <option value="">— Field —</option>
                        ${buildFieldOptions(rule.field || '')}
                    </select>
                </div>
                <div class="col-sm-3">
                    <select class="form-select form-select-sm rule-operator" data-index="${idx}">
                        ${buildOperatorOptions(fieldType, rule.operator || '')}
                    </select>
                </div>
                <div class="col-sm-4 rule-value-col ${hideValue ? 'd-none' : ''}">
                    <input type="text" class="form-control form-control-sm rule-value" data-index="${idx}"
                        value="${esc(rule.value || '')}" placeholder="Value">
                </div>
                <div class="col-sm-1 ${hideValue ? 'col-sm-1' : ''}">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-rule" data-index="${idx}" title="Remove">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>`;
            container.appendChild(row);
        });
        syncInput();
    }

    function syncInput() {
        rulesInput.value = JSON.stringify(rules);
    }

    container.addEventListener('change', function (e) {
        const idx = parseInt(e.target.dataset.index, 10);
        if (isNaN(idx)) return;
        if (e.target.classList.contains('rule-field')) {
            rules[idx].field = e.target.value;
            rules[idx].operator = '';
            renderRules();
        } else if (e.target.classList.contains('rule-operator')) {
            rules[idx].operator = e.target.value;
            const valueCol = container.querySelector(`.rule-row[data-index="${idx}"] .rule-value-col`);
            if (valueCol) valueCol.classList.toggle('d-none', noValueOps.includes(e.target.value));
            syncInput();
        }
    });

    container.addEventListener('input', function (e) {
        const idx = parseInt(e.target.dataset.index, 10);
        if (isNaN(idx)) return;
        if (e.target.classList.contains('rule-value')) {
            rules[idx].value = e.target.value;
            syncInput();
        }
    });

    container.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-rule');
        if (!btn) return;
        const idx = parseInt(btn.dataset.index, 10);
        rules.splice(idx, 1);
        renderRules();
    });

    document.getElementById('addRuleBtn').addEventListener('click', function () {
        rules.push({ field: '', operator: 'equals', value: '' });
        renderRules();
    });

    document.getElementById('previewBtn').addEventListener('click', function () {
        const badge  = document.getElementById('previewBadge');
        const errDiv = document.getElementById('previewError');
        const btn    = this;
        const btnLabel = btn.querySelector('.btn-label');

        badge.classList.add('d-none');
        errDiv.classList.add('d-none');
        btn.disabled = true;
        btnLabel.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading…';

        fetch('/segments/preview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                rules: rules,
                match_type: document.querySelector('input[name="match_type"]:checked')?.value || 'all',
                csrf_token: document.querySelector('input[name="csrf_token"]')?.value || '',
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.count !== undefined) {
                badge.textContent = Number(data.count).toLocaleString() + ' contacts';
                badge.classList.remove('d-none');
            } else {
                errDiv.textContent = data.error || 'Preview failed.';
                errDiv.classList.remove('d-none');
            }
        })
        .catch(() => {
            errDiv.textContent = 'Request failed.';
            errDiv.classList.remove('d-none');
        })
        .finally(() => {
            btn.disabled = false;
            btnLabel.innerHTML = '<i class="bi bi-people me-1"></i>Preview Count';
        });
    });

    renderRules();
})();
</script>

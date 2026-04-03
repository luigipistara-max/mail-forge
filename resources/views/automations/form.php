<?php
/** @var array|null $automation */
/** @var array      $errors */
/** @var array      $templates */
/** @var array      $lists */
$automation = $automation ?? null;
$errors     = $errors     ?? [];
$templates  = $templates  ?? [];
$lists      = $lists      ?? [];

$isEdit = !empty($automation['id']);
$e = fn(string $key): string => htmlspecialchars($automation[$key] ?? '', ENT_QUOTES, 'UTF-8');

$steps = $automation['steps'] ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold"><?= $isEdit ? 'Edit Automation' : 'New Automation' ?></h1>
    <a href="/automations" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<?php if (!empty($errors) && isset($errors[0])): ?>
<div class="alert alert-danger mb-4">
    <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= $isEdit ? '/automations/' . (int)$automation['id'] : '/automations' ?>">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <div class="row g-4">
        <!-- Main fields -->
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Details</div>
                <div class="card-body">

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                            class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                            value="<?= $e('name') ?>" required>
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"><?= $e('description') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['description'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select id="status" name="status"
                                class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>">
                                <option value="inactive" <?= ($automation['status'] ?? 'inactive') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="active"   <?= ($automation['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="trigger_type" class="form-label fw-semibold">Trigger Type <span class="text-danger">*</span></label>
                            <select id="trigger_type" name="trigger_type"
                                class="form-select <?= isset($errors['trigger_type']) ? 'is-invalid' : '' ?>">
                                <option value="">— Select Trigger —</option>
                                <?php
                                $triggerOptions = [
                                    'list_subscribe'   => 'List Subscribe',
                                    'list_unsubscribe' => 'List Unsubscribe',
                                    'date_based'       => 'Date Based',
                                    'manual'           => 'Manual',
                                ];
                                foreach ($triggerOptions as $val => $label):
                                ?>
                                <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ($automation['trigger_type'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['trigger_type'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['trigger_type'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Trigger Configuration (JSON)</div>
                <div class="card-body">
                    <div class="mb-0">
                        <label for="trigger_config" class="form-label fw-semibold">Trigger Config</label>
                        <textarea id="trigger_config" name="trigger_config" rows="4"
                            class="form-control font-monospace <?= isset($errors['trigger_config']) ? 'is-invalid' : '' ?>"
                            placeholder='{"list_id": 1}'><?= $e('trigger_config') ?></textarea>
                        <div class="form-text">JSON object with trigger-specific configuration.</div>
                        <?php if (isset($errors['trigger_config'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['trigger_config'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Steps builder -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                    <span class="fw-semibold">Steps</span>
                    <button type="button" class="btn btn-outline-primary btn-sm"
                        data-bs-toggle="modal" data-bs-target="#addStepModal">
                        <i class="bi bi-plus-lg me-1"></i>Add Step
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($steps)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-diagram-3 fs-2 d-block mb-2 opacity-25"></i>
                        No steps yet. Add the first step.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="stepsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th>Type</th>
                                    <th>Configuration</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($steps as $idx => $step): ?>
                            <tr data-step-index="<?= (int)$idx ?>">
                                <td class="text-muted small"><?= (int)($step['order'] ?? $idx + 1) ?></td>
                                <td>
                                    <?php
                                    $stepTypeLabels = [
                                        'send_email'  => ['label' => 'Send Email',  'icon' => 'bi-envelope',    'color' => 'primary'],
                                        'wait'        => ['label' => 'Wait',        'icon' => 'bi-clock',       'color' => 'warning'],
                                        'condition'   => ['label' => 'Condition',   'icon' => 'bi-signpost-split', 'color' => 'info'],
                                        'tag_add'     => ['label' => 'Add Tag',     'icon' => 'bi-tag',         'color' => 'success'],
                                        'tag_remove'  => ['label' => 'Remove Tag',  'icon' => 'bi-tag-fill',    'color' => 'secondary'],
                                    ];
                                    $st = $stepTypeLabels[$step['type'] ?? ''] ?? ['label' => ucfirst($step['type'] ?? ''), 'icon' => 'bi-gear', 'color' => 'secondary'];
                                    ?>
                                    <span class="badge bg-<?= $st['color'] ?>">
                                        <i class="bi <?= $st['icon'] ?> me-1"></i><?= $st['label'] ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?php
                                    $config = $step['config'] ?? [];
                                    if (is_string($config)) {
                                        $config = json_decode($config, true) ?? [];
                                    }
                                    $summary = [];
                                    foreach (array_slice($config, 0, 3) as $k => $v) {
                                        $summary[] = htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
                                    }
                                    echo implode(', ', $summary) ?: '—';
                                    ?>
                                    <input type="hidden" name="steps[<?= (int)$idx ?>][type]"   value="<?= htmlspecialchars($step['type'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="steps[<?= (int)$idx ?>][order]"  value="<?= (int)($step['order'] ?? $idx + 1) ?>">
                                    <input type="hidden" name="steps[<?= (int)$idx ?>][config]" value="<?= htmlspecialchars(is_array($step['config'] ?? null) ? json_encode($step['config']) : ($step['config'] ?? '{}'), ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($idx > 0): ?>
                                        <button type="button" class="btn btn-outline-secondary btn-move-up" title="Move Up"
                                            data-index="<?= (int)$idx ?>">
                                            <i class="bi bi-arrow-up"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($idx < count($steps) - 1): ?>
                                        <button type="button" class="btn btn-outline-secondary btn-move-down" title="Move Down"
                                            data-index="<?= (int)$idx ?>">
                                            <i class="bi bi-arrow-down"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-danger btn-delete-step" title="Remove Step"
                                            data-index="<?= (int)$idx ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /col-lg-8 -->

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Automation' ?>
                        </button>
                        <a href="/automations" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <?php if (!empty($lists)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Available Lists</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                    <?php foreach ($lists as $list): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="small"><?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge bg-light text-dark border"><?= number_format((int)($list['subscriber_count'] ?? 0)) ?></span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /row -->
</form>

<!-- Add Step Modal -->
<div class="modal fade" id="addStepModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Step</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newStepType" class="form-label fw-semibold">Step Type</label>
                    <select id="newStepType" class="form-select" onchange="showStepConfig(this.value)">
                        <option value="">— Select Type —</option>
                        <option value="send_email">Send Email</option>
                        <option value="wait">Wait</option>
                        <option value="condition">Condition</option>
                        <option value="tag_add">Add Tag</option>
                        <option value="tag_remove">Remove Tag</option>
                    </select>
                </div>

                <!-- Send Email config -->
                <div id="config_send_email" class="step-config d-none">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Template</label>
                            <select id="cfg_template_id" class="form-select">
                                <option value="">— None —</option>
                                <?php foreach ($templates as $tpl): ?>
                                <option value="<?= (int)$tpl['id'] ?>">
                                    <?= htmlspecialchars($tpl['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Subject</label>
                            <input type="text" id="cfg_subject" class="form-control" placeholder="Email subject…">
                        </div>
                    </div>
                </div>

                <!-- Wait config -->
                <div id="config_wait" class="step-config d-none">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Days</label>
                            <input type="number" id="cfg_wait_days" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Hours</label>
                            <input type="number" id="cfg_wait_hours" class="form-control" min="0" max="23" value="0">
                        </div>
                    </div>
                </div>

                <!-- Condition config -->
                <div id="config_condition" class="step-config d-none">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Field</label>
                            <input type="text" id="cfg_condition_field" class="form-control" placeholder="e.g. email">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Operator</label>
                            <select id="cfg_condition_operator" class="form-select">
                                <option value="equals">Equals</option>
                                <option value="not_equals">Not Equals</option>
                                <option value="contains">Contains</option>
                                <option value="not_contains">Not Contains</option>
                                <option value="greater_than">Greater Than</option>
                                <option value="less_than">Less Than</option>
                            </select>
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label fw-semibold">Value</label>
                            <input type="text" id="cfg_condition_value" class="form-control" placeholder="Value…">
                        </div>
                    </div>
                </div>

                <!-- Tag add/remove config -->
                <div id="config_tag_add" class="step-config d-none">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tag Name</label>
                        <input type="text" id="cfg_tag_add_name" class="form-control" placeholder="Tag name…">
                    </div>
                </div>
                <div id="config_tag_remove" class="step-config d-none">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tag Name</label>
                        <input type="text" id="cfg_tag_remove_name" class="form-control" placeholder="Tag name…">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnConfirmAddStep">Add Step</button>
            </div>
        </div>
    </div>
</div>

<script>
function showStepConfig(type) {
    document.querySelectorAll('.step-config').forEach(el => el.classList.add('d-none'));
    if (type) {
        const el = document.getElementById('config_' + type);
        if (el) el.classList.remove('d-none');
    }
}

document.getElementById('btnConfirmAddStep')?.addEventListener('click', function () {
    const type = document.getElementById('newStepType').value;
    if (!type) { alert('Please select a step type.'); return; }

    let config = {};
    if (type === 'send_email') {
        config = {
            template_id: document.getElementById('cfg_template_id').value,
            subject:     document.getElementById('cfg_subject').value,
        };
    } else if (type === 'wait') {
        config = {
            days:  document.getElementById('cfg_wait_days').value,
            hours: document.getElementById('cfg_wait_hours').value,
        };
    } else if (type === 'condition') {
        config = {
            field:    document.getElementById('cfg_condition_field').value,
            operator: document.getElementById('cfg_condition_operator').value,
            value:    document.getElementById('cfg_condition_value').value,
        };
    } else if (type === 'tag_add') {
        config = { tag: document.getElementById('cfg_tag_add_name').value };
    } else if (type === 'tag_remove') {
        config = { tag: document.getElementById('cfg_tag_remove_name').value };
    }

    const tbody = document.querySelector('#stepsTable tbody');
    let table   = document.getElementById('stepsTable');

    // Build table if it was not rendered (no existing steps)
    if (!table) {
        const card = document.querySelector('.card .card-body.p-0');
        card.innerHTML = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" id="stepsTable"><thead class="table-light"><tr><th style="width:50px">#</th><th>Type</th><th>Configuration</th><th class="text-end">Actions</th></tr></thead><tbody></tbody></table></div>';
        table = document.getElementById('stepsTable');
    }

    const rows  = document.querySelectorAll('#stepsTable tbody tr');
    const idx   = rows.length;
    const order = idx + 1;

    const stepTypeLabels = {
        send_email: 'Send Email', wait: 'Wait', condition: 'Condition',
        tag_add: 'Add Tag', tag_remove: 'Remove Tag',
    };
    const stepColors = {
        send_email: 'primary', wait: 'warning', condition: 'info',
        tag_add: 'success', tag_remove: 'secondary',
    };

    const summaryParts = Object.entries(config).slice(0, 3).map(([k, v]) => `${k}: ${v}`).join(', ');
    const row = document.createElement('tr');
    row.dataset.stepIndex = idx;
    row.innerHTML = `
        <td class="text-muted small">${order}</td>
        <td><span class="badge bg-${stepColors[type] || 'secondary'}">${stepTypeLabels[type] || type}</span></td>
        <td class="text-muted small">${summaryParts || '—'}
            <input type="hidden" name="steps[${idx}][type]"   value="${type}">
            <input type="hidden" name="steps[${idx}][order]"  value="${order}">
            <input type="hidden" name="steps[${idx}][config]" value="${JSON.stringify(config).replace(/"/g, '&quot;')}">
        </td>
        <td class="text-end">
            <div class="btn-group btn-group-sm">
                ${idx > 0 ? `<button type="button" class="btn btn-outline-secondary btn-move-up" data-index="${idx}"><i class="bi bi-arrow-up"></i></button>` : ''}
                <button type="button" class="btn btn-outline-danger btn-delete-step" data-index="${idx}"><i class="bi bi-trash"></i></button>
            </div>
        </td>`;
    document.querySelector('#stepsTable tbody').appendChild(row);

    bootstrap.Modal.getInstance(document.getElementById('addStepModal'))?.hide();
    document.getElementById('newStepType').value = '';
    document.querySelectorAll('.step-config').forEach(el => el.classList.add('d-none'));
});

document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-delete-step')) {
        e.target.closest('tr').remove();
        reindexSteps();
    }
    if (e.target.closest('.btn-move-up')) {
        const row  = e.target.closest('tr');
        const prev = row.previousElementSibling;
        if (prev) { row.parentNode.insertBefore(row, prev); reindexSteps(); }
    }
    if (e.target.closest('.btn-move-down')) {
        const row  = e.target.closest('tr');
        const next = row.nextElementSibling;
        if (next) { row.parentNode.insertBefore(next, row); reindexSteps(); }
    }
});

function reindexSteps() {
    document.querySelectorAll('#stepsTable tbody tr').forEach(function (row, i) {
        row.dataset.stepIndex = i;
        row.querySelector('td:first-child').textContent = i + 1;
        row.querySelectorAll('input[type=hidden]').forEach(function (inp) {
            inp.name = inp.name.replace(/steps\[\d+\]/, 'steps[' + i + ']');
        });
        const inp = row.querySelector('input[name$="[order]"]');
        if (inp) inp.value = i + 1;
        const moveUp   = row.querySelector('.btn-move-up');
        const moveDown = row.querySelector('.btn-move-down');
        const tbody    = document.querySelector('#stepsTable tbody');
        if (moveUp)   { if (i === 0) moveUp.remove(); else { moveUp.dataset.index   = i; } }
        if (moveDown) { if (i === tbody.children.length - 1) moveDown.remove(); else { moveDown.dataset.index = i; } }
    });
}
</script>

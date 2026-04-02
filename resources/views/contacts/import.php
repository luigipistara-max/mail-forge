<?php
/** @var array $errors */
/** @var int $step */
/** @var array $csvPreview */
/** @var array $csvHeaders */
/** @var array $availableFields */
$errors          = $errors          ?? [];
$step            = (int)($step      ?? 1);
$csvPreview      = $csvPreview      ?? [];
$csvHeaders      = $csvHeaders      ?? [];
$availableFields = $availableFields ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0">Import Contacts</h6>
    <a href="/contacts" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<!-- Step Indicator -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-center gap-0">
            <!-- Step 1 -->
            <div class="d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold"
                    style="width:2rem;height:2rem;background-color:<?= $step >= 1 ? '#0d6efd' : '#dee2e6' ?>;color:<?= $step >= 1 ? '#fff' : '#6c757d' ?>">
                    <?php if ($step > 1): ?>
                    <i class="bi bi-check-lg" style="font-size:.9rem"></i>
                    <?php else: ?>1<?php endif; ?>
                </div>
                <span class="ms-2 small fw-semibold <?= $step === 1 ? 'text-primary' : 'text-muted' ?>">Upload File</span>
            </div>
            <div class="flex-grow-1 border-top mx-3" style="max-width:6rem"></div>
            <!-- Step 2 -->
            <div class="d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold"
                    style="width:2rem;height:2rem;background-color:<?= $step >= 2 ? '#0d6efd' : '#dee2e6' ?>;color:<?= $step >= 2 ? '#fff' : '#6c757d' ?>">
                    2
                </div>
                <span class="ms-2 small fw-semibold <?= $step === 2 ? 'text-primary' : 'text-muted' ?>">Map Fields &amp; Confirm</span>
            </div>
        </div>
    </div>
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

<?php if ($step === 1): ?>
<!-- ==================== STEP 1: Upload ==================== -->
<form method="POST" action="/contacts/import" enctype="multipart/form-data">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>
    <input type="hidden" name="step" value="1">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Upload CSV File</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
                        <input type="file" id="csv_file" name="csv_file"
                            class="form-control <?= isset($errors['csv_file']) ? 'is-invalid' : '' ?>"
                            accept=".csv,text/csv" required>
                        <?php if (isset($errors['csv_file'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['csv_file'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <div class="form-text">Accepted format: <code>.csv</code>. First row must be column headers.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Import Options</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="duplicate_handling" class="form-label fw-semibold">Duplicate Handling</label>
                        <select id="duplicate_handling" name="duplicate_handling" class="form-select">
                            <option value="skip">Skip duplicates</option>
                            <option value="update">Update existing contact</option>
                            <option value="create_new">Create new record</option>
                        </select>
                        <div class="form-text">What to do when a contact with the same email already exists.</div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="double_opt_in" name="double_opt_in" value="1">
                        <label class="form-check-label fw-semibold" for="double_opt_in">Double Opt-in</label>
                        <div class="form-text">Send confirmation email to each imported contact before activating.</div>
                    </div>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-upload me-1"></i>Upload &amp; Preview
                </button>
            </div>
        </div>
    </div>
</form>

<?php elseif ($step === 2): ?>
<!-- ==================== STEP 2: Map Fields & Confirm ==================== -->
<form method="POST" action="/contacts/import/confirm">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>
    <input type="hidden" name="step" value="2">

    <!-- Preview Table -->
    <?php if (!empty($csvPreview)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">
            CSV Preview <span class="badge bg-light text-muted border ms-1">First <?= count($csvPreview) ?> rows</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($csvHeaders as $header): ?>
                            <th><?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($csvPreview, 0, 5) as $row): ?>
                        <tr>
                            <?php foreach ($csvHeaders as $header): ?>
                            <td><?= htmlspecialchars($row[$header] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Field Mapping -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-semibold">Map CSV Columns to Fields</div>
        <div class="card-body">
            <?php if (empty($csvHeaders)): ?>
            <p class="text-muted mb-0">No columns detected in the uploaded file.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40%">CSV Column</th>
                            <th>Map to Field</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($csvHeaders as $index => $header): ?>
                        <?php
                        // Auto-detect common field mappings
                        $autoMap = '';
                        $headerLower = strtolower(trim($header));
                        $commonMappings = [
                            'email'      => 'email',
                            'e-mail'     => 'email',
                            'first_name' => 'first_name',
                            'firstname'  => 'first_name',
                            'first name' => 'first_name',
                            'last_name'  => 'last_name',
                            'lastname'   => 'last_name',
                            'last name'  => 'last_name',
                            'phone'      => 'phone',
                            'telephone'  => 'phone',
                            'company'    => 'company',
                            'country'    => 'country',
                        ];
                        $autoMap = $commonMappings[$headerLower] ?? '';
                        ?>
                        <tr>
                            <td>
                                <code><?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?></code>
                                <?php if (!empty($csvPreview[0][$header])): ?>
                                <div class="text-muted small"><?= htmlspecialchars(mb_strimwidth($csvPreview[0][$header] ?? '', 0, 40, '…'), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select name="mapping[<?= htmlspecialchars($header, ENT_QUOTES, 'UTF-8') ?>]" class="form-select form-select-sm">
                                    <option value="">— Skip this column —</option>
                                    <?php foreach ($availableFields as $fieldKey => $fieldLabel): ?>
                                    <option value="<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $autoMap === $fieldKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="/contacts/import" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Start Over
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Confirm Import
        </button>
    </div>
</form>
<?php endif; ?>

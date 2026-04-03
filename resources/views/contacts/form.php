<?php
/** @var array|null $contact */
/** @var array $errors */
/** @var array $tags */
/** @var array $customFields */
$contact      = $contact      ?? null;
$errors       = $errors       ?? [];
$tags         = $tags         ?? [];
$customFields = $customFields ?? [];
$isEdit = !empty($contact['id']);
$e = fn(string $key): string => htmlspecialchars($contact[$key] ?? '', ENT_QUOTES, 'UTF-8');

$currentTags = '';
if (!empty($contact['tags'])) {
    $currentTags = is_array($contact['tags'])
        ? implode(', ', $contact['tags'])
        : htmlspecialchars($contact['tags'], ENT_QUOTES, 'UTF-8');
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0"><?= $isEdit ? 'Edit Contact' : 'Create Contact' ?></h6>
    <a href="/contacts" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
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

<form method="POST" action="<?= $isEdit ? '/contacts/' . (int)$contact['id'] . '/update' : '/contacts' ?>">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <!-- Contact Details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Contact Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email"
                            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            value="<?= $e('email') ?>" required autocomplete="email">
                        <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="first_name" class="form-label fw-semibold">First Name</label>
                            <input type="text" id="first_name" name="first_name"
                                class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('first_name') ?>">
                            <?php if (isset($errors['first_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['first_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="last_name" class="form-label fw-semibold">Last Name</label>
                            <input type="text" id="last_name" name="last_name"
                                class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('last_name') ?>">
                            <?php if (isset($errors['last_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['last_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="phone" class="form-label fw-semibold">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?= $e('phone') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="company" class="form-label fw-semibold">Company</label>
                            <input type="text" id="company" name="company" class="form-control" value="<?= $e('company') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="country" class="form-label fw-semibold">Country</label>
                            <input type="text" id="country" name="country" class="form-control" value="<?= $e('country') ?>" placeholder="e.g. United States">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Fields -->
            <?php if (!empty($customFields)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Custom Fields</div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($customFields as $field): ?>
                        <?php
                        $fieldKey   = htmlspecialchars($field['key'] ?? $field['slug'] ?? '', ENT_QUOTES, 'UTF-8');
                        $fieldLabel = htmlspecialchars($field['label'] ?? $field['name'] ?? $fieldKey, ENT_QUOTES, 'UTF-8');
                        $fieldType  = $field['type'] ?? 'text';
                        $fieldValue = htmlspecialchars($contact['custom_fields'][$field['key'] ?? $field['slug'] ?? ''] ?? '', ENT_QUOTES, 'UTF-8');
                        $fieldId    = 'cf_' . $fieldKey;
                        $inputName  = 'custom_fields[' . $fieldKey . ']';
                        ?>
                        <div class="col-sm-6">
                            <label for="<?= $fieldId ?>" class="form-label fw-semibold">
                                <?= $fieldLabel ?>
                                <?php if (!empty($field['required'])): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>
                            <?php if ($fieldType === 'textarea'): ?>
                            <textarea id="<?= $fieldId ?>" name="<?= $inputName ?>" class="form-control" rows="3"
                                <?= !empty($field['required']) ? 'required' : '' ?>><?= $fieldValue ?></textarea>
                            <?php elseif ($fieldType === 'select' && !empty($field['options'])): ?>
                            <select id="<?= $fieldId ?>" name="<?= $inputName ?>" class="form-select"
                                <?= !empty($field['required']) ? 'required' : '' ?>>
                                <option value="">— Select —</option>
                                <?php foreach ((array)$field['options'] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $fieldValue === htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php elseif ($fieldType === 'checkbox'): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="<?= $fieldId ?>"
                                    name="<?= $inputName ?>" value="1"
                                    <?= !empty($fieldValue) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= $fieldId ?>">Yes</label>
                            </div>
                            <?php elseif ($fieldType === 'date'): ?>
                            <input type="date" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                                class="form-control" value="<?= $fieldValue ?>"
                                <?= !empty($field['required']) ? 'required' : '' ?>>
                            <?php elseif ($fieldType === 'number'): ?>
                            <input type="number" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                                class="form-control" value="<?= $fieldValue ?>"
                                <?= !empty($field['required']) ? 'required' : '' ?>>
                            <?php else: ?>
                            <input type="text" id="<?= $fieldId ?>" name="<?= $inputName ?>"
                                class="form-control" value="<?= $fieldValue ?>"
                                <?= !empty($field['required']) ? 'required' : '' ?>>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="col-lg-4">

            <!-- Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Status</div>
                <div class="card-body">
                    <label for="status" class="form-label fw-semibold">Subscription Status</label>
                    <select id="status" name="status" class="form-select">
                        <?php
                        $statuses = ['subscribed', 'unsubscribed', 'pending', 'bounced', 'complained'];
                        $currentStatus = $contact['status'] ?? 'subscribed';
                        foreach ($statuses as $s):
                        ?>
                        <option value="<?= $s ?>" <?= $currentStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Controls whether this contact receives emails.</div>
                </div>
            </div>

            <!-- Tags -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Tags</div>
                <div class="card-body">
                    <label for="tags" class="form-label fw-semibold">Tags</label>
                    <input type="text" id="tags" name="tags" class="form-control"
                        value="<?= htmlspecialchars($currentTags, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="tag1, tag2, tag3">
                    <div class="form-text">Separate tags with commas.</div>
                    <?php if (!empty($tags)): ?>
                    <div class="mt-2">
                        <small class="text-muted fw-semibold d-block mb-1">Available tags:</small>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($tags as $tag): ?>
                            <?php $tagName = htmlspecialchars(is_array($tag) ? ($tag['name'] ?? '') : $tag, ENT_QUOTES, 'UTF-8'); ?>
                            <button type="button" class="badge bg-secondary border-0 tag-suggestion"
                                data-tag="<?= $tagName ?>"
                                style="cursor:pointer"><?= $tagName ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Contact' ?>
                </button>
                <a href="/contacts" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
document.querySelectorAll('.tag-suggestion').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = document.getElementById('tags');
        const tag = this.dataset.tag;
        const current = input.value.split(',').map(t => t.trim()).filter(Boolean);
        if (!current.includes(tag)) {
            current.push(tag);
            input.value = current.join(', ');
        }
    });
});
</script>

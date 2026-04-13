<?php
/** @var array|null $template */
/** @var array $errors */
$template = $template ?? null;
$errors   = $errors   ?? [];
$isEdit = !empty($template['id']);
$e = fn(string $key): string => htmlspecialchars($template[$key] ?? '', ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0"><?= $isEdit ? 'Edit Template' : 'Create Template' ?></h6>
    <a href="<?= BASE_PATH ?>/templates" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
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

<form method="POST" action="<?= $isEdit ? '/templates/' . (int)$template['id'] . '/update' : '/templates' ?>">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Template Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-8">
                            <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('name') ?>" required>
                            <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-4">
                            <label for="category" class="form-label fw-semibold">Category</label>
                            <select id="category" name="category" class="form-select">
                                <option value="">— Select —</option>
                                <?php foreach (['newsletter' => 'Newsletter', 'promotional' => 'Promotional', 'transactional' => 'Transactional', 'automated' => 'Automated'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($template['category'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-8">
                            <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                            <input type="text" id="subject" name="subject"
                                class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('subject') ?>" required>
                            <?php if (isset($errors['subject'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['subject'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-8">
                            <label for="preheader" class="form-label fw-semibold">Preheader</label>
                            <input type="text" id="preheader" name="preheader" class="form-control"
                                value="<?= $e('preheader') ?>" placeholder="Short preview text shown in inbox…">
                            <div class="form-text">Displayed after the subject line in most email clients.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">HTML Content</div>
                <div class="card-body">
                    <label for="body_html" class="form-label fw-semibold">HTML Body</label>
                    <textarea id="body_html" name="body_html"
                        class="form-control font-monospace <?= isset($errors['body_html']) ? 'is-invalid' : '' ?>"
                        rows="20" placeholder="<!DOCTYPE html>…" spellcheck="false"><?= $e('body_html') ?></textarea>
                    <?php if (isset($errors['body_html'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['body_html'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Plain Text Content</div>
                <div class="card-body">
                    <label for="body_text" class="form-label fw-semibold">Plain Text Body</label>
                    <textarea id="body_text" name="body_text" class="form-control font-monospace"
                        rows="10" placeholder="Plain text version of the email…" spellcheck="false"><?= $e('body_text') ?></textarea>
                    <div class="form-text">Used as a fallback for email clients that cannot display HTML.</div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">

            <!-- Merge Tags -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">
                    <button class="btn btn-link p-0 fw-semibold text-decoration-none text-dark w-100 text-start d-flex align-items-center justify-content-between"
                        type="button" data-bs-toggle="collapse" data-bs-target="#mergeTagsPanel">
                        <span><i class="bi bi-braces me-2 text-muted"></i>Merge Tags</span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                </div>
                <div class="collapse show" id="mergeTagsPanel">
                    <div class="card-body pt-2">
                        <p class="text-muted small mb-2">Click to copy a tag into your HTML:</p>
                        <?php
                        $mergeTags = [
                            '{{first_name}}'         => 'First Name',
                            '{{last_name}}'          => 'Last Name',
                            '{{email}}'              => 'Email Address',
                            '{{unsubscribe_url}}'    => 'Unsubscribe URL',
                            '{{view_in_browser_url}}' => 'View in Browser URL',
                        ];
                        foreach ($mergeTags as $tag => $desc):
                        ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-1 w-100 text-start merge-tag-btn"
                            data-tag="<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>">
                            <code class="me-2"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></code>
                            <span class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <?php endforeach; ?>
                        <div id="copyMsg" class="text-success small mt-1 d-none"><i class="bi bi-check-lg me-1"></i>Copied!</div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <?php if ($isEdit): ?>
                <a href="<?= BASE_PATH ?>/templates/<?= (int)$template['id'] ?>" class="btn btn-outline-secondary" target="_blank">
                    <i class="bi bi-eye me-1"></i>Preview
                </a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Template' ?>
                </button>
                <a href="<?= BASE_PATH ?>/templates" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
document.querySelectorAll('.merge-tag-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const tag = btn.dataset.tag;
        navigator.clipboard.writeText(tag).then(function () {
            const msg = document.getElementById('copyMsg');
            msg.classList.remove('d-none');
            setTimeout(function () { msg.classList.add('d-none'); }, 2000);
        });
    });
});
</script>

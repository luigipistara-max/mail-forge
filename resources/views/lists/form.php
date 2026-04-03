<?php
/** @var array|null $list */
/** @var array $errors */
$list   = $list   ?? null;
$errors = $errors ?? [];
$isEdit = !empty($list['id']);
$e = fn(string $key): string => htmlspecialchars($list[$key] ?? '', ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0"><?= $isEdit ? 'Edit List' : 'Create List' ?></h6>
    <a href="/lists" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
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

<form method="POST" action="<?= $isEdit ? '/lists/' . (int)$list['id'] . '/update' : '/lists' ?>">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">List Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                            value="<?= $e('name') ?>" required>
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"><?= $e('description') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Sender Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="from_name" class="form-label fw-semibold">From Name</label>
                            <input type="text" id="from_name" name="from_name" class="form-control" value="<?= $e('from_name') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="from_email" class="form-label fw-semibold">From Email</label>
                            <input type="email" id="from_email" name="from_email" class="form-control <?= isset($errors['from_email']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('from_email') ?>">
                            <?php if (isset($errors['from_email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['from_email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="reply_to" class="form-label fw-semibold">Reply-To</label>
                            <input type="email" id="reply_to" name="reply_to" class="form-control" value="<?= $e('reply_to') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Options</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                            <?= !empty($list['is_public']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">Public List</label>
                        <div class="form-text">Visible on the public subscribe page</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="double_optin" name="double_optin" value="1"
                            <?= !empty($list['double_optin']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="double_optin">Double Opt-in</label>
                        <div class="form-text">Send confirmation email before subscribing</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="subscribe_page_enabled" name="subscribe_page_enabled" value="1"
                            <?= !empty($list['subscribe_page_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="subscribe_page_enabled">Subscribe Page Enabled</label>
                        <div class="form-text">Enable public subscribe form for this list</div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Save Changes' : 'Create List' ?>
                </button>
                <a href="/lists" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

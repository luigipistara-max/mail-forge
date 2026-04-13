<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preferences</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; }
    </style>
</head>
<body>
<?php
/** @var array $contact */
/** @var array $lists */
/** @var string $token */
/** @var bool $success */
$contact = $contact ?? [];
$lists   = $lists   ?? [];
$token   = $token   ?? '';
$success = $success ?? false;
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="w-100" style="max-width: 560px">

        <div class="text-center mb-4">
            <h5 class="fw-bold mb-1"><i class="bi bi-sliders2 me-2 text-primary"></i>Email Preferences</h5>
            <?php if (!empty($contact['email'])): ?>
            <p class="text-muted small mb-0">Managing preferences for <strong><?= htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span>Your email preferences have been saved successfully.</span>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold">Mailing Lists</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_PATH ?>/preferences" id="preferencesForm">
                    <?= \MailForge\Helpers\CsrfHelper::field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <?php if (empty($lists)): ?>
                    <p class="text-muted small mb-0">No mailing lists available.</p>
                    <?php else: ?>
                    <p class="text-muted small mb-3">Check the lists you'd like to stay subscribed to:</p>
                    <div class="list-group list-group-flush">
                        <?php foreach ($lists as $list): ?>
                        <?php
                        $listId         = (int)($list['id'] ?? 0);
                        $listName       = htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $listDesc       = htmlspecialchars($list['description'] ?? '', ENT_QUOTES, 'UTF-8');
                        $isSubscribed   = !empty($list['pivot']['status'])
                            ? $list['pivot']['status'] === 'subscribed'
                            : !empty($list['subscribed']);
                        $subscribersCount = (int)($list['subscribers_count'] ?? 0);
                        ?>
                        <div class="list-group-item px-0 py-3">
                            <div class="form-check d-flex align-items-start gap-3">
                                <input class="form-check-input mt-1 flex-shrink-0" type="checkbox"
                                    name="lists[]" value="<?= $listId ?>"
                                    id="list_<?= $listId ?>"
                                    <?= $isSubscribed ? 'checked' : '' ?>>
                                <label class="form-check-label" for="list_<?= $listId ?>">
                                    <span class="fw-semibold d-block"><?= $listName ?></span>
                                    <?php if ($listDesc): ?>
                                    <span class="text-muted small"><?= $listDesc ?></span>
                                    <?php endif; ?>
                                    <?php if ($subscribersCount > 0): ?>
                                    <span class="text-muted small d-block">
                                        <i class="bi bi-people me-1"></i><?= number_format($subscribersCount) ?> subscribers
                                    </span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary fw-semibold">
                            <i class="bi bi-check-lg me-1"></i>Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Unsubscribe from all -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold mb-1">Unsubscribe from all</h6>
                <p class="text-muted small mb-3">Remove yourself from all mailing lists at once. You will stop receiving all emails from us.</p>
                <form method="POST" action="<?= BASE_PATH ?>/unsubscribe-all"
                    onsubmit="return confirm('Are you sure you want to unsubscribe from all lists?')">
                    <?= \MailForge\Helpers\CsrfHelper::field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-envelope-x me-1"></i>Unsubscribe from All
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

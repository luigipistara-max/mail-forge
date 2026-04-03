<?php
/** @var array|null $server */
/** @var array      $errors */
$server = $server ?? null;
$errors = $errors ?? [];

$isEdit = !empty($server['id']);
$e = fn(string $key): string => htmlspecialchars($server[$key] ?? '', ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold"><?= $isEdit ? 'Edit SMTP Server' : 'Add SMTP Server' ?></h1>
    <a href="/smtp-servers" class="btn btn-outline-secondary btn-sm">
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

<form method="POST" id="smtpForm"
    action="<?= $isEdit ? '/smtp-servers/' . (int)$server['id'] : '/smtp-servers' ?>">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <!-- Server details -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Server Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                            class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                            value="<?= $e('name') ?>" required placeholder="My SMTP Server">
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-8">
                            <label for="host" class="form-label fw-semibold">Host <span class="text-danger">*</span></label>
                            <input type="text" id="host" name="host"
                                class="form-control <?= isset($errors['host']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('host') ?>" required placeholder="smtp.example.com">
                            <?php if (isset($errors['host'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['host'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-4">
                            <label for="port" class="form-label fw-semibold">Port <span class="text-danger">*</span></label>
                            <input type="number" id="port" name="port" min="1" max="65535"
                                class="form-control <?= isset($errors['port']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars((string)($server['port'] ?? 587), ENT_QUOTES, 'UTF-8') ?>" required>
                            <?php if (isset($errors['port'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['port'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authentication -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Authentication</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="username" class="form-label fw-semibold">Username</label>
                            <input type="text" id="username" name="username"
                                class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('username') ?>" autocomplete="username">
                            <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['username'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <input type="password" id="password" name="password"
                                    class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                    autocomplete="new-password"
                                    <?= $isEdit ? 'placeholder="Leave blank to keep current"' : '' ?>>
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="encryption" class="form-label fw-semibold">Encryption</label>
                            <select id="encryption" name="encryption"
                                class="form-select <?= isset($errors['encryption']) ? 'is-invalid' : '' ?>">
                                <option value="tls" <?= ($server['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($server['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($server['encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                            <?php if (isset($errors['encryption'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['encryption'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sender info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Sender Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="from_email" class="form-label fw-semibold">From Email <span class="text-danger">*</span></label>
                            <input type="email" id="from_email" name="from_email"
                                class="form-control <?= isset($errors['from_email']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('from_email') ?>" required placeholder="noreply@example.com">
                            <?php if (isset($errors['from_email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['from_email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="from_name" class="form-label fw-semibold">From Name</label>
                            <input type="text" id="from_name" name="from_name"
                                class="form-control <?= isset($errors['from_name']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('from_name') ?>" placeholder="My Company">
                            <?php if (isset($errors['from_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['from_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate limits -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Rate Limits &amp; Priority</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <label for="max_per_hour" class="form-label fw-semibold">Max Per Hour</label>
                            <input type="number" id="max_per_hour" name="max_per_hour" min="0"
                                class="form-control <?= isset($errors['max_per_hour']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars((string)($server['max_per_hour'] ?? 500), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">0 = unlimited</div>
                            <?php if (isset($errors['max_per_hour'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['max_per_hour'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-4">
                            <label for="max_per_day" class="form-label fw-semibold">Max Per Day</label>
                            <input type="number" id="max_per_day" name="max_per_day" min="0"
                                class="form-control <?= isset($errors['max_per_day']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars((string)($server['max_per_day'] ?? 5000), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">0 = unlimited</div>
                            <?php if (isset($errors['max_per_day'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['max_per_day'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-4">
                            <label for="priority" class="form-label fw-semibold">Priority (1–10)</label>
                            <input type="number" id="priority" name="priority" min="1" max="10"
                                class="form-control <?= isset($errors['priority']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars((string)($server['priority'] ?? 5), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">Lower = higher priority</div>
                            <?php if (isset($errors['priority'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['priority'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /col-lg-8 -->

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                            <?= !empty($server['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="is_active">Active</label>
                        <div class="form-text">Enable this server for sending.</div>
                    </div>
                    <hr>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Save Changes' : 'Add Server' ?>
                        </button>
                        <button type="button" class="btn btn-outline-info" id="btnTestNew">
                            <i class="bi bi-plug me-1"></i>Test Connection
                        </button>
                        <a href="/smtp-servers" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                    <div id="testResult" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</form>

<script>
document.getElementById('togglePassword')?.addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon  = document.getElementById('togglePasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
});

document.getElementById('btnTestNew')?.addEventListener('click', function () {
    const btn    = this;
    const result = document.getElementById('testResult');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing…';
    result.className = 'mt-3 d-none';

    const formData = new FormData(document.getElementById('smtpForm'));
    const data = {};
    formData.forEach((v, k) => { data[k] = v; });

    fetch('/smtp-servers/test-new', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(function (res) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plug me-1"></i>Test Connection';
        result.className = 'mt-3 alert ' + (res.success ? 'alert-success' : 'alert-danger');
        result.textContent = res.message || (res.success ? 'Connection successful!' : 'Connection failed.');
    })
    .catch(function () {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plug me-1"></i>Test Connection';
        result.className = 'mt-3 alert alert-danger';
        result.textContent = 'Request failed.';
    });
});
</script>

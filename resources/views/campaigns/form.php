<?php
/** @var array|null $campaign */
/** @var array $errors */
/** @var array $templates */
/** @var array $lists */
/** @var array $segments */
/** @var array $smtpServers */
$campaign    = $campaign    ?? null;
$errors      = $errors      ?? [];
$templates   = $templates   ?? [];
$lists       = $lists       ?? [];
$segments    = $segments    ?? [];
$smtpServers = $smtpServers ?? [];
$isEdit = !empty($campaign['id']);
$e = fn(string $key): string => htmlspecialchars($campaign[$key] ?? '', ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0"><?= $isEdit ? 'Edit Campaign' : 'Create Campaign' ?></h6>
    <a href="<?= BASE_PATH ?>/campaigns" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
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

<form method="POST" action="<?= $isEdit ? '/campaigns/' . (int)$campaign['id'] . '/update' : '/campaigns' ?>" id="campaignForm">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>
    <input type="hidden" name="action" id="formAction" value="draft">

    <div class="row g-4">

        <!-- Left Main Column -->
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Campaign Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label fw-semibold">Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('name') ?>" required>
                            <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                            <input type="text" id="subject" name="subject"
                                class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('subject') ?>" required>
                            <?php if (isset($errors['subject'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['subject'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label for="preheader" class="form-label fw-semibold">Preheader</label>
                            <input type="text" id="preheader" name="preheader" class="form-control"
                                value="<?= $e('preheader') ?>" placeholder="Short preview text shown in inbox…">
                        </div>
                        <div class="col-sm-6">
                            <label for="from_name" class="form-label fw-semibold">From Name</label>
                            <input type="text" id="from_name" name="from_name" class="form-control"
                                value="<?= $e('from_name') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="from_email" class="form-label fw-semibold">From Email</label>
                            <input type="email" id="from_email" name="from_email"
                                class="form-control <?= isset($errors['from_email']) ? 'is-invalid' : '' ?>"
                                value="<?= $e('from_email') ?>">
                            <?php if (isset($errors['from_email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['from_email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="reply_to" class="form-label fw-semibold">Reply-To</label>
                            <input type="email" id="reply_to" name="reply_to" class="form-control"
                                value="<?= $e('reply_to') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Content</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Content Source</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="use_template" id="use_template_yes"
                                    value="1" <?= !empty($campaign['template_id']) ? 'checked' : '' ?>
                                    onchange="toggleContent()">
                                <label class="form-check-label" for="use_template_yes">Use a Template</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="use_template" id="use_template_no"
                                    value="0" <?= empty($campaign['template_id']) ? 'checked' : '' ?>
                                    onchange="toggleContent()">
                                <label class="form-check-label" for="use_template_no">Custom HTML</label>
                            </div>
                        </div>
                    </div>

                    <div id="templateSection" class="mb-3 <?= empty($campaign['template_id']) ? 'd-none' : '' ?>">
                        <label for="template_id" class="form-label fw-semibold">Template</label>
                        <select id="template_id" name="template_id" class="form-select">
                            <option value="">— Select Template —</option>
                            <?php foreach ($templates as $tpl): ?>
                            <option value="<?= (int)$tpl['id'] ?>" <?= ((int)($campaign['template_id'] ?? 0)) === (int)$tpl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tpl['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="customHtmlSection" class="<?= !empty($campaign['template_id']) ? 'd-none' : '' ?>">
                        <div class="mb-3">
                            <label for="body_html" class="form-label fw-semibold">HTML Body</label>
                            <textarea id="body_html" name="body_html"
                                class="form-control font-monospace" rows="15"
                                placeholder="<!DOCTYPE html>…" spellcheck="false"><?= $e('body_html') ?></textarea>
                        </div>
                        <div class="mb-0">
                            <label for="body_text" class="form-label fw-semibold">Plain Text Body</label>
                            <textarea id="body_text" name="body_text"
                                class="form-control font-monospace" rows="8"
                                placeholder="Plain text version…" spellcheck="false"><?= $e('body_text') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Audience</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="list_id" class="form-label fw-semibold">Mailing List <span class="text-danger">*</span></label>
                        <select id="list_id" name="list_id"
                            class="form-select <?= isset($errors['list_id']) ? 'is-invalid' : '' ?>">
                            <option value="">— Select List —</option>
                            <?php foreach ($lists as $list): ?>
                            <option value="<?= (int)$list['id'] ?>" <?= ((int)($campaign['list_id'] ?? 0)) === (int)$list['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($list['subscribers_count'])): ?>
                                (<?= number_format((int)$list['subscribers_count']) ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['list_id'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['list_id'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($segments)): ?>
                    <div class="mb-0">
                        <label for="segment_id" class="form-label fw-semibold">Segment <span class="text-muted fw-normal">(optional)</span></label>
                        <select id="segment_id" name="segment_id" class="form-select">
                            <option value="">All Subscribers</option>
                            <?php foreach ($segments as $seg): ?>
                            <option value="<?= (int)$seg['id'] ?>" <?= ((int)($campaign['segment_id'] ?? 0)) === (int)$seg['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($seg['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Optionally restrict sending to contacts matching a segment.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">SMTP Server</div>
                <div class="card-body">
                    <label for="smtp_server_id" class="form-label fw-semibold">Server <span class="text-danger">*</span></label>
                    <select id="smtp_server_id" name="smtp_server_id"
                        class="form-select <?= isset($errors['smtp_server_id']) ? 'is-invalid' : '' ?>">
                        <option value="">— Select SMTP —</option>
                        <?php foreach ($smtpServers as $smtp): ?>
                        <option value="<?= (int)$smtp['id'] ?>" <?= ((int)($campaign['smtp_server_id'] ?? 0)) === (int)$smtp['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($smtp['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($smtp['host'])): ?>
                            — <?= htmlspecialchars($smtp['host'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['smtp_server_id'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['smtp_server_id'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Tracking</div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="track_opens" name="track_opens" value="1"
                            <?= !empty($campaign['track_opens']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="track_opens">Track Opens</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="track_clicks" name="track_clicks" value="1"
                            <?= !empty($campaign['track_clicks']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="track_clicks">Track Clicks</label>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Scheduling</div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_timing" id="send_now"
                                value="now" <?= ($campaign['scheduled_at'] ?? '') === '' ? 'checked' : '' ?>
                                onchange="toggleSchedule()">
                            <label class="form-check-label" for="send_now">Send Immediately</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_timing" id="send_scheduled"
                                value="scheduled" <?= !empty($campaign['scheduled_at']) ? 'checked' : '' ?>
                                onchange="toggleSchedule()">
                            <label class="form-check-label" for="send_scheduled">Schedule for Later</label>
                        </div>
                    </div>

                    <div id="scheduleFields" class="<?= empty($campaign['scheduled_at']) ? 'd-none' : '' ?>">
                        <div class="mb-3">
                            <label for="scheduled_at" class="form-label fw-semibold small">Scheduled Date &amp; Time</label>
                            <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                                class="form-control form-control-sm"
                                value="<?= htmlspecialchars(
                                    !empty($campaign['scheduled_at'])
                                        ? date('Y-m-d\TH:i', strtotime($campaign['scheduled_at']))
                                        : '',
                                    ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label for="batch_size" class="form-label fw-semibold small">Batch Size</label>
                            <input type="number" id="batch_size" name="batch_size" class="form-control form-control-sm"
                                value="<?= htmlspecialchars((string)($campaign['batch_size'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                min="1" placeholder="e.g. 500">
                        </div>
                        <div class="col-6">
                            <label for="batch_interval_minutes" class="form-label fw-semibold small">Interval (min)</label>
                            <input type="number" id="batch_interval_minutes" name="batch_interval_minutes"
                                class="form-control form-control-sm"
                                value="<?= htmlspecialchars((string)($campaign['batch_interval_minutes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                min="1" placeholder="e.g. 5">
                        </div>
                    </div>
                    <div class="form-text">Leave blank to send all at once.</div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" id="btnSchedule" data-action="schedule">
                    <i class="bi bi-send me-1"></i>Schedule / Queue
                </button>
                <button type="submit" class="btn btn-outline-secondary" id="btnDraft" data-action="draft">
                    <i class="bi bi-floppy me-1"></i>Save as Draft
                </button>
                <a href="<?= BASE_PATH ?>/campaigns" class="btn btn-link text-muted">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
function toggleContent() {
    const useTemplate = document.getElementById('use_template_yes').checked;
    document.getElementById('templateSection').classList.toggle('d-none', !useTemplate);
    document.getElementById('customHtmlSection').classList.toggle('d-none', useTemplate);
}

function toggleSchedule() {
    const scheduled = document.getElementById('send_scheduled').checked;
    document.getElementById('scheduleFields').classList.toggle('d-none', !scheduled);
}

(function () {
    const form = document.getElementById('campaignForm');
    let pendingAction = 'draft';

    document.getElementById('btnSchedule').addEventListener('click', function () {
        pendingAction = 'schedule';
    });
    document.getElementById('btnDraft').addEventListener('click', function () {
        pendingAction = 'draft';
    });

    form.addEventListener('submit', function () {
        document.getElementById('formAction').value = pendingAction;
    });
})();
</script>

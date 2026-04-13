<?php
/** @var array $campaign */
/** @var string $preview */
$campaign = $campaign ?? [];
$preview  = $preview  ?? '';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h6 class="fw-bold mb-0"><?= htmlspecialchars($campaign['name'] ?? 'Campaign Preview', ENT_QUOTES, 'UTF-8') ?></h6>
        <?php if (!empty($campaign['subject'])): ?>
        <div class="text-muted small mt-1">
            <i class="bi bi-envelope me-1"></i>Subject: <?= htmlspecialchars($campaign['subject'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <?php if (in_array($campaign['status'] ?? '', ['draft', 'scheduled'], true)): ?>
        <a href="<?= BASE_PATH ?>/campaigns/<?= (int)($campaign['id'] ?? 0) ?>/edit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>
        <a href="<?= BASE_PATH ?>/campaigns/<?= (int)($campaign['id'] ?? 0) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-transparent d-flex align-items-center gap-3 py-2">
        <span class="text-muted small"><i class="bi bi-eye me-1"></i>Email Preview</span>
        <?php if (!empty($campaign['from_name']) || !empty($campaign['from_email'])): ?>
        <span class="text-muted small">
            <i class="bi bi-person me-1"></i>
            <?= htmlspecialchars(
                trim(($campaign['from_name'] ?? '') . ' <' . ($campaign['from_email'] ?? '') . '>'),
                ENT_QUOTES, 'UTF-8'
            ) ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <iframe id="previewFrame" class="w-100 border-0 rounded-bottom"
            style="min-height:600px;" sandbox="allow-same-origin"></iframe>
    </div>
</div>

<script>
(function () {
    const frame = document.getElementById('previewFrame');
    const html  = <?= json_encode($preview) ?>;
    const doc   = frame.contentDocument || frame.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();
    frame.style.height = (doc.documentElement.scrollHeight + 30) + 'px';
    frame.addEventListener('load', function () {
        frame.style.height = (frame.contentDocument.documentElement.scrollHeight + 30) + 'px';
    });
})();
</script>

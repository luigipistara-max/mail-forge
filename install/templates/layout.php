<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mail Forge – Installer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f4f8; }

        .installer-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 2rem 1rem 4rem;
        }

        .installer-brand {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.75rem;
            text-decoration: none;
        }

        .installer-brand .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.35rem;
            box-shadow: 0 2px 8px rgba(13,110,253,.35);
        }

        .installer-brand .brand-text {
            font-size: 1.45rem;
            font-weight: 700;
            color: #1a2233;
            letter-spacing: -.3px;
        }

        .installer-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            width: 100%;
            max-width: 700px;
            overflow: hidden;
        }

        /* Progress header */
        .installer-progress {
            background: #f8f9ff;
            border-bottom: 1px solid #e9ecef;
            padding: 1.25rem 1.75rem;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: .6rem;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 14px;
            left: 0;
            right: 0;
            height: 2px;
            background: #dee2e6;
            z-index: 0;
        }

        .step-dot {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .3rem;
            z-index: 1;
            position: relative;
        }

        .step-dot .dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 700;
            background: #fff;
            border: 2px solid #dee2e6;
            color: #adb5bd;
            transition: all .2s;
        }

        .step-dot.done .dot   { background: #0d6efd; border-color: #0d6efd; color: #fff; }
        .step-dot.active .dot { background: #fff; border-color: #0d6efd; color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.15); }

        .step-dot .label {
            font-size: .6rem;
            color: #adb5bd;
            white-space: nowrap;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .step-dot.done .label,
        .step-dot.active .label { color: #0d6efd; }

        /* Progress bar */
        .installer-bar {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            overflow: hidden;
            margin-top: .5rem;
        }
        .installer-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #0d6efd, #6ea8fe);
            border-radius: 2px;
            transition: width .4s ease;
        }

        /* Content body */
        .installer-body {
            padding: 2rem 2rem;
        }

        /* Footer step label */
        .installer-footer {
            padding: .75rem 1.75rem;
            background: #f8f9ff;
            border-top: 1px solid #e9ecef;
            font-size: .8rem;
            color: #6c757d;
        }

        /* Step headings */
        .step-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }

        .check-item { display: flex; align-items: center; gap: .5rem; padding: .4rem 0; border-bottom: 1px solid #f0f4f8; }
        .check-item:last-child { border-bottom: 0; }

        .form-label { font-weight: 500; font-size: .9rem; }
        .btn-primary { font-weight: 600; }
    </style>
</head>
<body>
<div class="installer-wrapper">

    <!-- Brand -->
    <span class="installer-brand">
        <span class="brand-icon"><i class="bi bi-envelope-paper-fill"></i></span>
        <span class="brand-text">Mail Forge</span>
    </span>

    <div class="installer-card">

        <!-- Progress -->
        <?php
        $stepNames = ['Welcome','Requirements','Database','Site URL','SMTP','Platform','Admin','Install','Complete'];
        $pct       = round(($currentStep / TOTAL_STEPS) * 100);
        ?>
        <div class="installer-progress">
            <div class="progress-steps">
                <?php foreach ($stepNames as $i => $name):
                    $num   = $i + 1;
                    $cls   = $num < $currentStep ? 'done' : ($num === $currentStep ? 'active' : '');
                    $dot   = $num < $currentStep ? '<i class="bi bi-check-lg" style="font-size:.8rem"></i>' : $num;
                ?>
                <div class="step-dot <?= $cls ?>">
                    <div class="dot"><?= $dot ?></div>
                    <div class="label"><?= $name ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="installer-bar">
                <div class="installer-bar-fill" style="width:<?= $pct ?>%"></div>
            </div>
        </div>

        <!-- Step content -->
        <div class="installer-body">
            <?= $content ?>
        </div>

        <!-- Footer -->
        <div class="installer-footer">
            Step <?= $currentStep ?> of <?= TOTAL_STEPS ?> &mdash; <?= $stepNames[$currentStep - 1] ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

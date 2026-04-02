<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\Setting;
use MailForge\Validators\Validator;

class SettingsController extends Controller
{
    private Setting $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->settingModel = new Setting();
    }

    // ─── Root redirect ────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();
        $this->redirect('/settings/general');
    }

    // ─── General ─────────────────────────────────────────────────────────

    public function general(): never
    {
        $this->requireAuth();

        if ($this->request->method === 'POST') {
            $this->handlePost('general', [
                'app_name'      => 'required|max:200',
                'app_url'       => 'required|url',
                'company_name'  => 'max:200',
                'company_email' => 'email',
                'timezone'      => 'required',
                'locale'        => 'required',
            ], '/settings/general');
        }

        $settings = $this->settingModel->getGroup('general');

        $timezones = \DateTimeZone::listIdentifiers();

        $this->render('settings/general', [
            'csrf'      => CsrfHelper::getToken(),
            'settings'  => $settings,
            'timezones' => $timezones,
            'success'   => $this->getFlash('success'),
            'error'     => $this->getFlash('error'),
        ]);
    }

    // ─── Email settings ───────────────────────────────────────────────────

    public function email(): never
    {
        $this->requireAuth();

        if ($this->request->method === 'POST') {
            $this->handlePost('email', [
                'from_name'    => 'required|max:200',
                'from_email'   => 'required|email',
                'batch_size'   => 'required|numeric',
                'batch_interval_minutes' => 'required|numeric',
            ], '/settings/email');
        }

        $settings = $this->settingModel->getGroup('email');

        $this->render('settings/email', [
            'csrf'     => CsrfHelper::getToken(),
            'settings' => $settings,
            'success'  => $this->getFlash('success'),
            'error'    => $this->getFlash('error'),
        ]);
    }

    // ─── PWA settings ─────────────────────────────────────────────────────

    public function pwa(): never
    {
        $this->requireAuth();

        if ($this->request->method === 'POST') {
            $this->handlePost('pwa', [
                'pwa_name'        => 'required|max:100',
                'pwa_short_name'  => 'required|max:50',
                'pwa_theme_color' => 'required',
                'pwa_bg_color'    => 'required',
            ], '/settings/pwa');
        }

        $settings = $this->settingModel->getGroup('pwa');

        $this->render('settings/pwa', [
            'csrf'     => CsrfHelper::getToken(),
            'settings' => $settings,
            'success'  => $this->getFlash('success'),
            'error'    => $this->getFlash('error'),
        ]);
    }

    // ─── Security settings ────────────────────────────────────────────────

    public function security(): never
    {
        $this->requireAuth();
        $this->requireRole('admin');

        if ($this->request->method === 'POST') {
            $this->handlePost('security', [
                'max_login_attempts' => 'required|numeric',
                'lockout_minutes'    => 'required|numeric',
                'password_min_length'=> 'required|numeric',
            ], '/settings/security');
        }

        $settings = $this->settingModel->getGroup('security');

        $this->render('settings/security', [
            'csrf'     => CsrfHelper::getToken(),
            'settings' => $settings,
            'success'  => $this->getFlash('success'),
            'error'    => $this->getFlash('error'),
        ]);
    }

    // ─── Queue settings ───────────────────────────────────────────────────

    public function queue(): never
    {
        $this->requireAuth();
        $this->requireRole('admin');

        if ($this->request->method === 'POST') {
            $this->handlePost('queue', [
                'default_batch_size'     => 'required|numeric',
                'default_batch_interval' => 'required|numeric',
                'max_retries'            => 'required|numeric',
                'retry_delay_seconds'    => 'required|numeric',
            ], '/settings/queue');
        }

        $settings = $this->settingModel->getGroup('queue');

        $this->render('settings/queue', [
            'csrf'     => CsrfHelper::getToken(),
            'settings' => $settings,
            'success'  => $this->getFlash('success'),
            'error'    => $this->getFlash('error'),
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * Validate and persist a settings group on POST, then redirect.
     *
     * @param array<string, string> $rules
     */
    private function handlePost(string $group, array $rules, string $redirectTo): void
    {
        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect($redirectTo);
        }

        $input = $this->request->body;
        unset($input['_csrf_token']);

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect($redirectTo);
        }

        foreach ($input as $key => $value) {
            $this->settingModel->set($group . '.' . $key, (string) $value);
        }

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'settings_updated',
            'settings',
            null,
            "Settings group '{$group}' updated"
        );

        $this->flash('success', 'Settings saved successfully.');
        $this->redirect($redirectTo);
    }
}

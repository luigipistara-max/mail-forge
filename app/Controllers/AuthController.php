<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\Setting;
use MailForge\Models\User;
use MailForge\Services\EmailService;
use MailForge\Validators\Validator;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    // ─── Login ────────────────────────────────────────────────────────────

    public function loginForm(): never
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', [
            'csrf'  => CsrfHelper::getToken(),
            'error' => $this->getFlash('error'),
            'old'   => ['email' => $this->session->getOld('email', ''), 'remember' => $this->session->getOld('remember', '')],
        ]);
    }

    public function login(): never
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $token = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($token)) {
            $this->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/login');
        }

        $email    = trim((string) ($this->request->body['email'] ?? ''));
        $password = (string) ($this->request->body['password'] ?? '');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );

        if ($validator->fails()) {
            $this->session->flashOldInput(['email' => $email]);
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect('/login');
        }

        // Rate limiting via settings
        $settingModel  = new Setting();
        $maxAttempts   = (int) $settingModel->get('max_login_attempts', 5);
        $lockoutMinutes = (int) $settingModel->get('lockout_minutes', 15);

        $user = $this->userModel->findByEmail($email);

        if ($user === null) {
            $this->flash('error', 'Invalid email or password.');
            $this->redirect('/login');
        }

        if ($this->userModel->isLocked((int) $user['id'])) {
            $this->flash('error', "Account locked. Try again after {$lockoutMinutes} minutes.");
            $this->redirect('/login');
        }

        if ((int) ($user['failed_login_attempts'] ?? 0) >= $maxAttempts) {
            $lockUntil = date('Y-m-d H:i:s', time() + $lockoutMinutes * 60);
            $this->db->prepare(
                "UPDATE `" . \MailForge\Core\Database::table('users') . "`
                 SET `locked_until` = ? WHERE `id` = ?"
            )->execute([$lockUntil, $user['id']]);

            $this->flash('error', "Too many failed attempts. Account locked for {$lockoutMinutes} minutes.");
            $this->redirect('/login');
        }

        if (!$this->userModel->verifyPassword($password, (string) ($user['password_hash'] ?? ''))) {
            $this->userModel->incrementFailedLogins((int) $user['id']);
            $this->flash('error', 'Invalid email or password.');
            $this->redirect('/login');
        }

        if (($user['status'] ?? '') !== 'active') {
            $this->flash('error', 'Your account is not active. Please contact support.');
            $this->redirect('/login');
        }

        $this->userModel->resetFailedLogins((int) $user['id']);
        $this->userModel->updateLastLogin((int) $user['id'], (string) ($this->request->ip ?? ''));

        $this->session->regenerate();
        $this->session->setUser([
            'id'         => $user['id'],
            'email'      => $user['email'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'role'       => $user['role'],
        ]);

        (new ActivityLog())->log(
            $user['id'],
            'login',
            'user',
            $user['id'],
            'User logged in',
            $this->request->ip ?? null
        );

        $intended = $this->session->getFlash('intended_url', '/dashboard');
        $this->redirect(is_string($intended) ? $intended : '/dashboard');
    }

    public function logout(): never
    {
        $user = $this->currentUser();

        if ($user !== null) {
            (new ActivityLog())->log(
                $user['id'],
                'logout',
                'user',
                $user['id'],
                'User logged out',
                $this->request->ip ?? null
            );
        }

        $this->session->destroy();
        $this->redirect('/login');
    }

    // ─── Forgot / Reset Password ──────────────────────────────────────────

    public function forgotPasswordForm(): never
    {
        if ($this->session->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/forgot-password', [
            'csrf'    => CsrfHelper::getToken(),
            'success' => $this->getFlash('success'),
            'error'   => $this->getFlash('error'),
        ]);
    }

    public function forgotPassword(): never
    {
        $token = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($token)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/forgot-password');
        }

        $email = trim((string) ($this->request->body['email'] ?? ''));

        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
        if ($validator->fails()) {
            $this->flash('error', $validator->firstError('email'));
            $this->redirect('/forgot-password');
        }

        $user = $this->userModel->findByEmail($email);

        // Always show success to prevent user enumeration
        $this->flash('success', 'If that email exists, a password reset link has been sent.');

        if ($user !== null && ($user['status'] ?? '') === 'active') {
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt  = date('Y-m-d H:i:s', time() + 3600);

            $this->userModel->setPasswordResetToken((int) $user['id'], $resetToken, $expiresAt);

            try {
                $settingModel = new Setting();
                $appUrl       = rtrim((string) $settingModel->get('app_url', $_ENV['APP_URL'] ?? 'http://localhost'), '/');
                $resetUrl     = "{$appUrl}/reset-password?token={$resetToken}";

                $smtpModel  = new \MailForge\Models\SmtpServer();
                $smtpConfig = $smtpModel->getPrimary();

                if ($smtpConfig !== null) {
                    $emailService = new EmailService($smtpConfig);
                    $emailService->send(
                        $user['email'],
                        'Reset Your Password',
                        "<p>Click the link below to reset your password:</p>"
                            . "<p><a href=\"{$resetUrl}\">{$resetUrl}</a></p>"
                            . "<p>This link expires in 1 hour.</p>"
                    );
                }
            } catch (\Throwable) {
                // Log failure silently; user still sees success message
            }
        }

        $this->redirect('/forgot-password');
    }

    public function resetPasswordForm(): never
    {
        $resetToken = (string) ($this->request->query['token'] ?? '');

        if ($resetToken === '') {
            $this->redirect('/login');
        }

        $user = $this->userModel->findByResetToken($resetToken);

        if ($user === null) {
            $this->flash('error', 'Invalid or expired reset link.');
            $this->redirect('/login');
        }

        $this->render('auth/reset-password', [
            'csrf'  => CsrfHelper::getToken(),
            'token' => $resetToken,
            'error' => $this->getFlash('error'),
        ]);
    }

    public function resetPassword(): never
    {
        $csrfToken  = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/login');
        }

        $resetToken = (string) ($this->request->body['token'] ?? '');
        $password   = (string) ($this->request->body['password'] ?? '');
        $confirm    = (string) ($this->request->body['password_confirmation'] ?? '');

        $user = $this->userModel->findByResetToken($resetToken);

        if ($user === null) {
            $this->flash('error', 'Invalid or expired reset link.');
            $this->redirect('/login');
        }

        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirm],
            ['password' => 'required|min:8|confirmed']
        );

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect("/reset-password?token={$resetToken}");
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->userModel->update((int) $user['id'], ['password_hash' => $hash]);
        $this->userModel->clearPasswordResetToken((int) $user['id']);

        (new ActivityLog())->log(
            $user['id'],
            'password_reset',
            'user',
            $user['id'],
            'Password was reset'
        );

        $this->flash('success', 'Password reset successfully. Please log in.');
        $this->redirect('/login');
    }

    // ─── Email Verification ───────────────────────────────────────────────

    public function verifyEmail(): never
    {
        $token = (string) ($this->request->query['token'] ?? '');

        if ($token === '') {
            $this->flash('error', 'Invalid verification link.');
            $this->redirect('/login');
        }

        $user = $this->userModel->findByDoubleOptinToken($token);

        if ($user === null) {
            $this->flash('error', 'Invalid or expired verification link.');
            $this->redirect('/login');
        }

        $this->userModel->update((int) $user['id'], [
            'email_verified_at'   => date('Y-m-d H:i:s'),
            'double_optin_token'  => null,
            'status'              => 'active',
        ]);

        (new ActivityLog())->log(
            $user['id'],
            'email_verified',
            'user',
            $user['id'],
            'Email address verified'
        );

        $this->flash('success', 'Email verified. You can now log in.');
        $this->redirect('/login');
    }
}

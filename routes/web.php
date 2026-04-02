<?php

use MailForge\Core\Router;

$router = new Router();

// Auth routes
$router->get('/login', ['MailForge\Controllers\AuthController', 'loginForm']);
$router->post('/login', ['MailForge\Controllers\AuthController', 'login']);
$router->get('/logout', ['MailForge\Controllers\AuthController', 'logout']);
$router->get('/forgot-password', ['MailForge\Controllers\AuthController', 'forgotPasswordForm']);
$router->post('/forgot-password', ['MailForge\Controllers\AuthController', 'forgotPassword']);
$router->get('/reset-password/{token}', ['MailForge\Controllers\AuthController', 'resetPasswordForm']);
$router->post('/reset-password/{token}', ['MailForge\Controllers\AuthController', 'resetPassword']);
$router->get('/verify-email/{token}', ['MailForge\Controllers\AuthController', 'verifyEmail']);

// Dashboard
$router->get('/', ['MailForge\Controllers\DashboardController', 'index']);
$router->get('/dashboard', ['MailForge\Controllers\DashboardController', 'index']);

// Contacts
$router->get('/contacts', ['MailForge\Controllers\ContactController', 'index']);
$router->get('/contacts/create', ['MailForge\Controllers\ContactController', 'create']);
$router->post('/contacts', ['MailForge\Controllers\ContactController', 'store']);
$router->get('/contacts/import', ['MailForge\Controllers\ContactController', 'importForm']);
$router->post('/contacts/import', ['MailForge\Controllers\ContactController', 'import']);
$router->get('/contacts/export', ['MailForge\Controllers\ContactController', 'exportCsv']);
$router->get('/contacts/{id}', ['MailForge\Controllers\ContactController', 'show']);
$router->get('/contacts/{id}/edit', ['MailForge\Controllers\ContactController', 'edit']);
$router->post('/contacts/{id}/update', ['MailForge\Controllers\ContactController', 'update']);
$router->post('/contacts/{id}/delete', ['MailForge\Controllers\ContactController', 'delete']);

// Lists
$router->get('/lists', ['MailForge\Controllers\ListController', 'index']);
$router->get('/lists/create', ['MailForge\Controllers\ListController', 'create']);
$router->post('/lists', ['MailForge\Controllers\ListController', 'store']);
$router->get('/lists/{id}', ['MailForge\Controllers\ListController', 'show']);
$router->get('/lists/{id}/edit', ['MailForge\Controllers\ListController', 'edit']);
$router->post('/lists/{id}/update', ['MailForge\Controllers\ListController', 'update']);
$router->post('/lists/{id}/delete', ['MailForge\Controllers\ListController', 'delete']);
$router->get('/lists/{id}/contacts', ['MailForge\Controllers\ListController', 'contacts']);
$router->post('/lists/{listId}/contacts/{contactId}/remove', ['MailForge\Controllers\ListController', 'removeContact']);

// Segments
$router->get('/segments', ['MailForge\Controllers\SegmentController', 'index']);
$router->get('/segments/create', ['MailForge\Controllers\SegmentController', 'create']);
$router->post('/segments', ['MailForge\Controllers\SegmentController', 'store']);
$router->get('/segments/{id}/edit', ['MailForge\Controllers\SegmentController', 'edit']);
$router->post('/segments/{id}/update', ['MailForge\Controllers\SegmentController', 'update']);
$router->post('/segments/{id}/delete', ['MailForge\Controllers\SegmentController', 'delete']);
$router->get('/segments/{id}/preview', ['MailForge\Controllers\SegmentController', 'preview']);

// Templates
$router->get('/templates', ['MailForge\Controllers\TemplateController', 'index']);
$router->get('/templates/create', ['MailForge\Controllers\TemplateController', 'create']);
$router->post('/templates', ['MailForge\Controllers\TemplateController', 'store']);
$router->get('/templates/{id}', ['MailForge\Controllers\TemplateController', 'preview']);
$router->get('/templates/{id}/edit', ['MailForge\Controllers\TemplateController', 'edit']);
$router->post('/templates/{id}/update', ['MailForge\Controllers\TemplateController', 'update']);
$router->post('/templates/{id}/delete', ['MailForge\Controllers\TemplateController', 'delete']);
$router->post('/templates/{id}/duplicate', ['MailForge\Controllers\TemplateController', 'duplicate']);
$router->post('/templates/{id}/send-test', ['MailForge\Controllers\TemplateController', 'sendTest']);

// Campaigns
$router->get('/campaigns', ['MailForge\Controllers\CampaignController', 'index']);
$router->get('/campaigns/create', ['MailForge\Controllers\CampaignController', 'create']);
$router->post('/campaigns', ['MailForge\Controllers\CampaignController', 'store']);
$router->get('/campaigns/{id}', ['MailForge\Controllers\CampaignController', 'show']);
$router->get('/campaigns/{id}/edit', ['MailForge\Controllers\CampaignController', 'edit']);
$router->post('/campaigns/{id}/update', ['MailForge\Controllers\CampaignController', 'update']);
$router->post('/campaigns/{id}/delete', ['MailForge\Controllers\CampaignController', 'delete']);
$router->post('/campaigns/{id}/schedule', ['MailForge\Controllers\CampaignController', 'schedule']);
$router->post('/campaigns/{id}/queue', ['MailForge\Controllers\CampaignController', 'queue']);
$router->post('/campaigns/{id}/pause', ['MailForge\Controllers\CampaignController', 'pause']);
$router->post('/campaigns/{id}/cancel', ['MailForge\Controllers\CampaignController', 'cancel']);
$router->post('/campaigns/{id}/duplicate', ['MailForge\Controllers\CampaignController', 'duplicate']);
$router->get('/campaigns/{id}/preview', ['MailForge\Controllers\CampaignController', 'preview']);
$router->get('/campaigns/{id}/recipients', ['MailForge\Controllers\CampaignController', 'recipients']);
$router->post('/campaigns/{id}/send-test', ['MailForge\Controllers\CampaignController', 'sendTest']);

// SMTP
$router->get('/smtp', ['MailForge\Controllers\SmtpController', 'index']);
$router->get('/smtp/create', ['MailForge\Controllers\SmtpController', 'create']);
$router->post('/smtp', ['MailForge\Controllers\SmtpController', 'store']);
$router->get('/smtp/{id}/edit', ['MailForge\Controllers\SmtpController', 'edit']);
$router->post('/smtp/{id}/update', ['MailForge\Controllers\SmtpController', 'update']);
$router->post('/smtp/{id}/delete', ['MailForge\Controllers\SmtpController', 'delete']);
$router->post('/smtp/{id}/test', ['MailForge\Controllers\SmtpController', 'test']);
$router->post('/smtp/{id}/toggle', ['MailForge\Controllers\SmtpController', 'toggleActive']);

// Reports
$router->get('/reports', ['MailForge\Controllers\ReportController', 'index']);
$router->get('/reports/campaigns/{id}', ['MailForge\Controllers\ReportController', 'campaign']);
$router->get('/reports/campaigns/{id}/export', ['MailForge\Controllers\ReportController', 'export']);
$router->get('/reports/contacts', ['MailForge\Controllers\ReportController', 'contacts']);

// Settings
$router->get('/settings', ['MailForge\Controllers\SettingsController', 'index']);
$router->get('/settings/general', ['MailForge\Controllers\SettingsController', 'general']);
$router->post('/settings/general', ['MailForge\Controllers\SettingsController', 'general']);
$router->get('/settings/email', ['MailForge\Controllers\SettingsController', 'email']);
$router->post('/settings/email', ['MailForge\Controllers\SettingsController', 'email']);
$router->get('/settings/pwa', ['MailForge\Controllers\SettingsController', 'pwa']);
$router->post('/settings/pwa', ['MailForge\Controllers\SettingsController', 'pwa']);
$router->get('/settings/security', ['MailForge\Controllers\SettingsController', 'security']);
$router->post('/settings/security', ['MailForge\Controllers\SettingsController', 'security']);
$router->get('/settings/queue', ['MailForge\Controllers\SettingsController', 'queue']);
$router->post('/settings/queue', ['MailForge\Controllers\SettingsController', 'queue']);

// Public pages (no auth required)
$router->get('/subscribe/{listId}', ['MailForge\Controllers\PublicController', 'subscribe']);
$router->post('/subscribe/{listId}', ['MailForge\Controllers\PublicController', 'subscribe']);
$router->get('/confirm/{token}', ['MailForge\Controllers\PublicController', 'confirmDoubleOptin']);
$router->get('/unsubscribe/{token}', ['MailForge\Controllers\PublicController', 'unsubscribePage']);
$router->post('/unsubscribe/{token}', ['MailForge\Controllers\PublicController', 'unsubscribeConfirm']);
$router->get('/webview/{campaignId}/{contactToken}', ['MailForge\Controllers\PublicController', 'webview']);
$router->get('/track/open/{campaignId}/{contactToken}', ['MailForge\Controllers\PublicController', 'trackOpen']);
$router->get('/track/click/{trackingCode}/{contactToken}', ['MailForge\Controllers\PublicController', 'trackClick']);

return $router;

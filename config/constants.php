<?php
// Application Constants

// User Roles
const USER_ROLE_ADMIN = 'admin';
const USER_ROLE_EDITOR = 'editor';
const USER_ROLE_VIEWER = 'viewer';

// Contact Statuses
const CONTACT_STATUS_ACTIVE = 'active';
const CONTACT_STATUS_INACTIVE = 'inactive';
const CONTACT_STATUS_ARCHIVED = 'archived';

// Campaign Statuses
const CAMPAIGN_STATUS_SCHEDULED = 'scheduled';
const CAMPAIGN_STATUS_RUNNING = 'running';
const CAMPAIGN_STATUS_COMPLETED = 'completed';
const CAMPAIGN_STATUS_CANCELLED = 'cancelled';

// Recipient Statuses
const RECIPIENT_STATUS_SENT = 'sent';
const RECIPIENT_STATUS_DELIVERED = 'delivered';
const RECIPIENT_STATUS_FAILED = 'failed';

// Automation Statuses
const AUTOMATION_STATUS_ACTIVE = 'active';
const AUTOMATION_STATUS_PAUSED = 'paused';
const AUTOMATION_STATUS_STOPPED = 'stopped';

// Encryption
const ENCRYPTION_KEY = 'your-encryption-key-here';

// Validation Patterns
const VALIDATION_EMAIL_PATTERN = '/^[\w-.]+@([\w-]+\.)+[\w-]{2,4}$/';
const VALIDATION_PHONE_PATTERN = '/^\+?[1-9]\d{1,14}$/';
?>
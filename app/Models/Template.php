<?php

declare(strict_types=1);

namespace MailForge\Models;

class Template extends BaseModel
{
    protected static string $table = 'templates';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'name',
        'description',
        'category',
        'html_content',
        'plain_content',
        'thumbnail',
        'status',
        'created_by',
    ];

    public function getActive(): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `status` = 'active' AND `deleted_at` IS NULL ORDER BY `name` ASC";

        return $this->executeQuery($sql)->fetchAll();
    }

    public function duplicate(int|string $id): int|string|null
    {
        $original = $this->find($id);

        if ($original === null) {
            return null;
        }

        $copy = array_intersect_key($original, array_flip(static::$fillable));
        $copy['name'] = $original['name'] . ' (Copy)';

        return $this->create($copy);
    }

    /**
     * Replace merge tags like {{first_name}}, {{email}}, {{unsubscribe_link}} etc.
     * in the given HTML string using contact and campaign data.
     */
    public function replaceMergeTags(string $html, array $contact, array $campaign = []): string
    {
        $tags = [
            '{{first_name}}'      => $contact['first_name']   ?? '',
            '{{last_name}}'       => $contact['last_name']    ?? '',
            '{{full_name}}'       => trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '')),
            '{{email}}'           => $contact['email']        ?? '',
            '{{phone}}'           => $contact['phone']        ?? '',
            '{{campaign_name}}'   => $campaign['name']        ?? '',
            '{{campaign_subject}}' => $campaign['subject']    ?? '',
            '{{from_name}}'       => $campaign['from_name']   ?? '',
            '{{from_email}}'      => $campaign['from_email']  ?? '',
            '{{current_year}}'    => date('Y'),
            '{{current_date}}'    => date('Y-m-d'),
        ];

        // Unsubscribe link uses contact ID + token when available.
        $unsubscribeToken = $contact['unsubscribe_token'] ?? ($contact['id'] ?? '');
        $tags['{{unsubscribe_link}}'] = '/unsubscribe/' . $unsubscribeToken;

        // Web version link.
        $tags['{{web_version_link}}'] = isset($campaign['id'])
            ? '/campaigns/' . $campaign['id'] . '/web-version'
            : '';

        // Replace all registered tags.
        $html = str_replace(array_keys($tags), array_values($tags), $html);

        // Remove any remaining unresolved tags.
        $html = preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/', '', $html) ?? $html;

        return $html;
    }

    public function getCategories(): array
    {
        $table = $this->getTable();
        $sql   = "SELECT DISTINCT `category` FROM `{$table}` WHERE `deleted_at` IS NULL AND `category` IS NOT NULL ORDER BY `category` ASC";

        $rows = $this->executeQuery($sql)->fetchAll();

        return array_column($rows, 'category');
    }
}

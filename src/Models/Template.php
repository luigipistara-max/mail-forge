<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * Template model
 */
class Template extends BaseModel
{
    protected string $table = 'templates';

    // ----------------------------------------------------------------
    // Rendering
    // ----------------------------------------------------------------

    /**
     * Render a template by replacing {{variable}} placeholders with values.
     *
     * Standard placeholders: {{first_name}}, {{last_name}}, {{email}},
     * {{unsubscribe_url}}, and any custom field name.
     *
     * @param  int                  $templateId
     * @param  array<string, mixed> $variables
     * @return array{subject: string, html: string, text: string}|null
     */
    public function render(int $templateId, array $variables = []): ?array
    {
        $template = $this->find($templateId);

        if ($template === null) {
            return null;
        }

        $subject  = self::replacePlaceholders($template['subject'], $variables);
        $html     = self::replacePlaceholders($template['html_content'], $variables);
        $text     = self::replacePlaceholders($template['text_content'] ?? '', $variables);

        return compact('subject', 'html', 'text');
    }

    /**
     * Replace {{key}} placeholders with corresponding values.
     *
     * @param array<string, mixed> $variables
     */
    public static function replacePlaceholders(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        // Remove any remaining unreplaced placeholders
        return preg_replace('/\{\{[^}]+\}\}/', '', $content) ?? $content;
    }

    // ----------------------------------------------------------------
    // Utility
    // ----------------------------------------------------------------

    /**
     * Duplicate a template and return the new record ID.
     */
    public function duplicate(int $templateId): ?int
    {
        $original = $this->find($templateId);

        if ($original === null) {
            return null;
        }

        unset($original['id'], $original['created_at'], $original['updated_at']);
        $original['name'] = $original['name'] . ' (copy)';

        return $this->create($original);
    }

    /**
     * Return templates filtered by category.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByCategory(string $category, int $userId): array
    {
        return $this->findAll(['category' => $category, 'user_id' => $userId], 'name');
    }
}

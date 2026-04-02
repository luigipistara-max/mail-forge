<?php

declare(strict_types=1);

namespace MailForge\Models;

use MailForge\Core\Database;

class Segment extends BaseModel
{
    protected static string $table = 'segments';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'name',
        'description',
        'match_type',
        'estimated_count',
        'last_calculated_at',
        'status',
    ];

    public function getRules(int|string $segmentId): array
    {
        $rulesTable = Database::getPrefix() . 'segment_rules';
        $sql        = "SELECT * FROM `{$rulesTable}` WHERE `segment_id` = :segment_id ORDER BY `sort_order` ASC";

        return $this->executeQuery($sql, [':segment_id' => $segmentId])->fetchAll();
    }

    public function addRule(int|string $segmentId, array $ruleData): int|string
    {
        $rulesTable           = Database::getPrefix() . 'segment_rules';
        $ruleData['segment_id'] = $segmentId;

        $columns      = array_keys($ruleData);
        $placeholders = array_map(fn(string $c) => ":{$c}", $columns);
        $colList      = implode(', ', array_map(fn(string $c) => "`{$c}`", $columns));
        $valList      = implode(', ', $placeholders);
        $sql          = "INSERT INTO `{$rulesTable}` ({$colList}) VALUES ({$valList})";
        $bindings     = array_combine($placeholders, array_values($ruleData));

        $this->executeQuery($sql, $bindings);

        return $this->db->lastInsertId();
    }

    public function updateRules(int|string $segmentId, array $rules): bool
    {
        $rulesTable = Database::getPrefix() . 'segment_rules';

        // Delete existing rules then re-insert.
        $this->executeQuery(
            "DELETE FROM `{$rulesTable}` WHERE `segment_id` = :segment_id",
            [':segment_id' => $segmentId]
        );

        foreach ($rules as $rule) {
            $this->addRule($segmentId, $rule);
        }

        return true;
    }

    /**
     * Run the segment rules and return matching contact IDs.
     */
    public function calculateContacts(int|string $segmentId): array
    {
        $segment = $this->find($segmentId);

        if ($segment === null) {
            return [];
        }

        $rules     = $this->getRules($segmentId);
        $matchType = $segment['match_type'] ?? 'all';

        if (empty($rules)) {
            return [];
        }

        $sql = $this->buildSegmentQuery($rules, $matchType);

        $rows = $this->executeQuery($sql)->fetchAll();
        $ids  = array_column($rows, 'id');

        // Cache the estimated count.
        $table = $this->getTable();
        $this->executeQuery(
            "UPDATE `{$table}` SET `estimated_count` = :count, `last_calculated_at` = NOW() WHERE `id` = :id",
            [':count' => count($ids), ':id' => $segmentId]
        );

        return $ids;
    }

    /**
     * Build a SELECT query for contact IDs matching the given rules.
     * Each rule: ['field', 'operator', 'value']
     * matchType: 'all' => AND, 'any' => OR
     */
    public function buildSegmentQuery(array $rules, string $matchType = 'all'): string
    {
        $contactTable = Database::getPrefix() . 'contacts';
        $clauses      = [];
        $glue         = $matchType === 'any' ? ' OR ' : ' AND ';

        foreach ($rules as $rule) {
            $field    = $rule['field']    ?? '';
            $operator = $rule['operator'] ?? '=';
            $value    = $this->db->quote((string) ($rule['value'] ?? ''));

            if ($field === '') {
                continue;
            }

            $safeField = preg_replace('/[^a-zA-Z0-9_]/', '', $field);

            $clauses[] = match ($operator) {
                'equals'            => "`{$safeField}` = {$value}",
                'not_equals'        => "`{$safeField}` != {$value}",
                'contains'          => "`{$safeField}` LIKE " . $this->db->quote('%' . trim((string) ($rule['value'] ?? ''), '%') . '%'),
                'not_contains'      => "`{$safeField}` NOT LIKE " . $this->db->quote('%' . trim((string) ($rule['value'] ?? ''), '%') . '%'),
                'starts_with'       => "`{$safeField}` LIKE " . $this->db->quote(trim((string) ($rule['value'] ?? ''), '%') . '%'),
                'ends_with'         => "`{$safeField}` LIKE " . $this->db->quote('%' . trim((string) ($rule['value'] ?? ''), '%')),
                'greater_than'      => "`{$safeField}` > {$value}",
                'less_than'         => "`{$safeField}` < {$value}",
                'is_null'           => "`{$safeField}` IS NULL",
                'is_not_null'       => "`{$safeField}` IS NOT NULL",
                default             => "`{$safeField}` = {$value}",
            };
        }

        if (empty($clauses)) {
            return "SELECT `id` FROM `{$contactTable}` WHERE `deleted_at` IS NULL";
        }

        $whereStr = implode($glue, $clauses);

        return "SELECT `id` FROM `{$contactTable}` WHERE `deleted_at` IS NULL AND ({$whereStr})";
    }

    public function getEstimatedCount(int|string $segmentId): int
    {
        $segment = $this->find($segmentId);

        if ($segment === null) {
            return 0;
        }

        // Return cached value if available and recent (< 1 hour old).
        if (
            isset($segment['estimated_count'], $segment['last_calculated_at'])
            && $segment['last_calculated_at'] !== null
            && strtotime($segment['last_calculated_at']) > (time() - 3600)
        ) {
            return (int) $segment['estimated_count'];
        }

        return count($this->calculateContacts($segmentId));
    }
}

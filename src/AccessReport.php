<?php

namespace DeFlip;

use DateTimeImmutable;

final class AccessReport
{
    public const DEFAULT_PAGE_SIZE = 20;
    public const MIN_PAGE_SIZE = 1;
    public const MAX_PAGE_SIZE = 200;
    private array $filters;

    public function __construct(array $input)
    {
        $this->filters = [];
        foreach (['title', 'author', 'publishYear', 'startDate', 'untilDate'] as $field) {
            $this->filters[$field] = isset($input[$field]) && is_string($input[$field]) ? mb_substr(trim(strip_tags($input[$field])), 0, 255) : '';
        }
        $this->filters['startDate'] = $this->filters['startDate'] ?: '2000-01-01';
        $this->filters['untilDate'] = $this->filters['untilDate'] ?: date('Y-m-d');
        $pageSize = isset($input['recsEachPage']) && is_scalar($input['recsEachPage']) ? (int) $input['recsEachPage'] : self::DEFAULT_PAGE_SIZE;
        $this->filters['recsEachPage'] = $pageSize >= self::MIN_PAGE_SIZE && $pageSize <= self::MAX_PAGE_SIZE ? $pageSize : self::DEFAULT_PAGE_SIZE;
    }

    public function value(string $field)
    {
        return $this->filters[$field] ?? '';
    }

    public function query(): array
    {
        return $this->filters;
    }

    public function errors(): array
    {
        foreach (['startDate', 'untilDate'] as $field) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $this->filters[$field])) {
                return [__('Select valid start and end dates.')];
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $this->filters[$field]);
            if (!$date || $date->format('Y-m-d') !== $this->filters[$field]) {
                return [__('Select valid start and end dates.')];
            }
        }
        return $this->filters['startDate'] > $this->filters['untilDate'] ? [__('The end date must be on or after the start date.')] : [];
    }

    public function criteria($database, ?int $fileId = null): string
    {
        if ($this->errors()) return '1 = 0';
        $criteria = $fileId === null ? 'fr.filelog_id IS NOT NULL' : 'fr.file_id = ' . $fileId;
        $criteria .= " AND fr.date_read >= '" . $database->escape_string($this->filters['startDate']) . "' AND fr.date_read < DATE_ADD('" . $database->escape_string($this->filters['untilDate']) . "', INTERVAL 1 DAY)";
        $bibliography = [];
        foreach (preg_split('/\s+/u', $this->filters['title'], -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            $word = $database->escape_string($word);
            $bibliography[] = "(filter_b.title LIKE '%{$word}%' OR filter_b.isbn_issn LIKE '%{$word}%')";
        }
        foreach (['author' => 'author', 'publishYear' => 'publish_year'] as $field => $column) {
            if ($this->filters[$field] !== '') {
                $value = $database->escape_string($this->filters[$field]);
                $bibliography[] = "filter_b.{$column} LIKE '%{$value}%'";
            }
        }
        if ($bibliography) {
            $criteria .= ' AND EXISTS (SELECT 1 FROM biblio_attachment filter_att JOIN search_biblio filter_b ON filter_b.biblio_id = filter_att.biblio_id WHERE filter_att.file_id = fr.file_id AND ' . implode(' AND ', $bibliography) . ')';
        }
        return $criteria;
    }

    public static function tables(bool $detail = false): string
    {
        // One representative collection per file prevents shared attachments multiplying access logs.
        $tables = 'files_read fr LEFT JOIN files f ON f.file_id = fr.file_id
            LEFT JOIN (SELECT file_id, MIN(biblio_id) AS biblio_id FROM biblio_attachment GROUP BY file_id) ba ON ba.file_id = fr.file_id
            LEFT JOIN search_biblio b ON b.biblio_id = ba.biblio_id';
        if ($detail) {
            $tables .= ' LEFT JOIN member m ON m.member_id = fr.member_id LEFT JOIN user u ON u.user_id = fr.user_id LEFT JOIN files_read_guest frg ON frg.id = fr.guest_id';
        }
        return $tables;
    }
}

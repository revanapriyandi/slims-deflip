<?php

namespace DeFlip;

use SLiMS\DB;

final class GuestAccess
{
    public const FIELDS = [
        'name' => ['label' => 'Name', 'maxLength' => 50, 'autocomplete' => 'name', 'type' => 'text'],
        'institution' => ['label' => 'Institution', 'maxLength' => 255, 'autocomplete' => 'organization', 'type' => 'text'],
        'phonenumber' => ['label' => 'Phone Number', 'maxLength' => 15, 'autocomplete' => 'tel', 'type' => 'tel'],
    ];

    public static function values(array $input): array
    {
        $values = [];
        foreach (self::FIELDS as $field => $definition) {
            $values[$field] = isset($input[$field]) && is_string($input[$field]) ? trim($input[$field]) : '';
        }
        return $values;
    }

    public static function errors(array $values, bool $agreed): array
    {
        $errors = [];
        foreach (self::FIELDS as $field => $definition) {
            $value = $values[$field] ?? '';
            if ($value === '') {
                $errors[$field] = sprintf(__('%s is required.'), __($definition['label']));
            } elseif (!mb_check_encoding($value, 'UTF-8') || mb_strlen($value, 'UTF-8') > $definition['maxLength'] || preg_match('/[\x00-\x1F\x7F]/u', $value)) {
                $errors[$field] = sprintf(__('%s must contain at most %s characters.'), __($definition['label']), $definition['maxLength']);
            }
        }
        if (!isset($errors['phonenumber']) && !preg_match('/^\+?[0-9][0-9 ()-]*$/D', $values['phonenumber'])) {
            $errors['phonenumber'] = __('Enter a valid phone number, including its area or country code.');
        }
        if (!$agreed) {
            $errors['agree'] = __('Agree to the terms and conditions to continue.');
        }
        return $errors;
    }

    public static function register(array $values, int $fileId): void
    {
        $database = DB::getInstance();
        $statement = $database->prepare('INSERT INTO files_read_guest (name, institution, phonenumber, created_at) VALUES (?, ?, ?, ?)');
        if (!$statement->execute([$values['name'], $values['institution'], $values['phonenumber'], date('Y-m-d H:i:s')])) {
            throw new \RuntimeException('Guest registration failed.');
        }
        $id = (int) $database->lastInsertId();
        if ($id < 1) {
            throw new \RuntimeException('Guest registration did not return an ID.');
        }
        $_SESSION['guestReadEbook'] = ['id' => $id, 'books' => [$fileId => ['startread' => date('Y-m-d H:i:s')]]];
    }

    public static function hasIdentity(): bool
    {
        return isset($_SESSION['guestReadEbook']['id']) && (int) $_SESSION['guestReadEbook']['id'] > 0;
    }
}

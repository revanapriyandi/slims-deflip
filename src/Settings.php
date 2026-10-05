<?php

namespace DeFlip;

use InvalidArgumentException;
use SLiMS\Config;

final class Settings
{
    private const MAX_TOS_BYTES = 60000;

    public static function defaults(): array
    {
        return [
            'guestForm' => false,
            'allowDownload' => true,
            'tos' => '<ol><li>Your ID will be used for internal use.</li><li>You are willing to accept all forms of use of the data that you have submitted and then will not dispute the use of the data even if you consider that the use of the data is not in accordance with your expectations.</li></ol>',
        ];
    }

    public static function get(): array
    {
        $stored = config('dflipConfig');
        $settings = array_replace(self::defaults(), is_array($stored) ? $stored : []);
        $settings['guestForm'] = filter_var($settings['guestForm'], FILTER_VALIDATE_BOOLEAN);
        $settings['allowDownload'] = filter_var($settings['allowDownload'], FILTER_VALIDATE_BOOLEAN);
        $settings['tos'] = self::sanitizeTerms(is_string($settings['tos']) ? $settings['tos'] : '');
        return $settings;
    }

    public static function validate(array $input): array
    {
        foreach (['guestForm', 'allowDownload'] as $field) {
            if (!isset($input[$field]) || !is_scalar($input[$field]) || !in_array((string) $input[$field], ['0', '1'], true)) {
                throw new InvalidArgumentException(__('Select a valid option for guest access and downloads.'));
            }
        }
        if (!isset($input['tos']) || !is_string($input['tos']) || strlen($input['tos']) > self::MAX_TOS_BYTES) {
            throw new InvalidArgumentException(__('Terms and conditions are too long or invalid.'));
        }
        $terms = self::sanitizeTerms($input['tos']);
        if ((string) $input['guestForm'] === '1' && trim(strip_tags($terms)) === '') {
            throw new InvalidArgumentException(__('Enter terms and conditions before enabling the guest form.'));
        }
        return [
            'guestForm' => (string) $input['guestForm'] === '1',
            'allowDownload' => (string) $input['allowDownload'] === '1',
            'tos' => $terms,
        ];
    }

    public static function save(array $settings): bool
    {
        return Config::createOrUpdate('dflipConfig', $settings);
    }

    public static function sanitizeTerms(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,b,i,u,s,ul,ol,li,a[href|title],h2,h3,blockquote');
        $config->set('URI.DisableExternalResources', true);
        $config->set('Cache.SerializerPath', sys_get_temp_dir());
        return (new \HTMLPurifier($config))->purify($html);
    }
}

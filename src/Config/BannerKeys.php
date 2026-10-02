<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Chiavi di configurazione dei banner.
 *
 * Ogni banner N usa chiavi `JPZ_TRIPLEBANNER_B{N}_{CAMPO}`. `IMAGE` è il nome
 * storico dell'immagine (dalla 2.0.0 è quella desktop) e non va rinominato:
 * così le installazioni esistenti conservano l'immagine senza migrazioni.
 */
final class BannerKeys
{
    public const PREFIX = 'JPZ_TRIPLEBANNER_';

    public const BANNER_COUNT = 3;

    public const IMAGE_DESKTOP = 'IMAGE';
    public const IMAGE_MOBILE = 'IMAGE_MOBILE';
    public const TEXT = 'TEXT';
    public const CATEGORY = 'CATEGORY';
    public const QUERY_PARAMS = 'QUERY_PARAMS';

    public const FIELDS = [
        self::IMAGE_DESKTOP,
        self::IMAGE_MOBILE,
        self::TEXT,
        self::CATEGORY,
        self::QUERY_PARAMS,
    ];

    /** Campi salvati per lingua. */
    public const LANG_FIELDS = [self::TEXT, self::QUERY_PARAMS];

    public static function key(int $banner, string $field): string
    {
        return self::PREFIX . 'B' . $banner . '_' . $field;
    }

    /**
     * @return int[]
     */
    public static function banners(): array
    {
        return range(1, self::BANNER_COUNT);
    }

    /**
     * @return string[]
     */
    public static function all(): array
    {
        $keys = [];
        foreach (self::banners() as $banner) {
            foreach (self::FIELDS as $field) {
                $keys[] = self::key($banner, $field);
            }
        }

        return $keys;
    }
}

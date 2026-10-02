<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Presenter;

use Category;
use Configuration;
use Jpz\TripleBanner\Config\BannerKeys;
use Jpz\TripleBanner\Image\BannerImageStorage;
use League\Uri\Modifier;
use Link;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Prepara i dati dei banner per il template del front office.
 */
final class BannerPresenter
{
    /**
     * Media query sotto la quale si usa l'immagine mobile (breakpoint `md` del tema).
     */
    public const MOBILE_MEDIA = '(max-width: 767.98px)';

    public function __construct(
        private readonly BannerImageStorage $images,
        private readonly Link $link,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function present(int $idLang): array
    {
        $banners = [];

        foreach (BannerKeys::banners() as $banner) {
            $text = (string) Configuration::get(BannerKeys::key($banner, BannerKeys::TEXT), $idLang);
            $desktop = $this->images->describe(Configuration::get(BannerKeys::key($banner, BannerKeys::IMAGE_DESKTOP)) ?: null);
            $mobile = $this->images->describe(Configuration::get(BannerKeys::key($banner, BannerKeys::IMAGE_MOBILE)) ?: null);

            // Senza immagine desktop la mobile resta l'unica disponibile: la si usa ovunque.
            if ($desktop === null && $mobile !== null) {
                $desktop = $mobile;
                $mobile = null;
            }

            if ($desktop === null && $text === '') {
                continue;
            }

            [$categoryLink, $categoryName] = $this->categoryLink($banner, $idLang);

            $banners[] = [
                'image' => $desktop === null ? null : [
                    'desktop' => $desktop,
                    'mobile' => $mobile,
                    'mobile_media' => self::MOBILE_MEDIA,
                ],
                // Compatibilità con i template che usano ancora un'immagine sola.
                'image_url' => $desktop['url'] ?? null,
                'text' => $text,
                'category_link' => $categoryLink,
                'category_name' => $categoryName,
            ];
        }

        return $banners;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function categoryLink(int $banner, int $idLang): array
    {
        $idCategory = (int) Configuration::get(BannerKeys::key($banner, BannerKeys::CATEGORY));
        if ($idCategory <= 0) {
            return ['', ''];
        }

        $category = new Category($idCategory, $idLang);
        if (!Validate::isLoadedObject($category)) {
            return ['', ''];
        }

        $queryParams = str_replace('/', '\/', trim((string) Configuration::get(BannerKeys::key($banner, BannerKeys::QUERY_PARAMS), $idLang)));
        $uri = Modifier::wrap($this->link->getCategoryLink($category))->appendQuery($queryParams);

        return [$uri->toString(), (string) $category->name];
    }
}

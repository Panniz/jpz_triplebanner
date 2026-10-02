<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Form;

use Jpz\TripleBanner\Config\BannerKeys;
use Jpz\TripleBanner\Image\BannerImageStorage;
use Language;
use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Lettura e scrittura della configurazione dei banner (chiavi in BannerKeys).
 *
 * I dati del form sono indicizzati per banner (`banner_1`, `banner_2`, ...);
 * le immagini arrivano come UploadedFile e vengono salvate in uploads/,
 * in configurazione resta solo il nome del file.
 */
final class BannerConfigurationDataConfiguration implements DataConfigurationInterface
{
    private const IMAGE_FIELDS = [
        'desktop' => BannerKeys::IMAGE_DESKTOP,
        'mobile' => BannerKeys::IMAGE_MOBILE,
    ];

    public function __construct(
        private readonly ConfigurationInterface $configuration,
        private readonly BannerImageStorage $images,
    ) {
    }

    public function getConfiguration(): array
    {
        $data = [];
        foreach (BannerKeys::banners() as $banner) {
            $category = (int) $this->configuration->get(BannerKeys::key($banner, BannerKeys::CATEGORY));

            $data['banner_' . $banner] = [
                'text' => $this->getLocalized(BannerKeys::key($banner, BannerKeys::TEXT)),
                'category' => $category > 0 ? $category : null,
                'query_params' => $this->getLocalized(BannerKeys::key($banner, BannerKeys::QUERY_PARAMS)),
            ];
        }

        return $data;
    }

    public function updateConfiguration(array $configuration): array
    {
        if (!$this->validateConfiguration($configuration)) {
            return ['Dati del form non validi.'];
        }

        $errors = [];
        foreach (BannerKeys::banners() as $banner) {
            $values = $configuration['banner_' . $banner];

            foreach (self::IMAGE_FIELDS as $variant => $field) {
                $error = $this->updateImage($banner, $variant, $field, $values);
                if ($error !== null) {
                    $errors[] = $error;
                }
            }

            $this->configuration->set(
                BannerKeys::key($banner, BannerKeys::TEXT),
                $this->normalizeLocalized($values['text'] ?? []),
                null,
                ['html' => true]
            );
            $this->configuration->set(
                BannerKeys::key($banner, BannerKeys::QUERY_PARAMS),
                array_map(
                    static fn (string $value): string => ltrim(trim($value), '?'),
                    $this->normalizeLocalized($values['query_params'] ?? [])
                )
            );
            $this->configuration->set(BannerKeys::key($banner, BannerKeys::CATEGORY), (int) ($values['category'] ?? 0));
        }

        return $errors;
    }

    public function validateConfiguration(array $configuration): bool
    {
        foreach (BannerKeys::banners() as $banner) {
            if (!isset($configuration['banner_' . $banner]) || !is_array($configuration['banner_' . $banner])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sostituisce o rimuove un'immagine. Un nuovo file ha la precedenza sulla
     * spunta "rimuovi"; il vecchio file viene cancellato solo a salvataggio riuscito.
     *
     * @param array<string, mixed> $values
     */
    private function updateImage(int $banner, string $variant, string $field, array $values): ?string
    {
        $key = BannerKeys::key($banner, $field);
        $current = (string) $this->configuration->get($key);
        $upload = $values['image_' . $variant] ?? null;

        if ($upload instanceof UploadedFile) {
            try {
                $filename = $this->images->store($upload, sprintf('banner%d_%s', $banner, $variant));
            } catch (\Throwable $e) {
                return sprintf('Banner %d, immagine %s: %s', $banner, $variant, $e->getMessage());
            }

            $this->configuration->set($key, $filename);
            $this->images->delete($current);

            return null;
        }

        if (!empty($values['remove_image_' . $variant]) && $current !== '') {
            $this->configuration->set($key, '');
            $this->images->delete($current);
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function getLocalized(string $key): array
    {
        $value = $this->configuration->get($key);
        $values = [];
        foreach (Language::getIDs(false) as $idLang) {
            $values[(int) $idLang] = is_array($value) ? (string) ($value[$idLang] ?? '') : '';
        }

        return $values;
    }

    /**
     * @param array<int|string, mixed> $values
     *
     * @return array<int, string>
     */
    private function normalizeLocalized(array $values): array
    {
        $normalized = [];
        foreach (Language::getIDs(false) as $idLang) {
            $normalized[(int) $idLang] = (string) ($values[$idLang] ?? '');
        }

        return $normalized;
    }
}

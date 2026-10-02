<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Image;

use Symfony\Component\HttpFoundation\File\UploadedFile;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * File delle immagini dei banner nella cartella `uploads/` del modulo.
 */
final class BannerImageStorage
{
    public const MODULE_NAME = 'jpz_triplebanner';

    /** Formati accettati in upload, anche dal form (vincolo Image). */
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public const MAX_SIZE = '5M';

    public function getDirectory(): string
    {
        return _PS_MODULE_DIR_ . self::MODULE_NAME . '/uploads/';
    }

    public function getBaseUrl(): string
    {
        return _MODULE_DIR_ . self::MODULE_NAME . '/uploads/';
    }

    public function exists(?string $filename): bool
    {
        return $this->isSafeName($filename) && is_file($this->getDirectory() . $filename);
    }

    public function getUrl(?string $filename): ?string
    {
        return $this->exists($filename) ? $this->getBaseUrl() . $filename : null;
    }

    /**
     * URL e dimensioni intrinseche, per gli attributi width/height che evitano
     * lo spostamento del layout al caricamento dell'immagine.
     *
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public function describe(?string $filename): ?array
    {
        if (!$this->exists($filename)) {
            return null;
        }

        $size = @getimagesize($this->getDirectory() . $filename);

        return [
            'url' => $this->getBaseUrl() . $filename,
            'width' => $size ? (int) $size[0] : null,
            'height' => $size ? (int) $size[1] : null,
        ];
    }

    /**
     * Sposta il file caricato in uploads/ con un nome univoco e lo restituisce.
     *
     * @throws \RuntimeException se il file non è valido o non può essere spostato
     */
    public function store(UploadedFile $file, string $prefix): string
    {
        if (!$file->isValid()) {
            throw new \RuntimeException($file->getErrorMessage());
        }

        $mimeType = (string) $file->getMimeType();
        if (!in_array($mimeType, self::MIME_TYPES, true)) {
            throw new \RuntimeException(sprintf('Formato non supportato (%s).', $mimeType));
        }

        $directory = $this->getDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossibile creare la cartella uploads.');
        }

        $extension = $file->guessExtension() ?: 'jpg';
        $filename = sprintf('%s_%s.%s', $prefix, bin2hex(random_bytes(4)) . time(), $extension);
        $file->move($directory, $filename);

        return $filename;
    }

    public function delete(?string $filename): void
    {
        if ($this->exists($filename)) {
            @unlink($this->getDirectory() . $filename);
        }
    }

    /**
     * Il nome viene dalla configurazione: niente percorsi, solo un file in uploads/.
     */
    private function isSafeName(?string $filename): bool
    {
        return is_string($filename) && $filename !== '' && basename($filename) === $filename && $filename[0] !== '.';
    }
}

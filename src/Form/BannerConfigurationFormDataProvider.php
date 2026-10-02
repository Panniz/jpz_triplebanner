<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class BannerConfigurationFormDataProvider implements FormDataProviderInterface
{
    public function __construct(private readonly DataConfigurationInterface $dataConfiguration)
    {
    }

    public function getData(): array
    {
        return $this->dataConfiguration->getConfiguration();
    }

    public function setData(array $data): array
    {
        return $this->dataConfiguration->updateConfiguration($data);
    }
}

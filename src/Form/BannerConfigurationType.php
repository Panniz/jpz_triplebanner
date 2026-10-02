<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Form;

use Jpz\TripleBanner\Config\BannerKeys;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Form della pagina di configurazione: un sotto-form per banner.
 */
final class BannerConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (BannerKeys::banners() as $banner) {
            $builder->add('banner_' . $banner, BannerType::class, [
                'label' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.Jpztriplebanner.Admin',
        ]);
    }
}

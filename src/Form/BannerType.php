<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Form;

use Jpz\TripleBanner\Image\BannerImageStorage;
use PrestaShopBundle\Form\Admin\Type\FormattedTextareaType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Campi di un singolo banner.
 */
final class BannerType extends AbstractType
{
    public function __construct(private readonly CategoryChoiceProvider $categoryChoiceProvider)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $imageConstraint = new Image(
            maxSize: BannerImageStorage::MAX_SIZE,
            mimeTypes: BannerImageStorage::MIME_TYPES,
            mimeTypesMessage: 'Formato non valido: sono consentiti JPG, PNG, GIF e WebP.',
        );

        $builder
            ->add('image_desktop', FileType::class, [
                'label' => 'Immagine desktop',
                'required' => false,
                'constraints' => [$imageConstraint],
                'help' => 'Usata su schermi da 768px in su, e ovunque se manca l\'immagine mobile.',
            ])
            ->add('remove_image_desktop', CheckboxType::class, [
                'label' => 'Rimuovi l\'immagine desktop',
                'required' => false,
            ])
            ->add('image_mobile', FileType::class, [
                'label' => 'Immagine mobile',
                'required' => false,
                'constraints' => [$imageConstraint],
                'help' => 'Facoltativa: usata sotto i 768px al posto di quella desktop.',
            ])
            ->add('remove_image_mobile', CheckboxType::class, [
                'label' => 'Rimuovi l\'immagine mobile',
                'required' => false,
            ])
            ->add('text', TranslatableType::class, [
                'label' => 'Testo',
                'type' => FormattedTextareaType::class,
                'required' => false,
                'options' => ['required' => false],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Categoria collegata',
                'choices' => $this->categoryChoiceProvider->getChoices(),
                'placeholder' => '-- Nessuna categoria --',
                'required' => false,
            ])
            ->add('query_params', TranslatableType::class, [
                'label' => 'Parametri aggiuntivi del link',
                'type' => TextType::class,
                'required' => false,
                'options' => ['required' => false],
                'help' => 'Query string aggiunta al link della categoria, ad esempio q=Taglia-42.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.Jpztriplebanner.Admin',
        ]);
    }
}

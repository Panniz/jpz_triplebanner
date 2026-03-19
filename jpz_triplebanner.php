<?php

use League\Uri\Modifier;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

class Jpz_TripleBanner extends Module
{
    protected $config_form = false;
    protected $upload_dir_path;
    protected $upload_dir_url;

    public const CONFIG_PREFIX = 'JPZ_TRIPLEBANNER_';

    // Config keys for Banner 1
    public const B1_IMAGE = self::CONFIG_PREFIX . 'B1_IMAGE';
    public const B1_TEXT = self::CONFIG_PREFIX . 'B1_TEXT';
    public const B1_CATEGORY = self::CONFIG_PREFIX . 'B1_CATEGORY';
    public const B1_QUERY_PARAMS = self::CONFIG_PREFIX . 'B1_QUERY_PARAMS';

    // Config keys for Banner 2
    public const B2_IMAGE = self::CONFIG_PREFIX . 'B2_IMAGE';
    public const B2_TEXT = self::CONFIG_PREFIX . 'B2_TEXT';
    public const B2_CATEGORY = self::CONFIG_PREFIX . 'B2_CATEGORY';
    public const B2_QUERY_PARAMS = self::CONFIG_PREFIX . 'B2_QUERY_PARAMS';

    // Config keys for Banner 3
    public const B3_IMAGE = self::CONFIG_PREFIX . 'B3_IMAGE';
    public const B3_TEXT = self::CONFIG_PREFIX . 'B3_TEXT';
    public const B3_CATEGORY = self::CONFIG_PREFIX . 'B3_CATEGORY';
    public const B3_QUERY_PARAMS = self::CONFIG_PREFIX . 'B3_QUERY_PARAMS';


    public function __construct()
    {
        $this->name = 'jpz_triplebanner';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Jacopo Zane';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Triple Banner', [], 'Modules.Jpztriplebanner.Admin');
        $this->description = $this->trans('Aggiunge tre banner personalizzabili alla homepage con immagine, testo e link a categoria.', [], 'Modules.Jpztriplebanner.Admin');

        $this->ps_versions_compliancy = ['min' => '8.2.0', 'max' => _PS_VERSION_];

        $this->upload_dir_path = $this->getLocalPath() . '/uploads/';
        $this->upload_dir_url = $this->getPathUri() . '/uploads/';
    }

    public function install(): bool
    {
        if (
            !parent::install() ||
            !$this->registerHook('displayHome')
        ) {
            return false;
        }

        // Crea la directory per gli upload se non esiste
        if (!file_exists($this->upload_dir_path)) {
            mkdir($this->upload_dir_path, 0755, true);
        }

        // Valori di default (vuoti)
        $languages = Language::getLanguages(false);
        $default_text_values = [];
        foreach ($languages as $lang) {
            $default_text_values[$lang['id_lang']] = '';
        }

        Configuration::updateValue(self::B1_IMAGE, '');
        Configuration::updateValue(self::B1_TEXT, $default_text_values, true);
        Configuration::updateValue(self::B1_CATEGORY, 0);
        Configuration::updateValue(self::B1_QUERY_PARAMS, '');

        Configuration::updateValue(self::B2_IMAGE, '');
        Configuration::updateValue(self::B2_TEXT, $default_text_values, true);
        Configuration::updateValue(self::B2_CATEGORY, 0);
        Configuration::updateValue(self::B2_QUERY_PARAMS, '');

        Configuration::updateValue(self::B3_IMAGE, '');
        Configuration::updateValue(self::B3_TEXT, $default_text_values, true);
        Configuration::updateValue(self::B3_CATEGORY, 0);
        Configuration::updateValue(self::B3_QUERY_PARAMS, '');

        return true;
    }

    public function uninstall(): bool
    {
        Configuration::deleteByName(self::B1_IMAGE);
        Configuration::deleteByName(self::B1_TEXT);
        Configuration::deleteByName(self::B1_CATEGORY);
        Configuration::deleteByName(self::B1_QUERY_PARAMS);

        Configuration::deleteByName(self::B2_IMAGE);
        Configuration::deleteByName(self::B2_TEXT);
        Configuration::deleteByName(self::B2_CATEGORY);
        Configuration::deleteByName(self::B2_QUERY_PARAMS);

        Configuration::deleteByName(self::B3_IMAGE);
        Configuration::deleteByName(self::B3_TEXT);
        Configuration::deleteByName(self::B3_CATEGORY);
        Configuration::deleteByName(self::B3_QUERY_PARAMS);

        // Opzionale: cancellare le immagini caricate e la cartella uploads
        // $files = glob($this->upload_dir_path . '*');
        // foreach ($files as $file) {
        //     if (is_file($file)) {
        //         unlink($file);
        //     }
        // }
        // if (is_dir($this->upload_dir_path)) {
        //     rmdir($this->upload_dir_path);
        // }

        return parent::uninstall();
    }

    public function getContent(): string
    {
        $output = '';
        if (Tools::isSubmit('submitJpztriplebannerModule')) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderForm();
    }

    /**
     * Crea il form di configurazione
     */
    protected function renderForm(): string
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitJpztriplebannerModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Definisce la struttura del form di configurazione
     */
    protected function getConfigForm(): array
    {
        $categories = Category::getCategories($this->context->language->id, true, false);
        $category_options = [];
        foreach ($categories as $category) {
            $category_options[] = [
                'id_option' => $category['id_category'],
                'name' => $category['name'] . ' (ID: ' . $category['id_category'] . ')',
            ];
        }

        $current_image_b1 = Configuration::get(self::B1_IMAGE);
        $image_desc_b1 = '';
        if ($current_image_b1 && file_exists($this->upload_dir_path . $current_image_b1)) {
            $image_desc_b1 = '<img src="' . $this->upload_dir_url . $current_image_b1 . '" style="max-height:100px; margin-top:10px;" /><br/>' .
                $this->trans('Immagine attuale: %s', [$current_image_b1], 'Modules.Jpztriplebanner.Admin');
        }

        $current_image_b2 = Configuration::get(self::B2_IMAGE);
        $image_desc_b2 = '';
        if ($current_image_b2 && file_exists($this->upload_dir_path . $current_image_b2)) {
            $image_desc_b2 = '<img src="' . $this->upload_dir_url . $current_image_b2 . '" style="max-height:100px; margin-top:10px;" /><br/>' .
                $this->trans('Immagine attuale: %s', [$current_image_b2], 'Modules.Jpztriplebanner.Admin');
        }

        $current_image_b3 = Configuration::get(self::B3_IMAGE);
        $image_desc_b3 = '';
        if ($current_image_b3 && file_exists($this->upload_dir_path . $current_image_b3)) {
            $image_desc_b3 = '<img src="' . $this->upload_dir_url . $current_image_b3 . '" style="max-height:100px; margin-top:10px;" /><br/>' .
                $this->trans('Immagine attuale: %s', [$current_image_b3], 'Modules.Jpztriplebanner.Admin');
        }


        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Impostazioni Banner', [], 'Modules.Jpztriplebanner.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    // Banner 1
                    [
                        'type' => 'html',
                        'name' => 'banner1_separator',
                        'html_content' => '<h3>' . $this->trans('Banner 1', [], 'Modules.Jpztriplebanner.Admin') . '</h3>',
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->trans('Immagine Banner 1', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B1_IMAGE,
                        'desc' => $this->trans('Carica un\'immagine per il primo banner. Formati consigliati: JPG, PNG, GIF.', [], 'Modules.Jpztriplebanner.Admin') . '<br/>' . $image_desc_b1,
                        'required' => false,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Testo Banner 1', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B1_TEXT,
                        'lang' => true,
                        'autoload_rte' => true, // Attiva l'editor WYSIWYG
                        'desc' => $this->trans('Inserisci il testo per il primo banner. Puoi usare HTML.', [], 'Modules.Jpztriplebanner.Admin'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Categoria Banner 1', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B1_CATEGORY,
                        'desc' => $this->trans('Seleziona una categoria da collegare al primo banner.', [], 'Modules.Jpztriplebanner.Admin'),
                        'options' => [
                            'query' => $category_options,
                            'id' => 'id_option',
                            'name' => 'name',
                            'default' => [
                                'value' => 0,
                                'label' => $this->trans('-- Seleziona una categoria --', [], 'Modules.Jpztriplebanner.Admin')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Additional query params', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B1_QUERY_PARAMS,
                        'lang' => true,
                        'desc' => $this->trans('Add eventual additional query params.', [], 'Modules.Jpztriplebanner.Admin'),
                    ],
                    // Banner 2
                    [
                        'type' => 'html',
                        'name' => 'banner2_separator',
                        'html_content' => '<hr style="margin-top:30px; margin-bottom:20px;"><h3>' . $this->trans('Banner 2', [], 'Modules.Jpztriplebanner.Admin') . '</h3>',
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->trans('Immagine Banner 2', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B2_IMAGE,
                        'desc' => $this->trans('Carica un\'immagine per il secondo banner. Formati consigliati: JPG, PNG, GIF.', [], 'Modules.Jpztriplebanner.Admin') . '<br/>' . $image_desc_b2,
                        'required' => false,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Testo Banner 2', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B2_TEXT,
                        'lang' => true,
                        'autoload_rte' => true,
                        'desc' => $this->trans('Inserisci il testo per il secondo banner. Puoi usare HTML.', [], 'Modules.Jpztriplebanner.Admin'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Categoria Banner 2', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B2_CATEGORY,
                        'desc' => $this->trans('Seleziona una categoria da collegare al secondo banner.', [], 'Modules.Jpztriplebanner.Admin'),
                        'options' => [
                            'query' => $category_options,
                            'id' => 'id_option',
                            'name' => 'name',
                            'default' => [
                                'value' => 0,
                                'label' => $this->trans('-- Seleziona una categoria --', [], 'Modules.Jpztriplebanner.Admin')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Additional query params', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B2_QUERY_PARAMS,
                        'lang' => true,
                        'desc' => $this->trans('Add eventual additional query params.', [], 'Modules.Jpztriplebanner.Admin'),
                    ],
                    // Banner 3
                    [
                        'type' => 'html',
                        'name' => 'banner2_separator',
                        'html_content' => '<hr style="margin-top:30px; margin-bottom:20px;"><h3>' . $this->trans('Banner 3', [], 'Modules.Jpztriplebanner.Admin') . '</h3>',
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->trans('Immagine Banner 3', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B3_IMAGE,
                        'desc' => $this->trans('Carica un\'immagine per il terzo banner. Formati consigliati: JPG, PNG, GIF.', [], 'Modules.Jpztriplebanner.Admin') . '<br/>' . $image_desc_b3,
                        'required' => false,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Testo Banner 3', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B3_TEXT,
                        'lang' => true,
                        'autoload_rte' => true,
                        'desc' => $this->trans('Inserisci il testo per il terzo banner. Puoi usare HTML.', [], 'Modules.Jpztriplebanner.Admin'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Categoria Banner 3', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B3_CATEGORY,
                        'desc' => $this->trans('Seleziona una categoria da collegare al terzo banner.', [], 'Modules.Jpztriplebanner.Admin'),
                        'options' => [
                            'query' => $category_options,
                            'id' => 'id_option',
                            'name' => 'name',
                            'default' => [
                                'value' => 0,
                                'label' => $this->trans('-- Seleziona una categoria --', [], 'Modules.Jpztriplebanner.Admin')
                            ]
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Additional query params', [], 'Modules.Jpztriplebanner.Admin'),
                        'name' => self::B3_QUERY_PARAMS,
                        'lang' => true,
                        'desc' => $this->trans('Add eventual additional query params.', [], 'Modules.Jpztriplebanner.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Salva', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    /**
     * Ottiene i valori di configurazione per il form
     */
    protected function getConfigFormValues(): array
    {
        $languages = Language::getLanguages(false);
        $values = [];

        // Banner 1
        $values[self::B1_IMAGE] = Configuration::get(self::B1_IMAGE); // Non lo usiamo per il file input, ma serve per il postProcess
        foreach ($languages as $lang) {
            $values[self::B1_TEXT][$lang['id_lang']] = Configuration::get(self::B1_TEXT, $lang['id_lang']);
        }
        $values[self::B1_CATEGORY] = Configuration::get(self::B1_CATEGORY);
        $values[self::B1_QUERY_PARAMS] = Configuration::get(self::B1_QUERY_PARAMS);

        // Banner 2
        $values[self::B2_IMAGE] = Configuration::get(self::B2_IMAGE);
        foreach ($languages as $lang) {
            $values[self::B2_TEXT][$lang['id_lang']] = Configuration::get(self::B2_TEXT, $lang['id_lang']);
        }
        $values[self::B2_CATEGORY] = Configuration::get(self::B2_CATEGORY);
        $values[self::B2_QUERY_PARAMS] = Configuration::get(self::B2_QUERY_PARAMS);

        // Banner 3
        $values[self::B3_IMAGE] = Configuration::get(self::B3_IMAGE);
        foreach ($languages as $lang) {
            $values[self::B3_TEXT][$lang['id_lang']] = Configuration::get(self::B3_TEXT, $lang['id_lang']);
        }
        $values[self::B3_CATEGORY] = Configuration::get(self::B3_CATEGORY);
        $values[self::B3_QUERY_PARAMS] = Configuration::get(self::B3_QUERY_PARAMS);

        return $values;
    }

    /**
     * Processa e salva i dati del form
     */
    protected function postProcess(): string
    {
        $errors = [];
        // $form_values = $this->getConfigFormValues(); // Per ottenere i valori esistenti delle immagini
        $languages = Language::getLanguages(false);

        // Gestione Upload Immagini
        $this->handleImageUpload(self::B1_IMAGE, 'banner1', $errors);
        $this->handleImageUpload(self::B2_IMAGE, 'banner2', $errors);
        $this->handleImageUpload(self::B3_IMAGE, 'banner3', $errors);

        // Salvataggio Testi Traducibili
        $text_b1 = [];
        $text_b2 = [];
        $text_b3 = [];
        $qb1 = [];
        $qb2 = [];
        $qb3 = [];
        foreach ($languages as $lang) {
            $text_b1[$lang['id_lang']] = Tools::getValue(self::B1_TEXT . '_' . $lang['id_lang']);
            $text_b2[$lang['id_lang']] = Tools::getValue(self::B2_TEXT . '_' . $lang['id_lang']);
            $text_b3[$lang['id_lang']] = Tools::getValue(self::B3_TEXT . '_' . $lang['id_lang']);
            $qb1[$lang['id_lang']] = Tools::getValue(self::B1_QUERY_PARAMS . '_' . $lang['id_lang']);
            $qb2[$lang['id_lang']] = Tools::getValue(self::B2_QUERY_PARAMS . '_' . $lang['id_lang']);
            $qb3[$lang['id_lang']] = Tools::getValue(self::B3_QUERY_PARAMS . '_' . $lang['id_lang']);
        }
        Configuration::updateValue(self::B1_TEXT, $text_b1, true); // true per permettere HTML
        Configuration::updateValue(self::B2_TEXT, $text_b2, true);
        Configuration::updateValue(self::B3_TEXT, $text_b3, true);
        // Salvataggio query params
        Configuration::updateValue(self::B1_QUERY_PARAMS, $qb1);
        Configuration::updateValue(self::B2_QUERY_PARAMS, $qb2);
        Configuration::updateValue(self::B3_QUERY_PARAMS, $qb3);

        // Salvataggio Categorie
        Configuration::updateValue(self::B1_CATEGORY, (int)Tools::getValue(self::B1_CATEGORY));
        Configuration::updateValue(self::B2_CATEGORY, (int)Tools::getValue(self::B2_CATEGORY));
        Configuration::updateValue(self::B3_CATEGORY, (int)Tools::getValue(self::B3_CATEGORY));


        if (count($errors)) {
            return $this->displayError(implode('<br />', $errors));
        }
        return $this->displayConfirmation($this->trans('Impostazioni aggiornate con successo.', [], 'Admin.Notifications.Success'));
    }

    /**
     * Funzione helper per gestire l'upload di una singola immagine
     */
    protected function handleImageUpload(string $configKey, string $fileInputPrefix, array &$errors): void
    {
        if (isset($_FILES[$configKey]) && !empty($_FILES[$configKey]['tmp_name'])) {
            $file = $_FILES[$configKey];
            if ($file['error']) {
                $errors[] = $this->trans('Errore durante il caricamento dell\'immagine per %s: Errore %s', [$configKey, $file['error']], 'Modules.Jpztriplebanner.Admin');
                return;
            }

            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed_mime_types)) {
                $errors[] = $this->trans('Formato file non valido per %s. Sono consentiti solo JPG, PNG, GIF.', [$configKey], 'Modules.Jpztriplebanner.Admin');
                return;
            }

            $max_file_size = 5 * 1024 * 1024; // 2MB
            if ($file['size'] > $max_file_size) {
                $errors[] = $this->trans('Il file per %s è troppo grande. La dimensione massima è 2MB.', [$configKey], 'Modules.Jpztriplebanner.Admin');
                return;
            }

            // Cancella vecchia immagine se esiste
            $old_image = Configuration::get($configKey);
            if ($old_image && file_exists($this->upload_dir_path . $old_image)) {
                unlink($this->upload_dir_path . $old_image);
            }

            // Crea un nome univoco per il file
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = $fileInputPrefix . '_' . time() . '.' . Tools::strtolower($extension);
            $destination = $this->upload_dir_path . $new_filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = $this->trans('Impossibile spostare il file caricato per %s nella directory di destinazione.', [$configKey], 'Modules.Jpztriplebanner.Admin');
                return;
            }
            Configuration::updateValue($configKey, $new_filename);
        } elseif (Tools::isSubmit('delete_' . $configKey)) { // Gestione opzionale per cancellare l'immagine
            $old_image = Configuration::get($configKey);
            if ($old_image && file_exists($this->upload_dir_path . $old_image)) {
                unlink($this->upload_dir_path . $old_image);
            }
            Configuration::updateValue($configKey, '');
        }
    }


    /**
     * Hook per visualizzare i banner sulla homepage
     */
    public function hookDisplayHome(array $params): string
    {
        $this->context->controller->registerStylesheet('jpz-triplebanner', $this->getPathUri() . 'views/css/main.css');

        $id_lang = $this->context->language->id;
        $banners_data = [];

        $img_b1 = Configuration::get(self::B1_IMAGE);
        $text_b1 = Configuration::get(self::B1_TEXT, $id_lang);
        $cat_id_b1 = (int)Configuration::get(self::B1_CATEGORY);
        $cat_link_b1 = '';
        $qp1 = str_replace('/', '\/', trim((string)Configuration::get(self::B1_QUERY_PARAMS, $id_lang)));
        $cat_name_b1 = '';

        if ($cat_id_b1 > 0) {
            $category1 = new Category($cat_id_b1, $id_lang);
            if (Validate::isLoadedObject($category1)) {
                $catUri = Modifier::wrap($this->context->link->getCategoryLink($category1))
                    ->appendQuery($qp1);

                $cat_link_b1 = $catUri->toString();
                $cat_name_b1 = $category1->name;
            }
        }

        if ($img_b1 || $text_b1) {
            $banners_data[] = [
                'image_url' => ($img_b1 && file_exists($this->upload_dir_path . $img_b1)) ? $this->upload_dir_url . $img_b1 : null,
                'text' => $text_b1,
                'category_link' => $cat_link_b1,
                'category_name' => $cat_name_b1,
            ];
        }

        $img_b2 = Configuration::get(self::B2_IMAGE);
        $text_b2 = Configuration::get(self::B2_TEXT, $id_lang);
        $cat_id_b2 = (int)Configuration::get(self::B2_CATEGORY);
        $cat_link_b2 = '';
        $cat_name_b2 = '';
        $qp2 = str_replace('/', '\/', trim((string)Configuration::get(self::B2_QUERY_PARAMS, $id_lang)));

        if ($cat_id_b2 > 0) {
            $category2 = new Category($cat_id_b2, $id_lang);
            if (Validate::isLoadedObject($category2)) {

                $catUri = Modifier::wrap($this->context->link->getCategoryLink($category2))
                    ->appendQuery($qp2);

                $cat_link_b2 = $catUri->toString();
                $cat_name_b2 = $category2->name;
            }
        }

        if ($img_b2 || $text_b2) {
            $banners_data[] = [
                'image_url' => ($img_b2 && file_exists($this->upload_dir_path . $img_b2)) ? $this->upload_dir_url . $img_b2 : null,
                'text' => $text_b2,
                'category_link' => $cat_link_b2,
                'category_name' => $cat_name_b2,
            ];
        }

        $img_b3 = Configuration::get(self::B3_IMAGE);
        $text_b3 = Configuration::get(self::B3_TEXT, $id_lang);
        $cat_id_b3 = (int)Configuration::get(self::B3_CATEGORY);
        $cat_link_b3 = '';
        $cat_name_b3 = '';
        $qp3 = str_replace('/', '\/', trim((string)Configuration::get(self::B3_QUERY_PARAMS, $id_lang)));

        if ($cat_id_b3 > 0) {
            $category3 = new Category($cat_id_b3, $id_lang);
            if (Validate::isLoadedObject($category3)) {
                $catUri = Modifier::wrap($this->context->link->getCategoryLink($category3))
                    ->appendQuery($qp3);

                $cat_link_b3 = $catUri->toString();
                $cat_name_b3 = $category3->name;
            }
        }

        if ($img_b3 || $text_b3) {
            $banners_data[] = [
                'image_url' => ($img_b3 && file_exists($this->upload_dir_path . $img_b3)) ? $this->upload_dir_url . $img_b3 : null,
                'text' => $text_b3,
                'category_link' => $cat_link_b3,
                'category_name' => $cat_name_b3,
            ];
        }


        if (empty($banners_data)) {
            return '';
        }

        $this->context->smarty->assign('banners', $banners_data);
        return $this->display(__FILE__, 'views/templates/hook/displayHome.tpl');
    }
}

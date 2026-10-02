<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Install;

use Configuration;
use Jpz\TripleBanner\Config\BannerKeys;
use Jpz\TripleBanner\Controller\Admin\ConfigurationController;
use Jpz\TripleBanner\Image\BannerImageStorage;
use Language;
use Module;
use Tab;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Configurazione di default, cartella uploads e Tab dei permessi.
 *
 * Il Tab è nascosto dal menu (`id_parent = -1`) ma serve: è il suo
 * `class_name` che il voter dei permessi Symfony controlla quando si apre la
 * pagina di configurazione (vedi `_legacy_controller` in config/routes.yml).
 */
class Installer
{
    public function install(Module $module): bool
    {
        return $this->installConfiguration() && $this->installUploadDirectory() && $this->installTab($module);
    }

    public function uninstall(): bool
    {
        foreach (BannerKeys::all() as $key) {
            Configuration::deleteByName($key);
        }

        return $this->uninstallTab();
    }

    /**
     * Scrive solo le chiavi mancanti: i valori già salvati restano com'erano.
     */
    public function installConfiguration(): bool
    {
        $emptyLocalized = array_fill_keys(Language::getIDs(false), '');

        foreach (BannerKeys::banners() as $banner) {
            foreach (BannerKeys::FIELDS as $field) {
                $key = BannerKeys::key($banner, $field);
                if (Configuration::hasKey($key) || Configuration::hasKey($key, (int) Configuration::get('PS_LANG_DEFAULT'))) {
                    continue;
                }

                $value = match (true) {
                    in_array($field, BannerKeys::LANG_FIELDS, true) => $emptyLocalized,
                    $field === BannerKeys::CATEGORY => 0,
                    default => '',
                };

                if (!Configuration::updateValue($key, $value, $field === BannerKeys::TEXT)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function installUploadDirectory(): bool
    {
        $directory = (new BannerImageStorage())->getDirectory();

        return is_dir($directory) || mkdir($directory, 0755, true) || is_dir($directory);
    }

    public function installTab(Module $module): bool
    {
        if (Tab::getIdFromClassName(ConfigurationController::TAB_CLASS_NAME)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = ConfigurationController::TAB_CLASS_NAME;
        $tab->route_name = 'jpz_triplebanner_configuration';
        $tab->module = $module->name;
        $tab->id_parent = -1;
        $tab->active = 1;
        $tab->name = array_fill_keys(Language::getIDs(false), $module->displayName);

        return $tab->add();
    }

    private function uninstallTab(): bool
    {
        $idTab = (int) Tab::getIdFromClassName(ConfigurationController::TAB_CLASS_NAME);
        if (!$idTab) {
            return true;
        }

        $tab = new Tab($idTab);

        return Validate::isLoadedObject($tab) ? $tab->delete() : true;
    }
}

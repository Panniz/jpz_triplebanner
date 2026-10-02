<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use Jpz\TripleBanner\Install\Installer;

/**
 * 2.0.0: configurazione in una pagina Symfony e immagine mobile per banner.
 *
 * L'immagine esistente resta sulla chiave `B{N}_IMAGE` e diventa quella
 * desktop, quindi non c'è nulla da spostare: si aggiungono le chiavi mancanti
 * (`B{N}_IMAGE_MOBILE`) e il Tab che regola i permessi della nuova pagina.
 */
function upgrade_module_2_0_0(Jpz_TripleBanner $module): bool
{
    $installer = new Installer();

    return $installer->installConfiguration()
        && $installer->installUploadDirectory()
        && $installer->installTab($module);
}

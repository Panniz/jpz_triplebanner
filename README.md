# jpz_triplebanner

Triple Banner per Al Principe Calzature: tre banner in homepage (hook `displayHome`), ciascuno con
immagine desktop, immagine mobile facoltativa, testo multilingua e link a una categoria con
parametri aggiuntivi. Target PrestaShop 9, PHP 8.1+.

## Installazione

Il modulo carica le proprie classi solo da `vendor/autoload.php`, che non è versionato:

```bash
cd dev/modules/jpz_triplebanner
composer install --no-dev
```

Da una 1.x basta caricare i file e lanciare l'aggiornamento del modulo: `upgrade/upgrade-2.0.0.php`
crea il Tab dei permessi e le chiavi delle immagini mobile. L'immagine esistente di ogni banner
diventa quella desktop.

## Struttura

- `config/routes.yml`, `src/Controller/Admin/ConfigurationController.php`: pagina di configurazione
  Symfony (`getContent()` reindirizza lì). Permessi sul Tab nascosto `AdminJpzTripleBannerConfiguration`.
- `config/services.yml`: form handler (`PrestaShop\PrestaShop\Core\Form\Handler`), data provider e
  data configuration, come da guida PrestaShop per le pagine di configurazione moderne.
- `src/Form/`: form type (`BannerConfigurationType` → un `BannerType` per banner), lettura e
  scrittura della configurazione, scelte delle categorie.
- `src/Image/BannerImageStorage.php`: file in `uploads/` (JPG, PNG, GIF, WebP).
- `src/Presenter/BannerPresenter.php`: dati per il front office.
- `views/templates/hook/_picture.tpl`: `<picture>` con la versione mobile come `<source>` sotto i
  768px, `width`/`height` intrinseci e `loading="lazy"`. I template del tema possono includerlo con
  `{include file='module:jpz_triplebanner/views/templates/hook/_picture.tpl' image=$banner.image alt=...}`.

{**
 * Immagine di un banner: <picture> con la variante mobile come <source>, se c'è.
 *
 * Parametri: $image (banner.image), $alt, $loading (default "lazy").
 *}
{if !isset($loading)}{assign var=loading value='lazy'}{/if}
<picture>
  {if $image.mobile}
    <source
      media="{$image.mobile_media|escape:'htmlall':'UTF-8'}"
      srcset="{$image.mobile.url|escape:'htmlall':'UTF-8'}"
      {if $image.mobile.width}width="{$image.mobile.width|intval}" height="{$image.mobile.height|intval}"{/if}
    >
  {/if}
  <img
    src="{$image.desktop.url|escape:'htmlall':'UTF-8'}"
    {if $image.desktop.width}width="{$image.desktop.width|intval}" height="{$image.desktop.height|intval}"{/if}
    alt="{$alt|escape:'htmlall':'UTF-8'}"
    loading="{$loading|escape:'htmlall':'UTF-8'}"
    decoding="async"
    {if isset($class)}class="{$class|escape:'htmlall':'UTF-8'}"{/if}
  >
</picture>

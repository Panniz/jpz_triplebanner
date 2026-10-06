{if !empty($banners)}
    <section id="jpztriplebanner" class="jpztriplebanner full">
        {foreach from=$banners item=banner}
            {if $banner.image && $banner.text}
                <div class="jpztriplebanner__banner">
                    {if $banner.category_link}
                        <a href="{$banner.category_link|escape:'htmlall':'UTF-8'}">
                            {include file='module:jpz_triplebanner/views/templates/hook/_picture.tpl'
                                image=$banner.image
                                alt=$banner.category_name|default:{l s='Banner' d='Modules.Jpztriplebanner.Front'}}
                        </a>
                    {else}
                        <div class="jpztriplebanner__image">
                            {include file='module:jpz_triplebanner/views/templates/hook/_picture.tpl'
                                image=$banner.image
                                alt={l s='Banner' d='Modules.Jpztriplebanner.Front'}}
                        </div>
                    {/if}

                    <div class="jpztriplebanner__content">
                        <div>
                            {$banner.text nofilter}
                        </div>
                        {if $banner.category_link && $banner.category_name}
                            <div class="jpztriplebanner__link">
                                <a href="{$banner.category_link|escape:'htmlall':'UTF-8'}" class="btn btn--primary">
                                    {l s='Scopri di più su' d='Modules.Jpztriplebanner.Front'}
                                    {$banner.category_name|escape:'htmlall':'UTF-8'}
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>
            {/if}
        {/foreach}
    </section>
{/if}

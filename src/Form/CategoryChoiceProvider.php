<?php

declare(strict_types=1);

namespace Jpz\TripleBanner\Form;

use Category;
use PrestaShop\PrestaShop\Core\Context\LanguageContext;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Categorie attive come scelte di una select, indentate per livello.
 */
final class CategoryChoiceProvider implements FormChoiceProviderInterface
{
    public function __construct(private readonly LanguageContext $languageContext)
    {
    }

    /**
     * @return array<string, int>
     */
    public function getChoices(): array
    {
        $choices = [];
        $tree = Category::getNestedCategories(null, $this->languageContext->getId(), true);
        $this->flatten(is_array($tree) ? $tree : [], $choices);

        return $choices;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<string, int> $choices
     */
    private function flatten(array $nodes, array &$choices): void
    {
        foreach ($nodes as $node) {
            $depth = max(0, (int) $node['level_depth'] - 1);
            $label = str_repeat('— ', $depth) . $node['name'] . ' (ID: ' . $node['id_category'] . ')';
            $choices[$label] = (int) $node['id_category'];

            if (!empty($node['children'])) {
                $this->flatten($node['children'], $choices);
            }
        }
    }
}

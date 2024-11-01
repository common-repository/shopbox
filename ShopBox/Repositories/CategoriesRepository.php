<?php
namespace Shopbox\Repositories;

class CategoriesRepository
{

    public function getByShopBoxId($shopBoxId)
    {
        $categories = get_categories(
            array(
                'taxonomy'     => 'product_cat',
                'orderby'    => 'id',
                'order'      => 'DESC',
                'hide_empty' => '0',
                'meta_query' => array(
                    array(
                        'key'     => '_shopbox_id',
                        'compare' => '=',
                        'value' => $shopBoxId
                    )
                )
            )
        );

        if ($categories) {
            return $categories[0];
        }

    }

    public function getAllCategories()
    {
		return get_categories(
            array(
                'taxonomy'     => 'product_cat',
                'orderby'    => 'id',
                'order'      => 'DESC',
                'hide_empty' => '0'
            )
        );
    }

    public function getSynchedCategories()
    {
        return get_categories(
            array(
                'taxonomy'     => 'product_cat',
                'orderby'    => 'id',
                'order'      => 'DESC',
                'hide_empty' => '0',
                'meta_query' => array(
                    array(
                        'key'     => '_shopbox_id',
                        'compare' => 'EXISTS'
                    )
                )
            )
        );
    }


    public function getUnsynchedCategories()
    {
        return get_categories(
            array(
                'taxonomy'     => 'product_cat',
                'orderby'    => 'id',
                'order'      => 'DESC',
                'hide_empty' => '0',
                'meta_query' => array(
                    array(
                        'key'     => '_shopbox_id',
                        'compare' => 'NOT EXISTS'
                    )
                )
            )
        );
    }

    public function getCategoriesShopboxIds()
    {
        $categories = $this->getSynchedCategories();

        $ids = [];
        foreach ($categories as $category) {
            $sbCategoryId = get_term_meta($category->term_id, '_shopbox_id', true);
            $ids[$category->term_id] = $sbCategoryId;
        }

        return $ids;
    }
}

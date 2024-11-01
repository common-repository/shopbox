<?php
namespace Shopbox\Repositories;

use WP_Query;

class ProductsRepository
{
    public function getAllPagesCount()
    {
        $query = $this->getQuery();
        return $query->max_num_pages;
    }

    public function getUnsynchedPagesCount()
    {
        $query = $this->getQuery(['unsynched'=> true]);
        return $query->max_num_pages;
    }

    public function getAllProducts($page = null)
    {
        $query = $this->getQuery(['page' => $page]);
        return $query->get_posts();
    }

    protected function getQuery($filter = [])
    {
		$args = array(
            'orderby'          => [],
            'post_type'        => 'product',
            'post_status'      => 'publish',
            // 'p' => 180,
        );

        if (!empty($filter['page'])) {
            $args['posts_per_page'] = 1;
            $args['paged'] = $filter['page'];
        } else {
            $args['posts_per_page'] = 1;
        }
        if (!empty($filter['posts_per_page'])) {
            $args['posts_per_page'] = $filter['posts_per_page'];
        }
        if (!empty($filter['unsynched'])) {
            $args['meta_query'] = [
                [
                    'key' => '_shopbox_id',
                    // 'value' => '1'
                    'compare' => 'NOT EXISTS'
                    // 'compare' => 'EXISTS'
                ]
            ];
        }

        $query = new WP_Query($args);
        return $query;
    }

    public function getUnsynchedProducts($page = null, $postsPerPage = null)
    {
        $query = $this->getQuery(['unsynched'=> true, 'page' => $page, 'posts_per_page' => $postsPerPage]);
        return $query->get_posts();
    }

    public function getSynchedProducts()
    {
        $args = array(
            'orderby'          => [],
            'post_type'        => 'product',
            'post_status'      => 'publish',
            'nopaging' => true,
            'meta_query' => [
                [
                    'key' => '_shopbox_id',
                    // 'value' => '1'
                    // 'compare' => 'NOT EXISTS'
                    'compare' => 'EXISTS'
                ]
            ]
        );

        $query = new WP_Query($args);
        return $query->get_posts();
    }
}

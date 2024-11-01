<?php
namespace ShopBox\Integrators;

use Wp_Query;

class InventoryImporter
{
    protected function findVariance($product, $varianceValues)
    {

        $variations = $product->get_available_variations();

        $found = false;
        foreach ($variations as $variation) {
            $variationId = $variation['variation_id'];
            $variationObj = new \WC_Product_variation($variationId);
            $attributes = $variationObj->get_variation_attributes();
            sort($attributes);
            $attributes = array_filter($attributes);
            if ($varianceValues == $attributes) {
                $found = $variationObj;
                break;
            }
        }

        return $found;
    }

    public function updateInventory($inventoryArray)
    {
        foreach ($inventoryArray as $item) {
            $args = array(
                'post_type'        => 'product',
                'post_status'      => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_shopbox_id',
                        'value' => $item['product_id'],
                        'compare' => '=',
                    )
                )
             );

            $query = new WP_Query($args);

            $posts = $query->get_posts();
            
            if ($posts) {
                //update post inventory
                $product = wc_get_product($posts[0]->ID);

                if (!isset($item['variance_values']) || !$item['variance_values']) {
                    $currentQuantity = $product->get_stock_quantity($item['quantity']);
                    $product->set_stock_quantity($currentQuantity - $item['quantity']);
                    $product->save();
                } else {
					$varianceValues = $item['variance_values'];
                    sort($varianceValues);
                    
                    if (!empty($item['variance_id'])) {
                        $variance = new \WC_Product_variation($item['variance_id']);
                    } else {
                        $variance = $this->findVariance($product, $varianceValues);
                    }

                    if ($variance) {
                        $currentQuantity = $variance->get_stock_quantity();
                        $variance->set_stock_quantity($currentQuantity - $item['quantity']);
                        $variance->save();
                    }
                }
            }

        }
    }
}

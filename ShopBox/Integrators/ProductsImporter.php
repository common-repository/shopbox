<?php
namespace ShopBox\Integrators;

use ShopBox\Repositories\CategoriesRepository;

class ProductsImporter
{
    public function __construct($api, $settings)
    {
        $this->api = $api;
        $this->settings = $settings;
        $this->categoriesRepository = new CategoriesRepository();
    }

    public function getUnsynchedProducts()
    {
        $tags = $this->api->getTags()->data;
        $sbProducts = [];
        foreach ($tags as $tag) {
            $temp = $this->api->getTagProducts($tag->uid, $this->settings->getCashRegisterId())->data;
            $sbProducts = array_merge($sbProducts, $temp);
        }
        

        $sbProducts = array_filter($sbProducts, function ($sbProduct) {

            $query = new \WP_Query([
                'post_type'        => 'product',
                'meta_query' => [
                    [
                        'key' => '_shopbox_id',
                        'value' => "$sbProduct->product"
                    ]
                ]
            ]);
            
            if ($query->get_posts()) {
                return false;
            }

            return true;
        });

        return $sbProducts;
    }

    public function getProductsInventory()
    {
        $items = $this->api->getProductBranchInventory($this->settings->getBranchId())->data;
        $return = [];
        foreach ($items as $item) {
            $return[$item->uid] = $item;
        }

        return $return;
    }


    public function import()
    {
        $sbProducts = $this->getUnsynchedProducts();
        $inventory = $this->getProductsInventory();
        foreach ($sbProducts as $sbProduct) {
            
            if (!$sbProduct->product0) {
                continue;
            }

            $sbProductId = $sbProduct->product0->uid;
            $postId = wp_insert_post([
                'post_title' => $sbProduct->product0->name,
                'post_type' => 'product',
                'post_status' => 'publish',
                'meta_input' => [
                    '_regular_price' => $sbProduct->selling_price/100,
                    '_price' => $sbProduct->selling_price/100,
                    '_shopbox_import' => 1,
                    '_shopbox_id' => $sbProduct->product0->uid,
                    '_sku' => isset($inventory[$sbProductId]) ? $inventory[$sbProductId]->sku_code:'',
                    '_stock' => isset($inventory[$sbProductId]) ? $inventory[$sbProductId]->quantity:'',
                ]
            ]);
            
            if ($sbProduct->product0->tag0 && $shopboxId = $sbProduct->product0->tag) {
                $category = $this->categoriesRepository->getByShopboxId($shopboxId);
                $termId = null;
                if (!$category) {
                    $category = get_term_by('name', $sbProduct->product0->tag0->name, 'product_cat');
                    
                    if ($category) {
                        $termId = $category->term_id;
                    } else {
                        //create category
                        $category = wp_insert_term(
                            $sbProduct->product0->tag0->name,   // the term 
                            'product_cat' // the taxonomy
                        );
                        
                        if (is_array($category)) {
                            $termId = $category['term_id'];
                            update_term_meta($termId, '_shopbox_id', $sbProduct->product0->tag0->uid);
                        } else {
                            
                        }
                    }
                } else {
                    $termId = $category->term_id;
                }

                if ($termId) {
                    wp_set_object_terms( $postId, $termId, 'product_cat' );
                }

            }

            if ($sbProduct->product0->image0) {
                $imagePath = $sbProduct->product0->image0->image_original;
                try {

                    $imageContent = file_get_contents($imagePath);
                    
                    $imageName = $sbProduct->product0->image0->path;
                    $upload = wp_upload_bits($imageName, null, $imageContent);
                    $imageName = $upload['file'];
                    
                    $wp_filetype = wp_check_filetype($imageName, null );
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title' => sanitize_file_name($imageName),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    $attachId = wp_insert_attachment( $attachment, $imageName, $postId );
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attachData = wp_generate_attachment_metadata( $attachId, $imageName );
                    wp_update_attachment_metadata( $attachId, $attachData );
                    set_post_thumbnail( $postId, $attachId );
                } catch (\Exception $e) {

                }
            }
        }
    }
}

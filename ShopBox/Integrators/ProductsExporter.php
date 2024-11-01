<?php
namespace ShopBox\Integrators;

use ShopBox\Repositories\ProductsRepository;
use ShopBox\Repositories\CategoriesRepository;

class ProductsExporter
{
    public function __construct($api, $settings)
    {
        $this->api = $api;
        $this->settings = $settings;
        $this->productsRepository = new ProductsRepository();
        $this->categoriesRepository = new CategoriesRepository();
        $this->categoriesExporter = new CategoriesExporter($api, $settings);
    }

    public function exportUnsynchedProducts($page = null)
    {
        $products = $this->productsRepository->getUnsynchedProducts();
        $this->exportProducts($products);
    }

    public function exportAllProducts($page = null)
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);
        $products = $this->productsRepository->getAllProducts($page);
        $this->exportProducts($products, true);
    }

    public function exportProducts($products, $exportAll = false)
    {
        $cashRegister = $this->api->getCashRegisterDetails($this->settings->getCashRegisterId());
        
        $categoriesIds = $this->categoriesRepository->getCategoriesShopboxIds();
        $vatPercentage = $this->settings->vatPercentage();

        foreach ($products as $product) {
            try {
                $postMeta = get_post_meta($product->ID);
                
                $imageId = get_post_thumbnail_id($product->ID);
                
                $return = $this->getPrimaryCategory($product->ID, 'product_cat');
                $term = $return['primary_category'];
                if ($term) {
                    $tagId = $categoriesIds[$term->term_id];
                } else {
                    $tagId = $this->settings->getTagId();
                }

                $p = wc_get_product($product->ID);
                if (!$p) {
                    continue;
                }

                $sbVariants = [];
                if ( $p->is_type( 'variable' ) ) {
                    $productVariants = $p->get_children();
                    $attributes = $p->get_attributes();
                    $variantNameMap = [];
                    $variantTypesMap = [];
                    foreach ($attributes as $key => $data) {
                        $taxonomy = get_taxonomy($key);
                        $variantTypesMap[$key] = $taxonomy->labels->singular_name ?? $taxonomy->labels->name ?? $key;
                        $terms = wc_get_product_terms( $p->id, $key, array( 'fields' => 'all' ) );
                        $variantNameMap[$key] = [];
                        foreach ($terms as $term) {
                            $variantNameMap[$key][$term->slug] = $term->name;
                        }
                    }
                    
                    // echo "<pre>";
                    // var_dump($productVariants);
                    // echo "</pre>";
                    // die;

                    $variantValues = [];
                    $variantPrices = [];
                    foreach ($productVariants as $productVariantId) {
                        $productVariant = new \WC_Product_Variation($productVariantId);
                        $values = [];
                        foreach ($productVariant->attributes as $attribute => $value) {
                            if (!$value) {
                                continue;
                            }
                            $variantType = str_replace('attribute_', '', $attribute);
                            $name = !empty($variantNameMap[$variantType][$value]) ? $variantNameMap[$variantType][$value] : $value;
                            
                            if (!isset($variantValues[$variantType])) {
                                $variantValues[$variantType] = [];
                            }
                            if (!in_array($value, $variantValues[$variantType])) {
                                $variantValues[$variantType][$value] = ['name' => $name, 'slug' => $value];
                            }
                            $variantTypeName = $variantTypesMap[$variantType] ?? $variantType;
                            $values[] = $variantTypeName.'-'.$name;
                        }

                        $skuCode = null;
                        if ($this->settings->exportProductIdAsSkuCode()) {
                            $skuCode =  $product->ID.'-'.$productVariantId;
                        } else {
                            $skuCode = $this->settings->exportSkuCode() ? $productVariant->get_sku() : null;
                        }
                        $variantPrices[] = [
                            'variant_id' => $productVariantId,
                            'values' => $values,
                            'price' => wc_get_price_excluding_tax($productVariant),
                            'barcode' => $this->settings->exportSkuCodeAsBarCode() ? $productVariant->get_sku() : null,
                            'sku_code' => $skuCode,
                        ];
                    }
                    
                    // echo "<pre>";
                    // var_dump($variantPrices);
                    // echo "</pre>";
                    // die;

                    $sbVariantValues = [];
                    $shopboxVariantTypesIds = [];
                    foreach ($variantValues as $variantType => $values) {
                        $values = array_map(
                            function ($item) {
                                return ['name' => $item['name'], 'slug' => $item['slug']];
                            },
                            $values
                        );

                        $sbVariantType = $this->api->createVariantType([
                            'name' => $variantTypesMap[$variantType] ?? $variantType,
                            'slug' => $variantType,
                            'variants' => array_values($values)
                        ]);

                        $shopboxVariantTypesIds[$sbVariantType->name] = $sbVariantType->uid;
                        foreach ($sbVariantType->variants as $value) {
                            $sbVariantValues[$sbVariantType->name.'-'.$value->name] = $value;
                        }
                    }

                    $sbVariants = [];
                    foreach ($variantPrices as $variantPrice) {
                        $vValues = [];
                        foreach ($variantPrice['values'] as $value) {
                            $vValues[] = $sbVariantValues[$value]->uid;
                        }

                        $sbVariants[]  = [
                            'uid' => 0,
                            'selling_price' => round($variantPrice['price'] * (100+$vatPercentage)),
                            'variance_value' => $vValues,
                            'barcode' => $variantPrice['barcode']??null,
                            'sku_code' => $variantPrice['sku_code']??null,
                            'inventory_multiply_factor' => 1,
                            'integration_number' => $variantPrice['variant_id'],
                            'integration_type' => 16
                        ];
                    }
                }

                $price = wc_get_price_excluding_tax($p);
                $shopboxId = null;
                if (isset($postMeta['_shopbox_id'])) {
                    $shopboxId = $postMeta['_shopbox_id'][0];
                }


                $sbImageId = null;

                if ($this->settings->updateImage() || !$shopboxId) {
                    if ($imageId && $this->settings->exportImages()) {
                        $path = get_attached_file($imageId);
                        $image = $this->api->uploadImage($path);
                        $sbImageId = $image->uid; 
                    }
                }


                if ($this->settings->exportProductIdAsSkuCode()) {
                    $skuCode =  $product->ID;
                } else {
                    $skuCode = $this->settings->exportSkuCode() ? $p->get_sku() : null;
                }

                $barCode = null;

                if ($this->settings->exportSkuCodeAsBarCode()) {
                    $barCode = $p->get_sku();
                    $barCode = is_numeric($barCode) ? $barCode : null;
                }
                $stockPrice = empty($postMeta['_alg_wc_cog_cost'][0]) ? 0 : floatval($postMeta['_alg_wc_cog_cost'][0]);
                
                $name = htmlspecialchars_decode($product->post_title);
                $sbProduct = $this->api->createProduct([
                    'woocommerce_id' => $product->ID,
                    'integration_number' => $product->ID,
                    'integration_type' => 16,
                    'uid' => $shopboxId,
                    'stock_price' => round($stockPrice * 100),
                    'selling_price' => round($price ? $price * (100+$vatPercentage) : 0),
                    'name' => $name,
                    'kitchen_name' => $name,
                    'official_name' => $name,
                    'tag' => $tagId,
                    'filter' => $cashRegister->filter_uid,
                    'image' => $sbImageId,
                    'variants' => $sbVariants,
                    'inventory_on_variants' => $p->get_manage_stock()?0:1,
                    'code' => $barCode??null,
                    'sku_code' => $skuCode,
                    'disable_name_update' => $this->settings->disableProductNameUpdate(),
                    'update_tag' => $this->settings->updateTag(),
                    'update_image' => $this->settings->updateImage(),
                    'auto_generate_barcode' => $this->settings->autoGenerateBarcode(),
                    'auto_generate_sku' => $this->settings->autoGenerateSku(),
                ]);
                
                if ($this->settings->exportInventory()) {
                    if ($sbProduct->inventory_on_variants && $p->is_type( 'variable' )) {
                        $woocommerceVariants = [];

                        foreach ($productVariants as $vId) {
                            $vr = new \WC_Product_variation($vId);
                            $attributes = [];
                            foreach ($vr->attributes as $key => $a) {
                                $variantTypeId = $shopboxVariantTypesIds[$variantTypesMap[$key] ?? $key] ?? null;
                                if (!empty($variantNameMap[$key][$a])) {
                                    $attributes[] = $variantTypeId.'-'.$variantNameMap[$key][$a];
                                } else {
                                    $attributes[] = $variantTypeId.'-'.$a;
                                }
                            }
                            sort($attributes);
                            
                            $woocommerceVariants[] = ['q' => $vr->get_stock_quantity(), 'variant_type_name' => $variantTypeName,'values' => array_values(array_filter($attributes))];
                        }

                        foreach ($sbProduct->product_variances as $productVariance) {
                            $values = [];
                            foreach ($productVariance->variance_values as $variance) {
                                $values[] = $variance->variance_type.'-'.$variance->name;
                            }
                            sort($values);
                            $quantity = null;
                            foreach ($woocommerceVariants as $v) {
                                if ($v['values'] == $values) {
                                    $quantity = $v['q'];
                                    break;
                                }
                            }

                            $this->api->createProductInventory([
                                'branch' => $this->settings->getBranchId(),
                                'product' => $sbProduct->uid,
                                'product_variance' => $productVariance->uid,
                                'quantity' => floatval($quantity),
                                'note' => 'wordpress_export'
                            ]);
                        }

                    } else {
                        $this->api->createProductInventory([
                            'branch' => $this->settings->getBranchId(),
                            'product' => $sbProduct->uid,
                            'quantity' => floatval($postMeta['_stock'][0]),
                            'note' => 'wordpress_export'
                        ]);
                    }
                }

                update_metadata('post', $product->ID, '_shopbox_id', $sbProduct->uid);
            } catch (\Exception $e) {
                try {
                  $logsPath = __DIR__.'/logs.php';
                  
                  $content = '';
                  if (file_exists($logsPath)) {
                      $content = file_get_contents($logsPath);
                  }
                  
                  $content .= "\n".$e->getMessage()."\n";
                  
                                  file_put_contents($logsPath, $content);
                } catch (\Exception $e) {
                }
            }
        }
    }

    protected function getPrimaryCategory($post_id, $term='category', $return_all_categories=false){
        $return = array();
    
        if (class_exists('WPSEO_Primary_Term')){
            // Show Primary category by Yoast if it is enabled & set
            $wpseo_primary_term = new \WPSEO_Primary_Term( $term, $post_id );
            $primary_term = get_term($wpseo_primary_term->get_primary_term());
    
            if (!is_wp_error($primary_term)){
                $return['primary_category'] = $primary_term;
            }
        }
    
        if (empty($return['primary_category']) || $return_all_categories){
            $categories_list = get_the_terms($post_id, $term);
    
            if (empty($return['primary_category']) && !empty($categories_list)){
                $return['primary_category'] = $categories_list[0];  //get the first category
            }
            if ($return_all_categories){
                $return['all_categories'] = array();
    
                if (!empty($categories_list)){
                    foreach($categories_list as &$category){
                        $return['all_categories'][] = $category->term_id;
                    }
                }
            }
        }
    
        return $return;
    }
}

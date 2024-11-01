<?php
namespace ShopBox\Integrators;

use ShopBox\Integrators\ProductsExporter;

class OrderCreator
{
    protected $productsExporter;

    public function __construct($api, $settings)
    {
        $this->api = $api;
        $this->settings = $settings;
        $this->productsExporter = new ProductsExporter($this->api, $this->settings);
    }

    protected function findVariance($productVariances, $attributes, $nameMap)
    {
        $originalAttributes = array_filter($attributes);
        foreach ($productVariances as $productVariance) {
            $attributes = $originalAttributes;
            $values = [];
            foreach ($productVariance->variance_values as $value) {
                $name = $value->name;
                foreach ($attributes as $key => $attr) {
                    $key = str_replace('attribute_', '', $key);

                    $otherName = $nameMap[$key][$attr] ?? $attr;
                    if ($name == $attr || $name == $otherName) {
                        $values[] = $attr;
                        break;
                    }
                }
            }
            $attributes = array_values($attributes);
            $values = array_values($values);
            sort($attributes);
            sort($values);
            if ($attributes == $values) {
                return $productVariance->uid;
            }
        }
    }

    /**
	 * * @param WCOrder $wcOrder Order
	 */
	public function create($wcOrderId) {
        // require_once( ABSPATH . '/wp-content/plugins/woocommerce/includes/class-wc-order.php' );
        $sbOrdersSynchStore = new  \ShopBox\DataStore\OrdersSynchStore();

        $wcOrder = new \WC_Order($wcOrderId);

        $status = $wcOrder->get_status();

        $sbOrderId = $sbOrdersSynchStore->getSbOrder($wcOrderId);
        $sbCanceledOrderId = $sbOrdersSynchStore->getSbOrder($wcOrderId, 'cancelled-synched');
        
        $hasActiveRefunds = false;
        $refunds = $wcOrder->get_refunds();
        foreach ($refunds as $refund) {
            if ($refund->get_items()) {
                $hasActiveRefunds = true;
                break;
            }
        }

        if ($status == 'completed' && !$sbOrderId) {
            $this->createBasket($wcOrder);
        } elseif (($status == 'cancelled' || $status == 'refunded') && !$hasActiveRefunds && $sbOrderId && !$sbCanceledOrderId) {
            $this->cancelBasket($wcOrder, $sbOrderId);
        } else {
            if ($sbOrderId) {
                $shopboxOrder = $this->api->getBasket($sbOrderId);
            }
    
            if ($sbOrderId && !$shopboxOrder->canceled) {
                foreach ($refunds as $refund) {
                    $refundOrderId = $sbOrdersSynchStore->getSbOrder($sbOrderId, 'refund-'.$refund->get_id().'-synched');
                    if (!$refundOrderId) {
                        $this->createBasket($refund, 'refund');
                    }
                }
            }
        }

    }


    protected function log($message)
    {
        try {
            $logsPath = __DIR__.'/logs.php';
            
            $content = '';
            if (file_exists($logsPath)) {
                $content = file_get_contents($logsPath);
            }
            
            $content .= "\n".$message."\n";
            
            file_put_contents($logsPath, $content);
          } catch (\Exception $e) {
          }
    }

    protected function createBasket($wcOrder, $type='order')
    {
        $sbOrdersSynchStore = new  \ShopBox\DataStore\OrdersSynchStore();

        try {
            $orderItems = $wcOrder->get_items();

            // var_dump($wcOrder->get_payment_method());die;
            
            $sbItems = [];
            $unsynchedProducts = [];
            
            foreach ($orderItems as $item) {
                $variationId = $item->get_variation_id();
                
                $p = wc_get_product($item->get_product_id());
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
                
                $sbProductId = get_post_meta($item->get_product_id(), '_shopbox_id', true);
                $varianceId = null;
                if (!$sbProductId) {
                    // $unsynchedProducts[] = $sbProductId;
                    // continue;
                } else {
                    try {
                        $product = $this->api->getProduct($sbProductId);
                        if ($variationId) {
                            if ($product->product_variances) {
                                $variationObj = new \WC_Product_variation($variationId);
                                $attributes = $variationObj->get_variation_attributes();
                                $varianceId = $this->findVariance($product->product_variances, $attributes, $variantNameMap);
                            } 
                            
                            if (!$varianceId) {
                                $sbProductId = null;
                            }
                        }
                    } catch (\Exception $e) {
                        $sbProductId = null;
                        // $unsynchedProducts[] = $sbProductId;
                        // continue;
                    }
                }

                if (!$sbProductId && $this->settings->autoExportProducts()) {
                    $this->productsExporter->exportProducts([get_post($item->get_product_id())]);
                    $sbProductId = get_post_meta($item->get_product_id(), '_shopbox_id', true);

                    $product = $this->api->getProduct($sbProductId);
                    
                    if ($variationId) {
                        if ($product->product_variances) {
                            $variationObj = new \WC_Product_variation($variationId);
                            $attributes = $variationObj->get_variation_attributes();
                            $varianceId = $this->findVariance($product->product_variances, $attributes, $variantNameMap);
                        } 
                        
                        if (!$varianceId) {
                            $sbProductId = null;
                        }
                    }
                }

                if (!$sbProductId) {
                    $unsynchedProducts[] = $sbProductId;
                    continue;
                }
                
                $sbItems[] = [
                    'product' => $sbProductId,
                    'quantity' => $item->get_quantity(),
                    'selling_price' => (int) (($item->get_total() + $item->get_total_tax())* 100 / $item->get_quantity()),
                    'comment' => '',
                    'tax' => null,
                    'course' => 0,
                    'product_variance' => $varianceId,
                    'menu' => null,
                    'name' => null,
                    'discount' => 0.0,
                ];
            }

            $shippingPrice = round(($wcOrder->get_shipping_total() + $wcOrder->get_shipping_tax()) * 100);

            if ($shippingPrice != 0) {
                $sbItems[] = [
                    'product' => null,
                    'quantity' => 1,
                    'selling_price' => $shippingPrice,
                    'comment' => '',
                    'tax' => null,
                    'course' => 0,
                    'menu' => null,
                    'name' => 'Shipping',
                    'discount' => 0.0,
                ];
            }

            if (count($unsynchedProducts) > 0) {
                $sbOrdersSynchStore->create([
                    'order_id' => $wcOrder->get_id(),
                    'status' => 'failed',
                    'message' => 'some products are not properly synched',
                ]);
                return false;
            }

            $cashRegisterId = $this->settings->getCashRegisterId();
            $staffId = $this->settings->getStaffId();
            
            $currency = $wcOrder->get_currency();
            $paymentType = $this->getPaymentType($currency);
            if ($type=='refund') {
                $datetime = $wcOrder->get_date_created();
            } else {
                $datetime = $wcOrder->get_date_completed();
            }

            $id = $type.'-'.$wcOrder->get_id();
            $sbOrder = [
                'order_hash' => $cashRegisterId.'-'.$id,
                'external_id' => $type.'-'.$wcOrder->get_id(),
                'creation_time' => $datetime->getTimestamp(),
                'cash_register' => $cashRegisterId,
                'basket_items' => $sbItems,
                'staff' => $staffId,
                'type' => $paymentType,
                'table_id' => -1,
                'discount_margin' => $wcOrder->get_total_discount(),
                'woocommerce' => true,
                'woocommerce_parent_order_id' => $wcOrder->get_parent_id(),
            ];
        
            $order = $this->api->createBasket($sbOrder);
            
            $storeData = [
                'order_id' => $type == 'refund' ? $wcOrder->get_parent_id() : $wcOrder->get_id(),
                'sb_order_id' => $order->uid,
                'status' => $type == 'refund' ? 'refund-'.$wcOrder->get_id().'-synched' : 'synched'
            ];
            $success = true;
        } catch (\Exception $e) {
            $storeData = [
                'order_id' => $type == 'refund' ? $wcOrder->get_parent_id() : $wcOrder->get_id(),
                'status' => $type == 'refund' ? 'refund-'.$wcOrder->get_id().'-failed' : 'synched',
                'message' => $e->getMessage(),
            ];
            $success = false;
        }

        $log = $sbOrdersSynchStore->get($wcOrder->get_id());
        if ($log) {
            $sbOrdersSynchStore->update($storeData, ['id' => $log->id]);
        } else {
            $sbOrdersSynchStore->create($storeData);
        } 
    }

    protected function getPaymentType($currency)
    {
        
        $currencyPaymentTypes = $this->settings->getCurrencyPaymentTypes();
        if (!empty($currencyPaymentTypes[$currency]) && is_numeric($currencyPaymentTypes[$currency])) {
            return $currencyPaymentTypes[$currency];
        }
        
        return $this->settings->getPaymentTypeId();
    }

    protected function cancelBasket($wcOrder, $sbOrderId)
    {
        $sbOrdersSynchStore = new  \ShopBox\DataStore\OrdersSynchStore();
        
        try {
            $order = $this->api->cancelBasket($sbOrderId);
            $sbOrdersSynchStore->create([
                'order_id' => $wcOrder->get_id(),
                'sb_order_id' => $order->uid,
                'status' => 'cancelled-synched'
            ]);
        } catch (\Exception $e) {
            $sbOrdersSynchStore->create([
                'order_id' => $wcOrder->get_id(),
                'status' => 'cancel-failed',
                'message' => $e->getMessage(),
            ]);
            return false;
        }

    }
}

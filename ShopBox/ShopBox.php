<?php
namespace ShopBox;

use ShopBox\Lib\Loader;
use ShopBox\Integrators\OrderCreator;
use ShopBox\Integrators\ProductsExporter;
use ShopBox\Admin\Admin as ShopBoxAdmin;
use ShopBox\Api as ShopBoxApi;
use ShopBox\Integrators\CategoriesExporter;
use ShopBox\ShopBoxHooks;

class ShopBox
{
    public function __construct()
    {
        if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
            $this->version = PLUGIN_NAME_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'shopbox';
        $this->loader = new Loader();
        $this->settings = new Settings($this->plugin_name);

        $this->shopBoxApi = new ShopBoxApi();
        $this->shopBoxApi->setToken($this->settings->getToken());	
    }

    public function run()
    {
        $this->pluginAdmin = new ShopboxAdmin( $this->plugin_name, $this->version );
        $this->load();
        
        if ($this->settings->synchOrders()) {
            $orderCreator = new OrderCreator($this->shopBoxApi, $this->settings);
            
            $this->loader->add_action( 'woocommerce_checkout_update_order_meta', $orderCreator, 'create' );
            $this->loader->add_action( 'woocommerce_process_shop_order_meta', $orderCreator, 'create', 100);
            $this->loader->add_action( 'woocommerce_order_status_changed', $orderCreator, 'create' );
            // $this->loader->add_action( 'woocommerce_order_status_completed', $orderCreator, 'create' );
            // $this->loader->add_action( 'woocommerce_order_status_cancelled', $orderCreator, 'create', 100);
            // $this->loader->add_action( 'woocommerce_order_status_refunded', $orderCreator, 'create', 100);
        }
        
        if (!isset($_GET['shopbox_hook_id']) && $this->settings->autoExportProducts()) {
            $this->loader->add_action( 'woocommerce_update_product', $this, 'postUpdated', 100, 3 );
            $this->loader->add_action( 'woocommerce_update_product_variation', $this, 'postUpdated', 100, 3 );
        }

        $this->loader->add_filter('woocommerce_duplicate_product_exclude_meta', $this, 'productExcludedMeta', 100, 3);

        $this->loader->run();
    }

    public function productExcludedMeta()
    {
        return ['_shopbox_id'];
    }

    public function postUpdated($postId)
    {
        $post = get_post($postId);

        if ($post->post_type !== 'product' || $post->post_status !='publish') {
            return;
        }

        try {
            $categoriesExporter = new CategoriesExporter($this->shopBoxApi, $this->settings);
            $categoriesExporter->exportUnsynchedCategories();
            
            $productsExporter = new ProductsExporter($this->shopBoxApi, $this->settings);
            $productsExporter->exportProducts([$post]);
        } catch (\Exception $e) {
            //log here
        }
    }

    public function load()
    {
        $pluginAdmin = $this->pluginAdmin;

        $this->loader->add_action( 'admin_enqueue_scripts', $pluginAdmin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $pluginAdmin, 'enqueue_scripts' );

        $this->loader->add_action('admin_init', $pluginAdmin, 'initialize');

        // Add menu item
        $this->loader->add_action( 'admin_menu', $pluginAdmin, 'add_plugin_admin_menu' );

        $this->loader->add_action( 'init', $pluginAdmin, 'prePageLoad' );

        // Add Settings link to the plugin
        // $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $plugin_name . '.php' );
        // $loader->add_filter( 'plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links' );


        
        $this->loader->add_action('admin_action_delete_meta_data', $pluginAdmin, 'deleteMetaData');
        $this->loader->add_action('admin_action_import_products', $pluginAdmin, 'importProductsPost');
        $this->loader->add_action('admin_action_export_products', $pluginAdmin, 'exportProductsPost');
        $this->loader->add_action('admin_action_cancel_export_products', $pluginAdmin, 'cancelExportProducts');
        $this->loader->add_action('admin_action_sb_retry_order_synch', $pluginAdmin, 'retryOrderSynchPost');

        $this->loader->add_action('action_update_inventory', $pluginAdmin, 'updateInventory');
        
        $this->loader->add_action('wp_ajax_sb_validate_settings', $pluginAdmin, 'validateSettings');
        $this->loader->add_action('wp_ajax_sb_get_branches', $pluginAdmin, 'getBranches');
        $this->loader->add_action('wp_ajax_sb_get_cash_registers', $pluginAdmin, 'getCashRegisters');

        add_filter( 'init', function( $template ) {
            $shopboxHookId = null;
            $action = null;

            if (!empty($_GET['shopbox_hook_id']) && !empty($_GET['action'])) {
                $shopboxHookId = sanitize_text_field( $_GET['shopbox_hook_id'] );
                $action = sanitize_text_field( $_GET['action'] );
            }
            
            if ( $shopboxHookId && $action ) {
                $ShopboxHooks = new ShopboxHooks($this->shopBoxApi, $this->settings);
                echo $ShopboxHooks->execute($shopboxHookId, $action);
                die;
            }
        } );
    }

}

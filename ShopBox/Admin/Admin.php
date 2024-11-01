<?php
namespace ShopBox\Admin;

use ShopBox\Api as ShopBoxApi;
use ShopBox\DataStore\OrdersSynchStore;
use ShopBox\Integrators\CategoriesExporter;
use ShopBox\Integrators\OrderCreator;
use ShopBox\Integrators\ProductsImporter;
use ShopBox\Jobs\ProductsExporterJob;
use ShopBox\Settings;
use ShopBox\Repositories\ProductsRepository;
use ShopBox\Repositories\CategoriesRepository;
use ShopBox\ShopBoxHooks;

class Admin
{

    private $plugin_name;

    private $version;

    private $page;

    protected $exporterJob;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $options = get_option($this->plugin_name);

        $this->settings = new Settings($this->plugin_name);
        $this->api = new ShopBoxApi();
        $this->api->setToken($this->settings->getToken());
        $this->exporterJob = new ProductsExporterJob;
        $this->exporterJob->setApi($this->api);
        $this->exporterJob->setSettings($this->settings);
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/shopbox-admin.css', array(), $this->version, 'all');

    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/jquery-validation-1.17.0/dist/jquery.validate.min.js');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/shopbox-admin.js', array('jquery'), $this->version, false);
    }

    public function initialize()
    {
        register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));
    }

    public function add_plugin_admin_menu()
    {
        add_menu_page(
            'Shopbox Integration Tools',
            'Shopbox',
            'manage_options',
            $this->plugin_name,
            array($this, 'home'),
            '',
            59
        );

        add_submenu_page(
            $this->plugin_name,
            'Import Products',
            'Import  Products',
            'manage_options',
            'shopbox_import_products',
            array($this, 'importProducts')
        );

        add_submenu_page(
            $this->plugin_name,
            'Export Products',
            'Export  Products',
            'manage_options',
            'shopbox_export_products',
            array($this, 'exportProducts')
        );

        add_submenu_page(
            $this->plugin_name,
            'ShopBox Settings',
            'Settings',
            'manage_options',
            'shopbox_settings',
            array($this, 'settings')
        );

        add_submenu_page(
            $this->plugin_name,
            'Exception Logs',
            'Exception Logs',
            'manage_options',
            'shopbox_exception_logs',
            array($this, 'exceptionLogs')
        );

    }

    public function add_action_links($links)
    {
        $settings_link = array(
            // '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name . '_settings') . '">' . __('Settings', $this->plugin_name) . '</a>',
        );
        return array_merge($settings_link, $links);
    }

    public function prePageLoad()
    {
        $page = !empty($_GET['page']) ? $_GET['page'] : null;

        $pages = [
            $this->plugin_name . '_home',
            $this->plugin_name . '_import_products',
            $this->plugin_name . '_export_products',
        ];
        if (!in_array($page, $pages)) {
            return; 
        }

        if (!$this->settings->has('token')
            || !$this->settings->has('cash_register_id')
            || !$this->settings->has('branch_id')
            || !$this->settings->has('tag_id')
            || !$this->settings->has('staff_id')
            || !$this->settings->has('payment_type_id')
        ) {
            wp_redirect(admin_url('admin.php?page=' . $this->plugin_name . '_settings'));
        } 
    }

    public function home()
    {
        $page = 'home';
        $ordersSynchStore = new OrdersSynchStore();

        $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;

        $pageData = $ordersSynchStore->getPage($pagenum, 30);

        $page_links = paginate_links(array(
            'base' => add_query_arg('pagenum', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;', 'text-domain'),
            'next_text' => __('&raquo;', 'text-domain'),
            'total' => $pageData['num_of_pages'],
            'current' => $pagenum,
        ));

        $ordersSynchLogs = $pageData['data'];
        include_once __DIR__ . '/partials/orders-synch-logs.php';
    }

    public function settings()
    {
        $options = get_option($this->plugin_name);
        $token = isset($options['token']) ? $options['token'] : null;
        $branchId = isset($options['branch_id']) ? $options['branch_id'] : null;
        $cashRegisterId = isset($options['cash_register_id']) ? $options['cash_register_id'] : null;
        $staffId = isset($options['staff_id']) ? $options['staff_id'] : null;
        $paymentTypeId = isset($options['payment_type_id']) ? $options['payment_type_id'] : null;
        $currencyPaymentTypes = isset($options['currency_payment_types']) ? $options['currency_payment_types'] : [];
        $tagId = isset($options['tag_id']) ? $options['tag_id'] : null;
        $synchOrders = isset($options['synch_orders']) ? $options['synch_orders'] : null;
        $autoExportProducts = isset($options['auto_export_products']) ? $options['auto_export_products'] : null;
        $exportImages = isset($options['export_images']) ? $options['export_images'] : null;
        $exportSkuCode = isset($options['export_sku_code']) ? $options['export_sku_code'] : 0;
        $exportProductIdAsSkuCode = isset($options['export_product_id_as_sku_code']) ? $options['export_product_id_as_sku_code'] : 0;
        $exportSkuAsBarCode = isset($options['export_sku_as_bar_code']) ? $options['export_sku_as_bar_code'] : 0;
        $vatPercentage = isset($options['vat_percentage']) ? $options['vat_percentage'] : 25;
        $exportInventory = isset($options['export_inventory']) ? $options['export_inventory'] : 1;
        $disableProductNameUpdate = isset($options['disable_product_name_update']) ? $options['disable_product_name_update'] : 0;
        $updateTag = isset($options['update_tag']) ? $options['update_tag'] : 0;
        $updateImage = isset($options['update_image']) ? $options['update_image'] : 0;
        $autoGenerateBarcode = isset($options['auto_generate_barcode']) ? $options['auto_generate_barcode'] : 0;
        $autoGenerateSkuCode = isset($options['auto_generate_sku']) ? $options['auto_generate_sku'] : 0;

        $hookUrl = null;
        
        $shopBoxHooks = new ShopBoxHooks($this->api, $this->settings);
        if ($shopBoxHooks->getHookId()) {
            $hookUrl = site_url('').'?shopbox_hook_id='.$shopBoxHooks->getHookId();
        }

        $branches = [];
        $staff = [];
        $cashRegisters = [];
        $tags = [];

        // var_dump($categories);die;

        if ($token) {
            try {
                $paymentTypes = $this->api->getPaymentTypes()->data;
            } catch (\Exception $e) {
            }
            
            try {
                $staff = $this->api->getStaff()->data;
            } catch (\Exception $e) {
            }
            
            try {
                $branches = $this->api->getBranches()->data;
            } catch (\Exception $e) {
            }
            
            try {
                $tags = $this->api->getTags()->data;
            } catch (\Exception $e) {
            }
            
            if ($branches && $branchId) {
                try {
                    $branchDetails = $this->api->getBranchDetails($branchId);
                
                    $cashRegisters = $branchDetails->cash_registers;
                } catch (\Exception $e) {
                }
            }
        }
        
        $page = 'settings';
        include_once __DIR__ . '/partials/general-settings.php';
    }

    public function deleteMetaData()
    {
        global $wpdb;
        
        $wpdb->delete($wpdb->prefix.'postmeta', ['meta_key' => '_shopbox_id']);
        $wpdb->delete($wpdb->prefix.'termmeta', ['meta_key' => '_shopbox_id']);
        
        wp_redirect($_SERVER['HTTP_REFERER']);
    }

    public function importProducts()
    {
        $productsImporter = new ProductsImporter($this->api, $this->settings);
        $unsynchedProducts = $productsImporter->getUnsynchedProducts();
        $inventory = $productsImporter->getProductsInventory();

        $args = array(
            'taxonomy'     => 'product_cat',
            'orderby'      => 'name',
            'hide_empty' => false
        );


        $wpCategories = get_categories( $args );
        $tags = $this->api->getTags()->data;
        
        $page = 'import_products';
        include_once __DIR__ . '/partials/import-products.php';
    }

    public function exportProducts()
    {
        $productsRepository = new ProductsRepository();
        $categoriesRepository = new CategoriesRepository();
        $unsynchedProducts = $productsRepository->getUnsynchedProducts(1, 100);
        $unsynchedCategories = $categoriesRepository->getUnsynchedCategories();
        $remainingJobsCount = $this->exporterJob->remainingJobsCount();
        $page = 'export_products';
        include_once __DIR__ . '/partials/export-products.php';
    }

    public function exceptionLogs()
    {
        $logsFile = __DIR__ . "/../Integrators/logs.php";
        $content = 'No Logs';
        if (file_exists($logsFile)) {
            $content = file_get_contents($logsFile);
        }
        
        $page = 'exception_logs';
        include_once __DIR__ . '/partials/exception-logs.php';
    }

    public function importProductsPost()
    {
        $productsImporter = new ProductsImporter($this->api, $this->settings);

        $productsImporter->import();
        wp_redirect($_SERVER['HTTP_REFERER']);
    }

    public function cancelExportProducts()
    {
        $this->exporterJob->cancel_process();
        wp_redirect($_SERVER['HTTP_REFERER']);
    }

    public function exportProductsPost()
    {
        $productsRepository = new ProductsRepository;
        $categoriesExporter = new CategoriesExporter($this->api, $this->settings);

        $exportAll = 0;
        if (isset($_POST['export_all'])) {
            $categoriesExporter->exportAllCategories();
            $pages = $productsRepository->getAllPagesCount();
            $exportAll = 1;
        } else {
            $categoriesExporter->exportUnsynchedCategories();
            $pages = $productsRepository->getUnsynchedPagesCount();
        }

        $this->exporterJob->cancel_process();
        if ($pages > 0) {
            for ($i=1; $i <= $pages; $i++) {
                $this->exporterJob->push_to_queue(['export_all' => $exportAll, 'page' => $i]);
            }
            $this->exporterJob->save()->dispatch();
        }

        wp_redirect($_SERVER['HTTP_REFERER']);
    }

    public function validate($input)
    {
        // All checkboxes inputs
        $valid = array();

        //Cleanup
        $valid['token'] = (isset($input['token']) && !empty($input['token'])) ? $input['token'] : '';
        $valid['branch_id'] = (isset($input['branch_id']) && !empty($input['branch_id'])) && $input['branch_id'] != '#NONE#' ? $input['branch_id'] : '';
        $valid['staff_id'] = (isset($input['staff_id']) && !empty($input['staff_id'])) && $input['staff_id'] != '#NONE#' ? $input['staff_id'] : '';
        $valid['payment_type_id'] = (isset($input['payment_type_id']) && !empty($input['payment_type_id'])) && $input['payment_type_id'] != '#NONE#' ? $input['payment_type_id'] : '';
        $valid['currency_payment_types'] = (isset($input['currency_payment_types']) && !empty($input['currency_payment_types'])) && $input['currency_payment_types'] != '#NONE#' ? $input['currency_payment_types'] : [];
        $valid['cash_register_id'] = (isset($input['cash_register_id']) && !empty($input['cash_register_id']) && $input['cash_register_id'] != '#NONE#') ? $input['cash_register_id'] : '';
        $valid['tag_id'] = (isset($input['tag_id']) && !empty($input['tag_id']) && $input['tag_id'] != '#NONE#') ? $input['tag_id'] : '';
        $valid['synch_orders'] = (isset($input['synch_orders']) && !empty($input['synch_orders'])) ? $input['synch_orders'] : 0;
        $valid['auto_export_products'] = (isset($input['auto_export_products']) && !empty($input['auto_export_products'])) ? $input['auto_export_products'] : 0;
        $valid['export_images'] = (isset($input['export_images']) && !empty($input['export_images'])) ? $input['export_images'] : 0;
        $valid['export_sku_code'] = !empty($input['export_sku_code']) ? $input['export_sku_code'] : 0;
        $valid['export_product_id_as_sku_code'] = !empty($input['export_product_id_as_sku_code']) ? $input['export_product_id_as_sku_code'] : 0;
        $valid['export_sku_as_bar_code'] = !empty($input['export_sku_as_bar_code']) ? $input['export_sku_as_bar_code'] : 0;
        $valid['vat_percentage'] = !empty($input['vat_percentage']) ? $input['vat_percentage'] : 0;
        $valid['export_inventory'] = !empty($input['export_inventory']) ? $input['export_inventory'] : 0;
        $valid['disable_product_name_update'] = empty($input['update_product_name']) ? 1 : 0;
        $valid['update_tag'] = !empty($input['update_tag']) ? $input['update_tag'] : 0;
        $valid['update_image'] = !empty($input['update_image']) ? $input['update_image'] : 0;
        $valid['auto_generate_barcode'] = !empty($input['auto_generate_barcode']) ? $input['auto_generate_barcode'] : 0;
        $valid['auto_generate_sku'] = !empty($input['auto_generate_sku']) ? $input['auto_generate_sku'] : 0;
        
        return $valid;
    }

    public function retryOrderSynchPost()
    {
        if (empty($_POST['order_id'])) {
            return;
        }

        $orderId = sanitize_text_field($_POST['order_id']);
        $orderCreator = new OrderCreator($this->api, $this->settings);
        $orderCreator->create($orderId);
        wp_redirect($_SERVER['HTTP_REFERER']);
    }
}

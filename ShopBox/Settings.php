<?php
namespace ShopBox;

class Settings
{
    protected $options;

    public function __construct($pluginName)
    {
        $this->options = get_option($pluginName);
    }

    public function synchOrders()
    {
        if (
            isset($this->options['synch_orders'])
            && $this->options['synch_orders']
        ) {
            return true;
        }

        return false;
    }

    public function autoExportProducts()
    {
        if (
            isset($this->options['auto_export_products'])
            && $this->options['auto_export_products']
        ) {
            return true;
        }

        return false;
    }

    public function exportImages()
    {
        if (
            isset($this->options['export_images'])
            && $this->options['export_images']
        ) {
            return true;
        }

        return false;
    }


    public function exportSkuCode()
    {
        return empty($this->options['export_sku_code']) ? false : true;
    }


    public function exportProductIdAsSkuCode()
    {
        return empty($this->options['export_product_id_as_sku_code']) ? false : true;
    }

    public function exportSkuCodeAsBarCode()
    {
        return empty($this->options['export_sku_as_bar_code']) ? false : true;
    }

    public function has($propName)
    {
        if (
            isset($this->options[$propName])
            && $this->options[$propName]
        ) {
            return true;
        }

        return false;
    }

    public function hasToken()
    {
        if (
            isset($this->options['token'])
            && $this->options['token']
        ) {
            return true;
        }

        return false;
    }

    public function getToken()
    {
        return $this->options['token'] ?? null;
    }

    public function getCashRegisterId()
    {
        return $this->options['cash_register_id'];        
    }

    public function getBranchId()
    {
        return $this->options['branch_id'];        
    }

    public function getTagId()
    {
        return $this->options['tag_id'];        
    }

    public function getStaffId()
    {
        return $this->options['staff_id'];
    }

    public function getPaymentTypeId()
    {
        return $this->options['payment_type_id'];
    }

    public function getCurrencyPaymentTypes()
    {
        return $this->options['currency_payment_types'] ?? [];
    }

    public function vatPercentage()
    {
        return !isset($this->options['vat_percentage']) ? 25 : $this->options['vat_percentage'];
    }

    public function exportInventory()
    {
        return !isset($this->options['export_inventory']) ? 1 : $this->options['export_inventory'];
    }

    public function disableProductNameUpdate()
    {
        return !isset($this->options['disable_product_name_update']) ? 0 : $this->options['disable_product_name_update'];
    }

    public function updateImage()
    {
        return !isset($this->options['update_image']) ? 0 : $this->options['update_image'];
    }

    public function updateTag()
    {
        return !isset($this->options['update_tag']) ? 0 : $this->options['update_tag'];
    }

    public function autoGenerateBarcode()
    {
        return !isset($this->options['auto_generate_barcode']) ? 0 : $this->options['auto_generate_barcode'];
    }

    public function autoGenerateSku()
    {
        return !isset($this->options['auto_generate_sku']) ? 0 : $this->options['auto_generate_sku'];
    }
}

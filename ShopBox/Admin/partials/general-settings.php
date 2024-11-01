
<div class="wrap" id='col-container'>
<?php include(__DIR__.'/header.php')?>

<div>
    <h2 class="nav-tab-wrapper">Shopbox settings</h2>

    <form method="POST" action="<?php echo admin_url( 'admin.php' ); ?>" style='display:none;'>
        <input type="hidden" name="action" value="delete_meta_data" />
        <?php submit_button('Delete Meta Data', 'primary','submit', TRUE); ?>
    </form>
    <form method="post" name="shopbox_options" action="options.php" id='shopbox-options'>

        <?php
            settings_fields($this->plugin_name);
            do_settings_sections($this->plugin_name);
        ?>

        <!-- remove some meta and generators from the <head> -->
        <fieldset>
            <div class="form-wrap">
                <div class="form-field form-required term-name-wrap">
                    <label for="<?php echo $this->plugin_name; ?>-token">Token</label>
                    <input 
                        type="text"
                        id="<?php echo $this->plugin_name; ?>-token"
                        name="<?php echo $this->plugin_name; ?>[token]"
                        value="<?php esc_html_e($token)?>"
                    />
                    <p>You can get it from shopbox dashboard.</p>
                </div>
                <div>
                    <label for="">Hook Url</label>
                    <b>
                        <?php esc_html_e($hookUrl); ?>
                    </b>
                </div>           
                <div class='form-field'>
                    <label for="<?php echo $this->plugin_name; ?>-branch">Select Staff</label>

                    <select name="<?php echo $this->plugin_name; ?>[staff_id]" id="">
                        <option value="#NONE#">— Select —</option>
                        <?php foreach ($staff as $item) {?>
                        <?php
                            if ($item->account0->uid == $staffId) {
                                $selected = 'selected';
                            } else {
                                $selected = '';
                            }
                        ?>
                        <option <?php echo $selected;?> value="<?php esc_attr_e($item->account0->uid)?>"><?php esc_html_e($item->account0->username)?></option>
                        <?php }?>
                    </select>
                </div>
                
                <h3>Select payment types</h3>
                <div class='form-field'>
                    <label for="<?php echo $this->plugin_name; ?>-branch"><b>Default</b></label>

                    <select name="<?php echo $this->plugin_name; ?>[payment_type_id]" id="">
                        <option value="#NONE#">— Select —</option>
                        <?php foreach ($paymentTypes as $item) {?>
                        <?php
                            if ($item->uid == $paymentTypeId) {
                                $selected = 'selected';
                            } else {
                                $selected = '';
                            }
                        ?>
                        <option <?php echo $selected;?> value="<?php esc_attr_e($item->uid)?>"><?php esc_html_e($item->name)?></option>
                        <?php }?>
                    </select>
                </div>

                <?php $currencies = [
                    'DKK', 'SEK', 'NOK', 'EUR'
                ];?>
                <?php foreach ($currencies as $currency) { ?>
                <div class='form-field'>
                    <label for="<?php echo $this->plugin_name; ?>-branch"><b> <?php echo $currency?></b></label>

                    <select name="<?php echo $this->plugin_name; ?>[currency_payment_types][<?php echo $currency?>]" id="">
                        <option value="#NONE#">— Select —</option>
                        <?php foreach ($paymentTypes as $item) {?>
                        <?php
                            if (!empty($currencyPaymentTypes[$currency]) && $item->uid == $currencyPaymentTypes[$currency]) {
                                $selected = 'selected';
                            } else {
                                $selected = '';
                            }
                        ?>
                        <option <?php echo $selected;?> value="<?php esc_attr_e($item->uid)?>"><?php esc_html_e($item->name)?></option>
                        <?php }?>
                    </select>
                </div>
                <?php } ?>

                <div class='form-field'>
                    <label for="<?php echo $this->plugin_name; ?>-branch">Select Uncategorized Tag</label>

                    <select name="<?php echo $this->plugin_name; ?>[tag_id]" id="">
                        <option value="#NONE#">— Select —</option>
                        <?php foreach ($tags as $tag) {?>
                        <?php
                            if ($tag->uid == $tagId) {
                                $selected = 'selected';
                            } else {
                                $selected = '';                                
                            }
                        ?>
                        <option <?php echo $selected;?> value="<?php esc_attr_e($tag->uid)?>"><?php esc_html_e($tag->name)?></option>
                        <?php }?>
                    </select>
                </div>

                <div class='form-field'>
                    <label for="<?php echo $this->plugin_name; ?>-branch">Select Branch</label>

                    <select name="<?php echo $this->plugin_name; ?>[branch_id]" id="">
                        <option value="#NONE#">— Select —</option>
                        <?php foreach ($branches as $branch) {?>
                        <?php
                            if ($branch->uid == $branchId) {
                                $selected = 'selected';
                            } else {
                                $selected = '';                                
                            }
                        ?>
                        <option <?php echo $selected;?> value="<?php esc_attr_e($branch->uid)?>"><?php esc_html_e($branch->name)?></option>
                        <?php }?>
                    </select>
                </div>
                


                <div class='form-field'>
                    <label for="<?php echo $this->plugin_name; ?>-branch">Select Cash register</label>

                    <select name="<?php echo $this->plugin_name; ?>[cash_register_id]" id="">
                        <option value="#NONE#">— Select —</option>
                        <?php foreach ($cashRegisters as $sCashRegister) {?>
                        <?php
                            if ($sCashRegister->uid == $cashRegisterId) {
                                $selected = 'selected';
                            } else {
                                $selected = '';                                
                            }
                        ?>
                        <option <?php echo $selected;?> value="<?php esc_attr_e($sCashRegister->uid) ?>"><?php esc_html_e($sCashRegister->name)?></option>
                        <?php }?>
                    </select>
                </div>
                
                <div class="form-field form-required term-name-wrap">
                    <label for="<?php echo $this->plugin_name; ?>-vat-percentage">Vat Percentage</label>
                    <input 
                        type="number"
                        id="<?php echo $this->plugin_name; ?>-vat-percentage"
                        name="<?php echo $this->plugin_name; ?>[vat_percentage]"
                        value="<?php esc_html_e($vatPercentage)?>"
                    />
                    <p>Vat Percentage to add to vat excluded prices before exporting to shopbox</p>
                </div>
                
                <label for="export_inventory">
                    <input name="<?php echo $this->plugin_name; ?>[export_inventory]" <?php echo $exportInventory?'checked':''?> type="checkbox" id="export_inventory" value="1"/>
                    Export Inventory
                </label>

                <label for="export_sku_code">
                    <input name="<?php echo $this->plugin_name; ?>[export_sku_code]" <?php echo $exportSkuCode?'checked':''?> type="checkbox" id="export_sku_code" value="1"/>
                    Export Sku Code
                </label>

                <label for="export_product_id_as_sku_code">
                    <input name="<?php echo $this->plugin_name; ?>[export_product_id_as_sku_code]" <?php echo $exportProductIdAsSkuCode?'checked':''?> type="checkbox" id="export_product_id_as_sku_code" value="1"/>
                    Export Woocommerce product ID’s as SKU Code
                </label>

                <label for="export_sku_as_bar_code">
                    <input name="<?php echo $this->plugin_name; ?>[export_sku_as_bar_code]" <?php echo $exportSkuAsBarCode?'checked':''?> type="checkbox" id="export_sku_as_bar_code" value="1"/>
                    Export Sku Code as bar code
                </label>

                <label for="export_images">
                    <input name="<?php echo $this->plugin_name; ?>[export_images]" <?php echo $exportImages?'checked':''?> type="checkbox" id="export_images" value="1"/>
                    Export Images
                </label>

                <label for="auto_export_products">
                    <input name="<?php echo $this->plugin_name; ?>[auto_export_products]" <?php echo $autoExportProducts?'checked':''?> type="checkbox" id="auto_export_products" value="1"/>
                    Automatically Export Products To ShopBox
                </label>

                <label for="update_product_name">
                    <input name="<?php echo $this->plugin_name; ?>[update_product_name]" <?php echo $disableProductNameUpdate?'':'checked'?> type="checkbox" id="update_product_name" value="1"/>
                    Update product name in shopbox app (after first import)
                </label>

                <label for="tag_update">
                    <input name="<?php echo $this->plugin_name; ?>[update_tag]" <?php echo $updateTag?'checked':''?> type="checkbox" id="tag_update" value="1"/>
                    update tag (after first import)
                </label>

                <label for="image_update">
                    <input name="<?php echo $this->plugin_name; ?>[update_image]" <?php echo $updateImage?'checked':''?> type="checkbox" id="image_update" value="1"/>
                    Update image in shopbox app (after first import)
                </label>

                <label for="synch_orders">
                    <input name="<?php echo $this->plugin_name; ?>[synch_orders]" <?php echo $synchOrders?'checked':''?> type="checkbox" id="synch_orders" value="1"/>
                    Send Woocommerce Orders To ShopBox
                </label>
                <label for="auto_generate_barcode">
                    <input name="<?php echo $this->plugin_name; ?>[auto_generate_barcode]" <?php echo $autoGenerateBarcode?'checked':''?> type="checkbox" id="auto_generate_barcode" value="1"/>
                    Auto generate barcode 
                </label>
                <label for="auto_generate_sku">
                    <input name="<?php echo $this->plugin_name; ?>[auto_generate_sku]" <?php echo $autoGenerateSkuCode?'checked':''?> type="checkbox" id="auto_generate_sku" value="1"/>
                    Auto generate sku code 
                </label>
            </div>
        </fieldset>

        <?php submit_button('Save Settings', 'primary','submit', TRUE); ?>

    </form>
</div>
</div>
<script>
    (function( $ ) {
        $(function () {
            // $('#shopbox-options').validate({
            //     rules: {
            //        "<?php echo $this->plugin_name; ?>[token]": {
            //             required: true,
            //             remote: "<?php echo admin_url('admin-ajax.php') ?>?action=sb_validate_settings"
            //         } 
            //     }
            // });

        });
        
    })( jQuery );
</script>
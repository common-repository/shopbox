
<?php include(__DIR__.'/header.php')?>

<form method="POST" action="<?php echo admin_url( 'admin.php' ); ?>">
    <input type="hidden" name="action" value="import_products" />
    <?php submit_button('Import Products', 'primary','submit', TRUE); ?>
</form>

<h2>Unsynched products</h2>
<table class='wp-list-table widefat fixed striped posts'>
    <thead>
        <tr>
            <th></th>
            <th>Title</th>
            <th>Price</th>
            <th>Quantity</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            foreach ($unsynchedProducts as $product) {
                if (!$product->product0) {
                    continue;
                }
                $sbProductId = $product->product;
        ?>
        <tr>
            <td>
                <?php if ($product->product0->image0) { ?>
                <img src="<?php echo esc_url($product->product0->image0->image_small)?>">
                <?php } ?>
            </td>
            <td><?php esc_html_e($product->product0->name)?></td>
            <td><?php esc_html_e($product->selling_price)?></td>
            <td><?php esc_html_e(isset($inventory[$sbProductId]) ? $inventory[$sbProductId]->quantity:'')?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

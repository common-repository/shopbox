<?php include(__DIR__.'/header.php')?>

<?php if ($remainingJobsCount > 0) { ?>
<h2>export in progress ...</h2>
<h2><?=$remainingJobsCount?> remaining</h2>
<form method="POST" action="<?php echo admin_url( 'admin.php' ); ?>">
    <input type="hidden" name="action" value="cancel_export_products" />
    <?php submit_button('Cancel Export', 'secondary','submit', TRUE); ?>
</form>
<?php } else { ?>
<form method="POST" action="<?php echo admin_url( 'admin.php' ); ?>">
    <h2>Export Products</h2>
    <label>
        <input name="export_all" type="checkbox" value="1"/>
        Export All
    </label>

    <input type="hidden" name="action" value="export_products" />
    <?php submit_button('Export Products', 'primary','submit', TRUE); ?>
</form>
<?php } ?>
<h2>Unsynched categories</h2>
<table class='wp-list-table widefat fixed striped posts'>
    <thead>
        <tr>
            <th>Title</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($unsynchedCategories as $category) { ?>
        <tr>
            <td><?php echo $category->cat_name?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>

<h2>Unsynched products</h2>
<table class='wp-list-table widefat fixed striped posts'>
    <thead>
        <tr>
            <th>Title</th>
            <th>Price</th>
            <th>Quantity</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($unsynchedProducts as $product) { ?>
        <tr>
            <td><?php esc_html_e($product->post_title)?></td>
            <td><?php esc_html_e(get_post_meta($product->ID, '_price', true))?></td>
            <td><?php esc_html_e(get_post_meta($product->ID, '_stock', true))?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
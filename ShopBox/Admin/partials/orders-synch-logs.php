<?php include(__DIR__.'/header.php')?>

<h2>Orders Synch Logs</h2>
<div class='wrap'>
    <table class='wp-list-table widefat fixed striped posts'>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>ShopBox ID</th>
                <th>Status</th>
                <th>Message</th>
            </tr>
        </thead>

        <tbody>
            <?php 
                foreach ($ordersSynchLogs as $log) { 
                    $firstName = get_post_meta($log->order_id, '_shipping_first_name', true);
                    $lastName = get_post_meta($log->order_id, '_shipping_last_name', true);
            ?>
            <tr>
                <td><?php echo edit_post_link(esc_html('#'.$log->order_id.' '.$firstName.' '.$lastName), '<strong>', '</strong>', $log->order_id)?></td>
                <td><?php echo $log->sb_order_id?></td>
                <td>
                    <?php echo $log->status?>
                
                    <?php if ($log->status == 'failed' || $log->status == 'cancel-failed') {
                        $text = 'Retry';
                    } else {
                        $text = 'Resend';
                    } ?>

                    <form method="POST" action="<?php echo admin_url( 'admin.php' ); ?>">
                        <input type="hidden" name="action" value="sb_retry_order_synch" />
                        <input type="hidden" name="order_id" value="<?php echo $log->order_id?>" />
                        <?php submit_button($text, 'primary','submit', TRUE); ?>
                    </form>
                </td>
                <td><?php echo esc_html($log->message)?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="tablenav bottom">
        <?php
            if ( $page_links ) {
                echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
            }
        ?>
    </div>
</div>

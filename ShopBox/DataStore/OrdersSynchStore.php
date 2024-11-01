<?php
namespace ShopBox\DataStore;

class OrdersSynchStore
{
    static protected function getTableName()
    {
        global $wpdb;
        return $wpdb->prefix . 'sb_orders_synch';
    }
    
    static public function createTable()
    {
        global $wpdb;
        $ordersSynchTable = self::getTableName();

        #Check to see if the table exists already, if not, then create it
        if($wpdb->get_var( "show tables like '$ordersSynchTable'" ) != $ordersSynchTable) 
        {
            $sql = "CREATE TABLE IF NOT EXISTS `$ordersSynchTable`
                    ( 
                        `id` INT NOT NULL AUTO_INCREMENT , 
                        `order_id` DOUBLE NOT NULL , 
                        `sb_order_id` INT, 
                        `status` VARCHAR(255) ,
                        `message` VARCHAR(255) , 
                        PRIMARY KEY (`id`)
                    ) ENGINE = InnoDB";
            require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
            dbDelta($sql);
        }
    }

    public function getSbOrder($orderId, $status = 'synched')
    {
        global $wpdb;
        $tableName = self::getTableName();
        $sbOrderId = $wpdb->get_var(
            "SELECT sb_order_id
            FROM  $tableName
            WHERE order_id = '$orderId'
                AND sb_order_id IS NOT NULL
                AND status = '$status'"
        );

        return $sbOrderId;
    }

    public function create($data)
    {
        global $wpdb;
        $r = $wpdb->insert(self::getTableName(), $data);
    }

    public function get($orderId)
    {
        global $wpdb;
        $tableName = self::getTableName();
        return $wpdb->get_row(
                "SELECT *
                FROM  $tableName
                WHERE order_id = '$orderId'"
            );

    }

    public function update($data, $where)
    {
        global $wpdb;
        $r = $wpdb->update(self::getTableName(), $data, $where);
    }

    public function getPage($pagenum, $limit)
    {
        global $wpdb;
        $ordersSynchTable = self::getTableName();

        $offset = ( $pagenum - 1 ) * $limit;
        $total = $wpdb->get_var( "SELECT COUNT(`id`) FROM $ordersSynchTable" );
        $num_of_pages = ceil( $total / $limit );

        return [
            'total' => $total,
            'data' => $wpdb->get_results( "SELECT * FROM $ordersSynchTable ORDER BY id DESC LIMIT $offset, $limit" ),
            'per_page' => $limit,
            'num_of_pages' => $num_of_pages
        ];
    }
}

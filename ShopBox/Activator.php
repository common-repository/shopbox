<?php
namespace ShopBox;

use ShopBox\DataStore\OrdersSynchStore;

class Activator {

	public static function activate() {
        OrdersSynchStore::createTable();
	}

}

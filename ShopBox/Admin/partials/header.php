<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
	<a href="<?php echo admin_url( 'admin.php?page=' . $this->plugin_name)?>"
		class="nav-tab <?php echo $page == 'home'?'nav-tab-active':''?>"
	>Logs</a>
	<a href="<?php echo admin_url( 'admin.php?page=' . $this->plugin_name . '_import_products')?>" 
		class="nav-tab <?php echo $page == 'import_products'?'nav-tab-active':''?>"
	>Import Products</a>
	<a href="<?php echo admin_url( 'admin.php?page=' . $this->plugin_name . '_export_products')?>" 
		class="nav-tab <?php echo $page == 'export_products'?'nav-tab-active':''?>"
	>Export Products</a>
	<a href="<?php echo admin_url( 'admin.php?page=' . $this->plugin_name . '_settings')?>" 
		class="nav-tab <?php echo @$page == 'settings'?'nav-tab-active':''?>"
	>Settings</a>
</nav>

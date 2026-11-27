<?php
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }
// Only the plugin's own setting is removed. WooCommerce and WordPress data are never touched.
delete_option('pcw_enabled_widgets');

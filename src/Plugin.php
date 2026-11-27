<?php
namespace Phehrest\CustomWidgets;

use Phehrest\CustomWidgets\Admin\Dashboard;
use Phehrest\CustomWidgets\Query\CartProducts;
use Phehrest\CustomWidgets\Widgets\Registry;

if (!defined('ABSPATH')) { exit; }

final class Plugin {
    public function boot(): void {
        (new CartProducts())->boot();
        $registry = new Registry();
        if (is_admin()) { (new Dashboard($registry))->boot(); }
        $registry->boot();
    }
}

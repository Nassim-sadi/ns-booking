<?php
if (!defined('ABSPATH')) exit;
class NSBC_Deactivator {
    public static function deactivate() { flush_rewrite_rules(); }
}

<?php
if (!defined('ABSPATH')) exit;

class NSBC_Loader {
    private $actions = [];
    private $filters = [];

    public function add_action($hook, $component, $callback = null, $priority = 10, $accepted_args = 1) {
        if ($callback === null && is_string($component) && is_callable($component)) {
            $callback = $component;
            $component = null;
        }
        $this->actions[] = compact('hook','component','callback','priority','accepted_args');
    }
    public function add_filter($hook, $component, $callback = null, $priority = 10, $accepted_args = 1) {
        if ($callback === null && is_string($component) && is_callable($component)) {
            $callback = $component;
            $component = null;
        }
        $this->filters[] = compact('hook','component','callback','priority','accepted_args');
    }
    public function run() {
        foreach ($this->filters as $f) {
            $cb = $f['component'] ? [$f['component'], $f['callback']] : $f['callback'];
            add_filter($f['hook'], $cb, $f['priority'], $f['accepted_args']);
        }
        foreach ($this->actions as $a) {
            $cb = $a['component'] ? [$a['component'], $a['callback']] : $a['callback'];
            add_action($a['hook'], $cb, $a['priority'], $a['accepted_args']);
        }
    }
}

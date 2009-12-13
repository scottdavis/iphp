<?php
interface iphp_command {
    function run($shell, $args);
    function name();
}
class iphp_command_exit implements iphp_command {
    function run($shell, $args) {
        $shell->running = false;
    }
    function name() {
        return array('exit', 'die', 'bye', 'quit');
    }
}
class iphp_command_reload implements iphp_command {
    function run($shell, $args) {
        $shell->initialize($shell->options());
    }
    function name() {
        return 'reload';
    }
}

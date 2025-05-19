<?php
function flying_scripts_set_default_config() {
    $current_version = get_option('FLYING_SCRIPTS_VERSION');
    
    if (FLYING_SCRIPTS_VERSION !== $current_version) {
        $defaults = array(
            'flying_scripts_timeout' => 5,
            'flying_scripts_include_list' => array(),
            'flying_scripts_disabled_pages' => array(),
            'flying_scripts_first_visit_only' => 0,
            'flying_scripts_jquery_dependent' => array() // New default option
        );
        
        foreach ($defaults as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                update_option($option_name, $default_value);
            }
        }
        
        update_option('FLYING_SCRIPTS_VERSION', FLYING_SCRIPTS_VERSION);
    }
}

add_action('plugins_loaded', 'flying_scripts_set_default_config');
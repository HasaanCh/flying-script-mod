<?php
// Set default config on plugin load if not set
function flying_scripts_set_default_config() {
    // Check if version has changed
    $current_version = get_option('FLYING_SCRIPTS_VERSION');
    
    if (FLYING_SCRIPTS_VERSION !== $current_version) {
        // Set default options if they don't exist
        $defaults = array(
            'flying_scripts_timeout' => 5,
            'flying_scripts_include_list' => array(),
            'flying_scripts_disabled_pages' => array(),
            'flying_scripts_first_visit_only' => 0
        );
        
        foreach ($defaults as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                update_option($option_name, $default_value);
            }
        }
        
        // Update the version number
        update_option('FLYING_SCRIPTS_VERSION', FLYING_SCRIPTS_VERSION);
    }
}

add_action('plugins_loaded', 'flying_scripts_set_default_config');
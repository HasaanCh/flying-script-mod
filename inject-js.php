<?php
function flying_scripts_should_skip_js_injection() {
    // Skip if in admin area
    if (is_admin()) {
        return true;
    }
    
    // Skip for Elementor edit/preview mode
    if (isset($_GET['elementor-preview']) || 
        isset($_GET['elementor_library']) || 
        isset($_GET['action']) && $_GET['action'] === 'elementor') {
        return true;
    }
    
    // Skip if Elementor is in edit mode (check for Elementor constants/functions)
    if (defined('ELEMENTOR_VERSION')) {
        // Check if we're in Elementor edit mode
        if (isset($_GET['action']) && $_GET['action'] === 'elementor') {
            return true;
        }
        
        // Check for Elementor preview
        if (isset($_GET['elementor-preview'])) {
            return true;
        }
        
        // Additional check for Elementor edit mode
        if (function_exists('\Elementor\Plugin::instance') && 
            \Elementor\Plugin::instance()->editor && 
            \Elementor\Plugin::instance()->editor->is_edit_mode()) {
            return true;
        }
        
        // Check for Elementor preview mode
        if (function_exists('\Elementor\Plugin::instance') && 
            \Elementor\Plugin::instance()->preview && 
            \Elementor\Plugin::instance()->preview->is_preview_mode()) {
            return true;
        }
    }
    
    // Skip for other page builders
    // Beaver Builder
    if (isset($_GET['fl_builder']) || 
        (function_exists('FLBuilderModel') && FLBuilderModel::is_builder_active())) {
        return true;
    }
    
    // Divi Builder
    if (function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled()) {
        return true;
    }
    
    return false;
}

function flying_scripts_inject_js() {
    // Don't inject JS if we should skip processing
    if (flying_scripts_should_skip_js_injection()) {
        return;
    }
    
    $timeout = intval(get_option('flying_scripts_timeout', 5));
    $first_visit_only = get_option('flying_scripts_first_visit_only', 0) ? true : false;
    ?>
<script type="text/javascript" id="flying-scripts">
const loadScriptsTimer = setTimeout(loadScripts, <?php echo $timeout ?>*1000);
const userInteractionEvents = ['click', 'mousemove', 'keydown', 'touchstart', 'touchmove', 'wheel'];
userInteractionEvents.forEach(function(event) {
    window.addEventListener(event, triggerScriptLoader, { passive: true });
});
function triggerScriptLoader() {
    loadScripts();
    clearTimeout(loadScriptsTimer);
    userInteractionEvents.forEach(function(event) {
        window.removeEventListener(event, triggerScriptLoader, { passive: true });
    });
}
function loadScripts() {
    document.querySelectorAll("script[data-type='lazy']").forEach(function(elem) {
        elem.setAttribute("src", elem.getAttribute("data-src"));
    });
    let retryCount = 0;
    const maxRetries = 50;
    function loadJQueryScripts() {
        if (typeof jQuery !== 'undefined') {
            document.querySelectorAll("script[data-type='jquery-lazy']").forEach(function(elem) {
                elem.setAttribute("src", elem.getAttribute("data-src"));
            });
        } else if (retryCount < maxRetries) {
            retryCount++;
            setTimeout(loadJQueryScripts, 100);
        }
    }
    loadJQueryScripts();
    <?php if ($first_visit_only) : ?>
    document.cookie = "flying_scripts_visitor=1; max-age=2592000; path=/";
    <?php endif; ?>
}
</script>
    <?php
}

add_action('wp_print_footer_scripts', 'flying_scripts_inject_js');
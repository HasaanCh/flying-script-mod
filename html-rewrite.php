<?php
include('lib/dom-parser.php');

function flying_scripts_is_keyword_included($content, $keywords)
{
    foreach ($keywords as $keyword) {
        if (strpos($content, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

function flying_scripts_should_skip_processing() {
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
    
    // Skip for other page builders that might have similar issues
    // Beaver Builder
    if (isset($_GET['fl_builder']) || 
        (function_exists('FLBuilderModel') && FLBuilderModel::is_builder_active())) {
        return true;
    }
    
    // Divi Builder
    if (function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled()) {
        return true;
    }
    
    // Gutenberg editor (Block editor)
    if (function_exists('is_admin') && 
        (strpos($_SERVER['REQUEST_URI'], 'post.php') !== false || 
         strpos($_SERVER['REQUEST_URI'], 'post-new.php') !== false) &&
        isset($_GET['action']) && $_GET['action'] === 'edit') {
        return true;
    }
    
    return false;
}

function flying_scripts_rewrite_html($html)
{
    // Skip processing if we should not process
    if (flying_scripts_should_skip_processing()) {
        return $html;
    }
    
    // Process only GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return $html;
    }
    
    // Detect non-HTML
    if (!isset($html) || trim($html) === '' || strcasecmp(substr($html, 0, 5), '<?xml') === 0 || trim($html)[0] !== "<") {
        return $html;
    }

    // Exclude on pages
    $disabled_pages = get_option('flying_scripts_disabled_pages');
    $current_url = home_url($_SERVER['REQUEST_URI']);
    if (flying_scripts_is_keyword_included($current_url, $disabled_pages)) {
        return $html;
    }
    
    // Check if we should skip for returning visitors
    $first_visit_only = get_option('flying_scripts_first_visit_only');
    if ($first_visit_only && isset($_COOKIE['flying_scripts_visitor'])) {
        return $html;
    }

    // Parse HTML
    $newHtml = str_get_html($html);

    // Not HTML, return original
    if (!is_object($newHtml)) {
        return $html;
    }

    $include_list = get_option('flying_scripts_include_list');
    $jquery_dependent = get_option('flying_scripts_jquery_dependent');

    foreach ($newHtml->find("script[!type],script[type='text/javascript']") as $script) {
        $script_text = $script->outertext;
        // Skip jQuery core scripts and flying-scripts
        if (strpos($script_text, 'jquery.min.js') !== false || 
            strpos($script_text, 'jquery.js') !== false || 
            $script->getAttribute('id') === 'flying-scripts') {
            continue;
        }
        
        // Skip Elementor scripts to prevent breaking the editor
        if (strpos($script_text, 'elementor') !== false) {
            continue;
        }
        
        if (flying_scripts_is_keyword_included($script_text, $include_list)) {
            if (flying_scripts_is_keyword_included($script_text, $jquery_dependent)) {
                $script->setAttribute("data-type", "jquery-lazy");
                if ($script->getAttribute("src")) {
                    $script->setAttribute("data-src", $script->getAttribute("src"));
                    $script->removeAttribute("src");
                } else {
                    $script->setAttribute("data-src", "data:text/javascript;base64,".base64_encode($script->innertext));
                    $script->innertext="";
                }
            } else {
                $script->setAttribute("data-type", "lazy");
                if ($script->getAttribute("src")) {
                    $script->setAttribute("data-src", $script->getAttribute("src"));
                    $script->removeAttribute("src");
                } else {
                    $script->setAttribute("data-src", "data:text/javascript;base64,".base64_encode($script->innertext));
                    $script->innertext="";
                }
            }
        }
    }
    
    return $newHtml;
}

// Modified condition to use the new skip function
if (!flying_scripts_should_skip_processing()) {
    ob_start("flying_scripts_rewrite_html");
}

// W3TC HTML rewrite
add_filter('w3tc_process_content', function ($buffer) {
    if (flying_scripts_should_skip_processing()) {
        return $buffer;
    }
    return flying_scripts_rewrite_html($buffer);
});
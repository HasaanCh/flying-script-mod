<?php
function flying_scripts_inject_js() {
    // Get options with defaults as fallback for safety
    $timeout = intval(get_option('flying_scripts_timeout', 5));
    $first_visit_only = get_option('flying_scripts_first_visit_only', 0) ? true : false;
    ?>
<script type="text/javascript" id="flying-scripts">
const loadScriptsTimer=setTimeout(loadScripts,<?php echo $timeout ?>*1000);
const userInteractionEvents=['click', 'mousemove', 'keydown', 'touchstart', 'touchmove', 'wheel'];
userInteractionEvents.forEach(function(event){window.addEventListener(event,triggerScriptLoader,{passive:!0})});
function triggerScriptLoader(){loadScripts();clearTimeout(loadScriptsTimer);userInteractionEvents.forEach(function(event){window.removeEventListener(event,triggerScriptLoader,{passive:!0})})}
function loadScripts(){
    document.querySelectorAll("script[data-type='lazy']").forEach(function(elem){elem.setAttribute("src",elem.getAttribute("data-src"))});
    <?php if ($first_visit_only) : ?>
    // Set cookie for returning visitors - expires in 30 days
    document.cookie = "flying_scripts_visitor=1; max-age=2592000; path=/";
    <?php endif; ?>
}
</script>
    <?php
}

add_action('wp_print_footer_scripts', 'flying_scripts_inject_js');
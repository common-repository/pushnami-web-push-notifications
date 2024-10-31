<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

cp_options_remove();

function cp_options_remove()
{
    delete_option('pushnami_api_key');
    delete_option('pushnami_post_types');

    // legacy options not implemented
    // delete_option('pushnami_ab');
    // delete_option('pushnami_icon');

    delete_option('pushnami_prompt');
    delete_option('pushnami_update');
    delete_option('pushnami_advanced_optin');
    delete_option('pushnami_prompt_trigger');
    delete_option('pushnami_prompt_trigger_id');
    delete_option('pushnami_prompt_delay');
    delete_option('pushnami_prompt_delay_time');

    delete_option('pushnami_debug');
    delete_option('pushnami_debuger');
    delete_option('pushnami_api_url');
    delete_option('pushnami_gcm_id');
}

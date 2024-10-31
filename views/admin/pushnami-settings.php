<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly
?>
<div class="wrap">

    <?php
        // custom css and js
        add_action('admin_enqueue_scripts', 'cstm_css_and_js');

        function push_css_and_js() {
            wp_enqueue_style( 'style', plugins_url( '/assets/css/style.css' , __FILE__ ) );
            wp_register_script( 'scripts', plugins_url( '/assets/js/scripts.js' , __FILE__ ), array( 'jquery' ), false, true );
            wp_enqueue_script( 'scripts' );
        }
    ?>

    <h2>Pushnami - Settings</h2>

    <?php

        if (get_option('pushnami_collision_manifest.json') && !get_option('pushnami_use_custom_manifest')) {
            ?><div class="error">
                <form method="post" action="">
                    <?php wp_nonce_field('wp_pushnami_update_manifest', 'wp_pushnami_update_manifest'); ?>
                    <p>
                        An existing manifest.json file found! Update manifest.json file with Pushnami's options?
                    </p>
                    <?php
                        submit_button( 'Update Now', 'secondary', 'pn-update-manifest', true );
                    ?>
                </form>
            </div><?php
        }

        if (get_option('pushnami_collision_service-worker.js') && !get_option('pushnami_use_custom_manifest')) {
            ?><div class="error">
                <form method="post" action="">
                    <?php wp_nonce_field('wp_pushnami_update_worker', 'wp_pushnami_update_worker'); ?>
                    <p>
                        An existing service-worker.js file found! Replace file with Pushnami's service-worker.js?
                    </p>
                    <?php
                        submit_button( 'Replace Now', 'secondary', 'pn-update-worker', true );
                    ?>
                </form>
            </div><?php
        }

        if (get_option('pushnami_json_error_manifest.json')) {
            ?><div class="error">
                <form method="post" action="">
                    <?php wp_nonce_field('wp_pushnami_fix_manifest', 'wp_pushnami_fix_manifest'); ?>
                    <p>
                        Invalid or malformed manifest.json file. Manually fix or replace with Pushnami's default manifest.json?
                    </p>
                    <?php
                        submit_button( 'Replace Now', 'secondary', 'pn-fix-manifest', true );
                    ?>
                </form>
            </div><?php
        }

        if(isset($_POST['pn-save-changes'])) {
            if(isset($_POST) && isset($_POST['wp_pushnami_settings']) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['wp_pushnami_settings'] ) ), 'wp_pushnami_settings' )) {
                if(isset($_POST['pushnami_api_key']) && !empty($_POST['pushnami_api_key'])) {
                    update_option('pushnami_api_key', sanitize_text_field($_POST['pushnami_api_key']));
                }

                if(isset($_POST['pushnami_sw_paths']) ) {
                    if(!empty($_POST['pushnami_sw_paths'])) {
                        update_option('pushnami_sw_paths', sanitize_text_field($_POST['pushnami_sw_paths']));
                    } else if(empty($_POST['pushnami_sw_paths'])) {
                        delete_option('pushnami_sw_paths');
                    }
                }

                if(isset($_POST['pushnami_api_url']) ) {
                    if(!empty($_POST['pushnami_api_url'])) {
                        update_option('pushnami_api_url', sanitize_text_field($_POST['pushnami_api_url']));
                    } else if(empty($_POST['pushnami_api_url'])) {
                        delete_option('pushnami_api_url');
                    }
                }

                $write_error = false;
                if(isset($_POST['pushnami_gcm_id']) && !empty($_POST['pushnami_gcm_id']) && is_numeric($_POST['pushnami_gcm_id'])) {
                    update_option('pushnami_gcm_id', sanitize_text_field($_POST['pushnami_gcm_id']));
                    $form_url = 'admin.php?page=pushnami-settings';

                    $gcm_sender_id = get_option('pushnami_gcm_id');

                    $manifest_file = '{"gcm_sender_id": "'.$gcm_sender_id.'"}';
                    $write_result = WPPushnami::writeFile($form_url, $manifest_file, 'manifest.json');
                    if (is_wp_error( $write_result )) $write_error = $write_result->get_error_message() . ' manifest.json';
                } else if(isset($_POST['pushnami_gcm_id']) && empty($_POST['pushnami_gcm_id'])) {
                    delete_option('pushnami_gcm_id');
                    $form_url = 'admin.php?page=pushnami-settings';
                    $manifest_file = '{"gcm_sender_id": "' . PUSHNAMI_DEFAULT_GCM .'"}';
                    $write_result = WPPushnami::writeFile($form_url, $manifest_file, 'manifest.json');
                    if (is_wp_error( $write_result )) $write_error = $write_result->get_error_message() . ' manifest.json';
                }

                if(isset($_POST['pushnami_pages_to_exclude']) && !empty($_POST['pushnami_pages_to_exclude'])) {
                    update_option('pushnami_pages_to_exclude', sanitize_text_field($_POST['pushnami_pages_to_exclude']));
                } else if(isset($_POST['pushnami_pages_to_exclude']) && empty($_POST['pushnami_pages_to_exclude'])) {
                    delete_option('pushnami_pages_to_exclude');
                }

                // if(isset($_POST['pushnami_ab']) && !empty($_POST['pushnami_ab'])) {
                //     update_option('pushnami_ab', true);
                // } else {
                //     update_option('pushnami_ab', false);
                // }

                if(isset($_POST['pushnami_prompt']) && !empty($_POST['pushnami_prompt'])) {
                    update_option('pushnami_prompt', '1');
                } else {
                    update_option('pushnami_prompt', '0');
                }

                if(isset($_POST['track_category']) && !empty($_POST['track_category'])) {
                    update_option('track_category', '1');
                } else {
                    update_option('track_category', '0');
                }

                if(isset($_POST['pushnami_update']) && !empty($_POST['pushnami_update'])) {
                    update_option('pushnami_update', '1');
                } else {
                    update_option('pushnami_update', '0');
                }

                if(isset($_POST['pushnami_advanced_optin']) && !empty($_POST['pushnami_advanced_optin'])) {
                    update_option('pushnami_advanced_optin', true);
                } else {
                    update_option('pushnami_advanced_optin', false);
                }

                if(isset($_POST['pushnami_prompt_trigger']) && !empty($_POST['pushnami_prompt_trigger'])) {
                    update_option('pushnami_prompt_trigger', true);
                    update_option('pushnami_prompt_trigger_id', sanitize_text_field($_POST['pushnami_prompt_trigger_id']));
                } else {
                    update_option('pushnami_prompt_trigger', false);
                    delete_option('pushnami_prompt_trigger_id');
                }

                if(isset($_POST['pushnami_prompt_delay']) && !empty($_POST['pushnami_prompt_delay'])) {
                    update_option('pushnami_prompt_delay', true);
                    update_option('pushnami_prompt_delay_time', sanitize_text_field($_POST['pushnami_prompt_delay_time']));
                } else {
                    update_option('pushnami_prompt_delay', false);
                    delete_option('pushnami_prompt_delay_time');
                }

                if(isset($_POST['pushnami_debug']) && !empty($_POST['pushnami_debug'])) {
                    update_option('pushnami_debug', true);
                } else {
                    update_option('pushnami_debug', false);
                    delete_option('pushnami_debuger');
                    delete_option('pushnami_api_url');
                    delete_option('pushnami_gcm_id');
                }

                // if(isset($_POST['pushnami_debuger']) && !empty($_POST['pushnami_debuger'])) {
                //     update_option('pushnami_debuger', true);
                // } else {
                //     update_option('pushnami_debuger', false);
                // }

                WPPushnami::update();

                if (!$write_error) {
                    echo '<div class="updated"><p>Changes saved successfully!</p></div>';
                }
            } else {
                echo '<div class="error"><p>Something went wrong!</p></div>';
            }
        }

        if(isset($_POST['pn-update-manifest'])) {
            if(isset($_POST) && isset($_POST['wp_pushnami_update_manifest']) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['wp_pushnami_update_manifest'] ) ), 'wp_pushnami_update_manifest' )) {
                WPPushnami::forceUpdateManifest();
            }
        }

        if(isset($_POST['pn-update-worker'])) {
            if(isset($_POST) && isset($_POST['wp_pushnami_update_worker']) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['wp_pushnami_update_worker'] ) ), 'wp_pushnami_update_worker' )) {
                WPPushnami::forceUpdateWorker();
            }
        }

        if(isset($_POST['pn-fix-manifest'])) {
            if(isset($_POST) && isset($_POST['wp_pushnami_fix_manifest']) && wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['wp_pushnami_fix_manifest'] ) ), 'wp_pushnami_fix_manifest' )) {
                WPPushnami::forceUpdateManifest();
            }
        }

    ?>

    <p>
        To setup Pushnami, you can get an API key from your Pushnami website <a target="_blank" href="https://admin-v3.pushnami.com/pages/websites">settings page here</a>.<br/>
        If you dont have an account, signup for a <a target="_blank" href="https://pushnami.com/account-creation/?utm_source=wordpress&utm_medium=plugin&utm_campaign=settings">free trial here</a>.
    </p>

    <form method="post" action="">
        <?php wp_nonce_field('wp_pushnami_settings', 'wp_pushnami_settings'); ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><h3>Website Options</h3></th>
            </tr>
            <tr valign="top">
                <th scope="row">API Key <small>(required)</small></th>
                <td><input type="text" size="64" name="pushnami_api_key" value="<?php echo esc_attr( get_option('pushnami_api_key') ); ?>" placeholder="API Key" required/></td>
            </tr>
            <tr valign="top">
                <th scope="row">Pages to Exclude<br/><i>(separated by ",")</i></th>
                <td><textarea rows="4" cols="67" name="pushnami_pages_to_exclude" placeholder="Page1,Page2,Page3"/><?php echo esc_attr( get_option('pushnami_pages_to_exclude') ); ?></textarea></td>
            </tr>
        </table>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><h3>Advanced Options</h3></th>
            </tr>
            <tr valign="top" class="advanced_option">
                <th scope="row">Show Prompt</th>
                <td>
                    <input
                        type="checkbox"
                        name="pushnami_prompt"
                        id="pushnami_prompt"
                        value="true"
                        <?php
                            $checked = !(get_option('pushnami_prompt') === '0');
                            checked($checked);
                        ?>
                    />
                </td>
            </tr>
            <tr valign="top" class="advanced_option">
                <th scope="row">Track Categories Read</th>
                <td>
                    <input
                        type="checkbox"
                        name="track_category"
                        id="track_category"
                        value="true"
                        <?php
                            $checked = !(get_option('track_category') === '0');
                            checked($checked);
                        ?>
                    />
                </td>
            </tr>
            <tr valign="top" class="advanced_option">
                <th scope="row">Update Subscriber</th>
                <td>
                    <input
                        type="checkbox"
                        name="pushnami_update"
                        id="pushnami_update"
                        value="true"
                        <?php
                            $checked = !(get_option('pushnami_update') === '0');
                            checked($checked);
                        ?>
                    />                
                </td>
            </tr>
        </table>
        <div class="option_notice danger" id="prompt_update_error" style="display: none;">
            <p>"Show Prompt" or "Update Subscriber" are required</p>
        </div>
        <table class="form-table">
            <tr valign="top" class="advanced_option">
                <th scope="row">Show Advanced Opt-in</th>
                <td>
                    <input
                        type="checkbox"
                        name="pushnami_advanced_optin"
                        id="pushnami_advanced_optin"
                        value="true"
                        <?php checked(get_option('pushnami_advanced_optin')); ?>
                    />
                </td>
            </tr>
        </table>
        <div class="option_notice warning" id="advanced_optin_notice" style="<?php if (!get_option('pushnami_advanced_optin')) echo 'display:none;'; ?>">
            <p>Warning - "Advanced Opt-in" must be enabled by Pushnami Support.</p>
        </div>
        <table class="form-table">
            <tr valign="top" class="advanced_option">
                <th scope="row">Trigger Prompt on Click</th>
                <td>
                    <input
                        type="checkbox"
                        name="pushnami_prompt_trigger"
                        id="pushnami_prompt_trigger"
                        value="true"
                        <?php checked(get_option('pushnami_prompt_trigger')); ?>
                    />
                </td>
            </tr>
            <tr
                valign="top"
                class="advanced_option sub_option"
                id="pushnami_prompt_trigger_id"
                style="<?php if (!get_option('pushnami_prompt_trigger')) echo 'display:none;' ?>'">
                <th scope="row"><span>Button ID</span></th>
                <td>
                    <input
                        type="text"
                        name="pushnami_prompt_trigger_id"
                        value="<?php
                            if (get_option('pushnami_prompt_trigger_id')) {
                                echo esc_attr( get_option('pushnami_prompt_trigger_id') );
                            } else {
                                echo 'MyBtn';
                            }
                        ?>"
                        placeholder="Button ID"
                        size="64"
                    />
                </td>
            </tr>
            <tr valign="top" class="advanced_option">
                <th scope="row">Prompt Delay</th>
                <td>
                    <input
                        type="checkbox"
                        name="pushnami_prompt_delay"
                        id="pushnami_prompt_delay"
                        value="true"
                        <?php checked(get_option('pushnami_prompt_delay')); ?>
                    />
                </td>
            </tr>
            <tr
                valign="top"
                class="advanced_option sub_option"
                id="pushnami_prompt_delay_time"
                style="<?php if (!get_option('pushnami_prompt_delay')) echo 'display:none;' ?>">
                <th scope="row"><span>Delay (ms)</span></th>
                <td>
                    <input
                        type="number"
                        name="pushnami_prompt_delay_time"
                        value="1500"
                        placeholder="Delay"
                        size="64"
                    />
                </td>
            </tr>
            <!-- <tr valign="top">
                <th scope="row">Enable A/B with j=2</th>
                <td><input type="checkbox" name="pushnami_ab" value="true" <?php //checked(get_option('pushnami_ab')); ?> /></td>
            </tr> -->
            <tr valign="top" class="advanced_option">
                <th scope="row">Extra Worker Path</th>
                <td><input type="text" size="64" name="pushnami_sw_paths" value="<?php echo esc_attr( get_option('pushnami_sw_paths') ); ?>" placeholder="service-worker.js"/></td>
            </tr>
            <tr valign="top" class="debug_option" style="<?php if (!get_option('pushnami_debug')) echo 'display:none;'; ?>">
                <th scope="row"><h3>Debug Options</h3></th>
            </tr>
            <tr valign="top" class="debug_option" style="<?php if (!get_option('pushnami_debug')) echo 'display:none;'; ?>">
                <th scope="row">Show Debug Options</th>
                <td>
                    <input
                        type="checkbox"
                        name="pushnami_debug"
                        id="pushnami_debug"
                        <?php checked(get_option('pushnami_debug')); ?>
                    />
                </td>
            </tr>
            <!-- <tr valign="top" class="debug_option" style="<?php if (!get_option('pushnami_debug')) echo 'display:none;'; ?>">
                <th scope="row">Enable Debuger</th>
                <td><input type="checkbox" name="pushnami_debuger" value="true" <?php checked(get_option('pushnami_debuger')); ?> /></td>
            </tr> -->
            <tr valign="top" class="debug_option" style="<?php if (!get_option('pushnami_debug')) echo 'display:none;'; ?>">
                <th scope="row">API URL</th>
                <td><input type="text" size="64" name="pushnami_api_url" value="<?php echo esc_attr( get_option('pushnami_api_url') ); ?>" placeholder="API URL"/></td>
            </tr>
            <tr valign="top" class="debug_option" style="<?php if (!get_option('pushnami_debug')) echo 'display:none;'; ?>">
                <th scope="row">GCM Sender ID</th>
                <td><input type="text" size="64" name="pushnami_gcm_id" value="<?php echo esc_attr( get_option('pushnami_gcm_id') ); ?>" placeholder="GCM Sender ID"/></td>
            </tr>
        </table>

        <?php
            submit_button( 'Save Changes', 'primary', 'pn-save-changes', true, array( 'id' => 'submit_button') );
        ?>
    </form>
</div>

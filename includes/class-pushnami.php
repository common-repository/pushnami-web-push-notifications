<?php if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class WPPushnami
{

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function init()
    {

        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->hooks();
    }

    /**
     * All hooks registration goes here.
     * @return void
     */
    private function hooks()
    {
        add_action('admin_menu', array($this, 'registerMenues'));
        add_action('wp_enqueue_scripts', array($this, 'pushnami_enqueue_scripts'));
        add_action('wp_print_scripts', array($this, 'pushnami_print_scripts'));
        add_action('wp_head', array($this, 'manifestFile'), 1, 1);
        add_action('parse_request',  array($this, 'my_plugin_parse_request'));

        $pluginDirName = basename( plugin_dir_path(  dirname( __FILE__ , 1 ) ) );
        $settingsPath = 'plugin_action_links_' . $pluginDirName . '/pushnami.php';
        add_filter($settingsPath, array($this, 'settingsLink'));
        add_filter('query_vars', array($this, 'my_plugin_query_vars'));
    }

    /**
     * Adds settings link next to plugin deactivate link
     * @param  Array $links
     * @return [string]
     */
    public function settingsLink( $links )
    {
        // Build and escape the URL.
        $url = esc_url( add_query_arg(
            'page',
            'pushnami-settings',
            get_admin_url() . 'admin.php'
        ) );
        // Create the link.
        $settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
        // Adds the link to the end of the array.
        array_unshift(
            $links,
            $settings_link
        );
        return $links;
    }

    /**
     * Manifest link in header
     * @return [type] [description]
     */
    public function manifestFile()
    {
        if (!get_option('pushnami_use_custom_manifest')) {
            echo ''
            .'<meta name="pushnami" content="wordpress-plugin"/>'.PHP_EOL
            .'<link rel="manifest" href="/manifest.json">'.PHP_EOL;
        }
    }

    /**
     * Manifest file (manifest.json) creation
     * @return void
     */
    public static function writeManifestJson($force = false, $showErrors = true)
    {
        // bail if using custom manifest (PWA for WP)
        $useCustomManifest = get_option('pushnami_use_custom_manifest');
        if ($useCustomManifest) return;

        $gcm_sender_id = "".get_option('pushnami_gcm_id');
        if (!$gcm_sender_id) $gcm_sender_id = PUSHNAMI_DEFAULT_GCM;

        $tmp_man = '{"gcm_sender_id": "' . $gcm_sender_id . '"}';
        $form_url = 'admin.php?page=pushnami-settings';

        $write_result = self::writeFile($form_url, $tmp_man, 'manifest.json', $force);
        if (is_wp_error( $write_result ) && $showErrors) {
            echo '<div class="error"><p>' . esc_html( $write_result->get_error_message() ) . ' manifest.json</p></div>';
        }
    }

    /**
     * ServiceWorker file (service-worker.js) Render Template
     * @return void
     */
    public static function renderServiceWorker()
    {
        $api_url = get_option('pushnami_api_url');
        if (!$api_url) $api_url = PUSHNAMI_DEFAULT_API_URL;

        $tmp_sw = file_get_contents(PUSHNAMI_PLUGIN_DIR . 'assets/tmp/service-worker.js.tmp');
        $tmp_sw = str_replace('KEY', get_option('pushnami_api_key'), $tmp_sw);
        $tmp_sw = str_replace('API_PATH', $api_url, $tmp_sw);

        return $tmp_sw;
    }

    /**
     * ServiceWorker file (service-worker.js) Write File
     * @return void
     */
    public static function writeServiceWorker($force = false, $showErrors = true)
    {
        // bail if using custom manifest (PWA for WP)
        $useCustomManifest = get_option('pushnami_use_custom_manifest');
        if ($useCustomManifest) return;

        $tmp_sw = self::renderServiceWorker();
        $form_url = 'admin.php?page=pushnami-settings';

        $write_result = self::writeFile($form_url, $tmp_sw, 'service-worker.js', $force);
        if (is_wp_error( $write_result ) && $showErrors) {
            echo '<div class="error"><p>' . esc_html( $write_result->get_error_message() ) . ' service-worker.js</p></div>';
        }
        return $write_result;
    }

    /**
     * Admin menus registration
     * @return void
     */
    public function registerMenues()
    {
        add_menu_page('Pushnami', 'Pushnami', 'manage_options', 'pushnami-settings', array($this, 'registerSettingsPage'), 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2ZXJzaW9uPSIxLjEiIHdpZHRoPSIxMTEiIGhlaWdodD0iMTExIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCA1OTIuNyAxMjgiIHhtbDpzcGFjZT0icHJlc2VydmUiPgoJPGcgY2xhc3M9ImN1cnJlbnRMYXllciI+CgkJPHRpdGxlPlB1c2huYW1pLUxvZ288L3RpdGxlPgoJCTxwb2x5Z29uIGlkPSJzdmdfOSIgZmlsbD0iI2ZmZiIgcG9pbnRzPSI1NS42MDAwMDEzMzUxNDQwNCw1NS42MDAwMDMyNDI0OTI2NzYgNTUuNjAwMDAxMzM1MTQ0MDQsMCAxMTEuMTk5OTk5ODA5MjY1MTQsMCAiLz4KCQk8cG9seWdvbiBpZD0ic3ZnXzEwIiBmaWxsPSIjZmZmIiBwb2ludHM9IjAsMTExLjIwMDAwMTcxNjYxMzc3IDAsNTUuNjAwMDAzMjQyNDkyNjc2IDU1LjYwMDAwMTMzNTE0NDA0LDU1LjYwMDAwMzI0MjQ5MjY3NiAiLz4KCQk8cG9seWdvbiBpZD0ic3ZnXzExIiBmaWxsPSIjZmZmIiBwb2ludHM9IjAsNTUuNjAwMDAzMjQyNDkyNjc2IDAsMCA1NS42MDAwMDEzMzUxNDQwNCwwICIvPgoJPC9nPgo8L3N2Zz4=', 30);
        add_submenu_page('pushnami-settings', 'Settings', 'Settings', 'manage_options', 'pushnami-settings', array($this, 'registerSettingsPage'));
    }

    /**
     * Write File generic function.
     * @param  String $form_url     URL of the form to return if no permissions
     * @param  String $file_content Content of the file to be writen
     * @param  String $filename     Name of the new file
     * @return void
     */
    public static function writeFile($form_url, $updated_content, $filename, $force = false)
    {
        global $wp_filesystem;

        //check_admin_referer('pushnami');

        $method = '';
        $context = get_home_path();

        //$form_url = wp_nonce_url($form_url, 'pushnami'); //page url with nonce value

        if (!self::initFilesystem($form_url, $method, ABSPATH)) {
            return false;
        }

        $target_dir = $wp_filesystem->find_folder($context);
        $target_file = trailingslashit($target_dir) . $filename;
        $file_exists = file_exists($target_file);

        // file being updated is json
        if (self::endsWith($filename, '.json')) {
            $default_json = json_decode(file_get_contents(PUSHNAMI_PLUGIN_DIR . 'assets/tmp/' . $filename . '.tmp'));
            $updated_json = json_decode('{}');
            $json_valid = false;

            // if file exist, then check if json is valid
            if ($file_exists) {
                $updated_json = json_decode(file_get_contents($target_file));
                $json_valid = $updated_json !== null && JSON_ERROR_NONE === json_last_error();
            }

            // if file exists and valid json, check if was installed by this plugin
            if ($file_exists && $json_valid) {
                if (!$force && (!property_exists($updated_json, 'pn_wordpress') || $updated_json->pn_wordpress != true)) {
                    update_option('pushnami_collision_'. $filename, true);
                    return;
                }
            }

            // if file exists and json invalid, stop and display error
            if (!$force && $file_exists && !$json_valid) {
                update_option('pushnami_json_error_'. $filename, true);
                return;
            }

            // if file not found or error parsing, then use file's default props
            if (!$file_exists || !$json_valid) {
                $updated_json = $default_json;
            }

            // add any missing props
            foreach ($default_json as $name => $value) {
                if (!property_exists($updated_json, $name)) {
                    $updated_json->{$name} = $value;
                }
            }

            // update manifest with updated props
            foreach (json_decode($updated_content) as $name => $value) {
                $updated_json->{$name} = $value;
            }

            $updated_content = json_encode($updated_json, JSON_PRETTY_PRINT);
        } else {
            // handle service-worker collision detection
            if ($file_exists) {
                $existing_content = file_get_contents($target_file);

                $pn_wordpress_installed = strpos($existing_content, '/*pn_wordpress*/') > 0;

                if (!$force && !$pn_wordpress_installed) {
                    update_option('pushnami_collision_'. $filename, true);
                    return;
                }

                if (!$force && $pn_wordpress_installed) {
                    delete_option('pushnami_collision_'. $filename);
                }
            }
        }

        // update file contents and return error object issue writing file
        if (!$wp_filesystem->put_contents($target_file, $updated_content, FS_CHMOD_FILE)) {
            // echo 'error';
            return new WP_Error('writing_error', 'Error when writing file');
        }

        // forced updated file, so remove collision option flag
        if ($force) {
            delete_option('pushnami_json_error_'. $filename);
            delete_option('pushnami_collision_'. $filename);
            wp_redirect($form_url);
        }

        return $updated_content;
    }

    /**
     * Initialization of the FileSystem class
     * @param  String $form_url
     * @param  String $method
     * @param  String $context
     * @param  String $fields
     * @return void
     */
    public static function initFilesystem($form_url, $method, $context, $fields = null)
    {
        global $wp_filesystem;
        include_once ABSPATH . 'wp-admin/includes/file.php';
        if (false === ($creds = request_filesystem_credentials($form_url, $method, false, $context, $fields))) {

            return false;
        }

        if (!WP_Filesystem($creds)) {

            request_filesystem_credentials($form_url, $method, true, $context);
            return false;
        }

        return true; //filesystem object successfully initiated
    }

    /**
     * Register the JS files and vars
     * [inline scripts WP >= 4.5]
     * @return void
     */
    public function pushnami_enqueue_scripts()
    {
        // check wordpress version; prevent if not v4.5 or greater
        $wp_version = get_bloginfo('version');
        if (!version_compare($wp_version, '4.5', '>=')) return;

        if (self::can_inline_script()) {
            // get pushnami script options
            $options = self::get_script_options();

            // prevents script insert when api_key not set yet
            if (!$options->api_key) return;

            // no advanced options toggled, so use EASY install script
            if (self::use_easy_script()) {
                wp_enqueue_script('pushnami_script', $options->api_url . '/scripts/v1/push/' . $options->api_key, false, null);
                return;
            }

            // get rendered advanced pushnami script string
            $script = self::render_inline_script($options);

            // inline scripts WP >= 4.5
            wp_register_script('pushnami_script', '');
            wp_enqueue_script('pushnami_script');
            wp_add_inline_script('pushnami_script', $script);
        }
    }

    /**
     * Register the JS files and vars
     * [inline scripts WP < 4.5]
     * @return void
     */
    public function pushnami_print_scripts()
    {
        // check wordpress version; prevent if not less than v4.5
        $wp_version = get_bloginfo('version');
        if (!version_compare($wp_version, '4.5', '<')) return;

        if (self::can_inline_script()) {
            // get pushnami script options
            $options = self::get_script_options();

            // prevents script insert when api_key not set yet
            if (!$options->api_key) return;

            // no advanced options toggled, so use EASY install script
            if (self::use_easy_script()) {
                wp_enqueue_script('pushnami_script', $options->api_url . '/scripts/v1/push/' . $options->api_key, false, null);
                return;
            }

            // get rendered advanced pushnami script string
            $script = self::render_inline_script($options);

            // inline scripts WP < 4.5
            wp_register_script('pushnami_script', '');
            wp_enqueue_script('pushnami_script');
            wp_add_inline_script('pushnami_script', $script);
        }
    }

    /**
     * Register Settings Admin Page
     * @return void
     */
    public function registerSettingsPage()
    {
        wp_enqueue_media();
        include_once PUSHNAMI_PLUGIN_DIR . 'views/admin/pushnami-settings.php';
    }

    /**
     * Registration hook
     * @return void
     */
    public static function installFunctions()
    {
        self::checkForPWAforWP();
        self::writeServiceWorker(false, false);
        self::writeManifestJson(false, false);
    }

    /**
     * Update hook
     * @return void
     */
    public static function update()
    {
        self::checkForPWAforWP();
        self::writeServiceWorker();
        self::writeManifestJson();
    }

    /**
     * Check for PWA for WP compatibility
     * @return void
     */
    public static function checkForPWAforWP()
    {
        if (!class_exists( 'PWAFORWP_Service_Worker' )) {
            update_option('pushnami_use_custom_manifest', false);
        }
    }

    /**
     * Https requirement Notice
     * @return void
     */
    public function checkSiteConfigNotice()
    {
        ?>
	    <div class="error">
	        <p>Your Site URL should be set to HTTPS for Chrome Push Notifications Plugin to work.</p>
	    </div>
	    <?php
    }

    /**
     * Check if Site url is set to HTTPS
     * @return void
     */
    public function checkSSL()
    {
        return strpos(get_option('siteurl'), 'https://') !== false;
    }

    /**
     * Fix http to https
     * @param  string $url
     * @return string
     */
    public static function fixHttpsURL($url)
    {
        if (stripos($url, 'http://') === 0) {
            $url = str_replace('http://', 'https://', $url);
        }

        return $url;
    }

    /**
     * Determines whether string ends with substring
     * @param  String $haystack
     * @param  String $needle
     * @return string
     */
    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Constructs object containing pushnami script options/defaults
     * @return (object)[]
     */
    public static function get_script_options()
    {
        $options = (object)[];

        $options->api_key = get_option('pushnami_api_key');
        $options->api_url = get_option('pushnami_api_url');
        $options->prompt = !(get_option('pushnami_prompt') === '0');
        $options->update = !(get_option('pushnami_update') === '0') || get_option('track_category');
        $options->optin = get_option('pushnami_advanced_optin');
        $options->trigger = get_option('pushnami_prompt_trigger');
        $options->trigger_id = get_option('pushnami_prompt_trigger_id');
        $options->delay = get_option('pushnami_prompt_delay');
        $options->delay_time = get_option('pushnami_prompt_delay_time');
        $options->use_custom_manifest = get_option('pushnami_use_custom_manifest') || false;
        $options->use_custom_manifest_url = get_option('pushnami_use_custom_manifest_url');
        $options->swPath = null;

        // default api url if option not set
        if (!$options->api_url) $options->api_url = PUSHNAMI_DEFAULT_API_URL;

        return $options;
    }

    /**
     * Updates pushnami script options
     * (just use_custom_manifest for PWA for WP plugin extension)
     * @param  Array $options
     * @return void
     */
    public static function save_pushnami_options($options) {
        if (property_exists($options, 'use_custom_manifest')) {
            if ($options->use_custom_manifest) {
                update_option('pushnami_use_custom_manifest', true);
            } else {
                update_option('pushnami_use_custom_manifest', false);
            }
        }
        if (property_exists($options, 'use_custom_manifest_url')) {
            update_option('pushnami_use_custom_manifest_url', $options->use_custom_manifest_url);
        }
    }

    /**
     * Render advanced script
     * @param  Array $options
     * @return string
     */
    public static function render_inline_script($options)
    {
        if (!$options) return '';

        // render script prompt options
        $prompt_options = array();
        if ($options->trigger) array_push($prompt_options, '"onClick":"' . $options->trigger_id . '"');
        if ($options->delay) array_push($prompt_options, '"delay":' . $options->delay_time . '');
        if (sizeof($prompt_options)) $prompt_options = '{' . join(',', $prompt_options) . '}';
        else $prompt_options = '';

        $update_options = array();
        // Get post categories
        $categories = get_the_category();
        if (!empty($categories)) {
            // In case of multiple post categories, use the first one
            $category = $categories[0]->name;
            array_push($update_options, '"last_category":"' . $category . '"');
        }
        if (sizeof($update_options)
            && get_option('track_category') === '1'
        ) {
            $update_options = '{' . join(',', $update_options) . '}';
        }
        else $update_options = '';

        $script_options  = '';

        if ($options->swPath) {
            $script_options .= 'if (\'setSWPath\' in Pushnami) '
                .'Pushnami.setSWPath(\''.$options->swPath.'\');'
                .PHP_EOL.'        '
            ;
        }

        // render script options
        $script_options  .= 'Pushnami';
        // if ($options->swPath) $script_options .= PHP_EOL . '            .setSWPath(\''.$options->swPath.'\')';
        if ($options->update) $script_options .= PHP_EOL . '            .update(' . $update_options . ')';
        if ($options->optin)  $script_options .= PHP_EOL . '            .showOverlay({force:true})';
        if ($options->prompt) $script_options .= PHP_EOL . '            .prompt(' . $prompt_options . ')';
        $script_options .= ';';

        // render script template with options
        $script = file_get_contents(PUSHNAMI_PLUGIN_DIR . 'assets/tmp/advanced-script.js.tmp');
        $script = str_replace('/*API_KEY*/', $options->api_key, $script);
        $script = str_replace('/*API_PATH*/', $options->api_url, $script);
        $script = str_replace('/*OPTIONS*/', $script_options, $script);

        return $script;
    }

    /**
     * Checks whether script should be inlined to current page
     * @return boolean
     */
    public function can_inline_script() {
        $excludePagesArray = explode(",", get_option('pushnami_pages_to_exclude'));
        $useCustomManifest = get_option('pushnami_use_custom_manifest');
        return (
            !( get_option('pushnami_ab') &&
            (isset($_GET['j']) && $_GET['j'] === '2') ) &&
            !is_page($excludePagesArray) &&
            !$useCustomManifest
        );
    }

    /**
     * Checks whether easy script should be used
     * @return boolean
     */
    public function use_easy_script() {
        return (
            !(get_option('pushnami_prompt') === '0') &&
            !(get_option('pushnami_update') === '0') &&
            !get_option('pushnami_advanced_optin') &&
            !get_option('pushnami_prompt_trigger') &&
            !get_option('pushnami_prompt_delay') &&
            !get_option('track_category')
        );
    }

    /**
     * Force update on service-worker.js
     * @return void
     */
    public static function forceUpdateWorker()
    {
        self::writeServiceWorker(true);
    }

    /**
     * Force update on manifest.json
     * @return void
     */
    public static function forceUpdateManifest()
    {
        self::writeManifestJson(true);
    }

    public function my_plugin_parse_request($wp) {
        $swPathsStr = get_option('pushnami_sw_paths');
        if ($swPathsStr) {
            $swPathsArr = explode(",", $swPathsStr);
            foreach ($swPathsArr as $swPath) {
                $swPathKeyValue = explode("=", trim($swPath));
                $count = count($swPathKeyValue);
                if ($count < 2) continue; // skip non query param paths

                $key = preg_replace('/^\?{1}/', '', $swPathKeyValue[0]);
                $val = $swPathKeyValue[1];
                if (array_key_exists($key, $wp->query_vars)
                        && $wp->query_vars[$key] == $val) {
                    header( 'Content-Type: application/javascript');
                    header( 'Service-Worker-Allowed: /');
                    $worker = self::renderServiceWorker();
                    die($worker);
                }
            }
        }
    }

    public function my_plugin_query_vars($vars) {
        $swPathsStr = get_option('pushnami_sw_paths');
        if ($swPathsStr) {
            $swPathsArr = explode(",", $swPathsStr);

            foreach ($swPathsArr as $swPath) {
                $swPathKeyValue = explode("=", trim($swPath));
                $count = count($swPathKeyValue);
                if ($count < 2) continue; // skip non query param paths

                $key = preg_replace('/^\?{1}/', '', $swPathKeyValue[0]);
                $vars[] = $key;
            }
        }

        return $vars;
    }
}

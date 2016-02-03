<?php
if (!class_exists('ZPM_Client')) {

    class ZPM_Client {

        /**
         * The API endpoint. Configured through the class's constructor.
         *
         * @var String  The API endpoint.
         */
        private $api_endpoint;

        /**
         * The plugin id (slug) used for this plugin on the Plugin Manager site.
         * Configured through the class's constructor.
         *
         * @var int     The plugin id of the related plugin in the Plugin manager.
         */
        private $plugin_id;

        /**
         * The name of the plugin using this class. Configured in the class's constructor.
         *
         * @var int     The name of the plugin  using this class.
         */
        private $plugin_name;

        /**
         * The text domain of the plugin using this class.
         * Populated in the class's constructor.
         *
         * @var String  The text domain of the plugin.
         */
        private $text_domain;

        /**
         * The absoluth path of the requested plugin
         * Populated in the class's constructor.
         *
         * @var String  The text domain of the plugin.
         */
        private $plugin_file;

        /**
         * The fake object to modify the WP update request
         *
         * @var array
         */
        private $zip_update;

        /**
         * The caller main file
         *
         * @var array
         */
        private $file;

        /**
         * 
         */
        
        private $protected_files;
        
        /**
         * Initializes the plugin manager client.
         *
         * @param $plugin_id   	string  The plugin id of the related plugin in the Plugin manager.
         * @param $plugin_name 	string  The name of the plugin, used for menus
         * @param $text_domain  string  plugin text domain, used for localizing the settings screens.
         * @param $api_url      string  The URL to the license manager API (your license server)
         * @param $plugin_file  string  The full path to the plugin's main file
         */
        public function __construct($api_url, $text_domain, $file, $plugin_name) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-hackattempts-activator.php';

            //Change this when database is modified
            $this->new_db_version = 1;

            $this->api_endpoint = $api_url;
            $this->plugin_name = $plugin_name;
            $this->text_domain = $text_domain;
            $this->file = $file;
            $this->plugin_file = WP_PLUGIN_DIR . '/' . $this->text_domain . '/' . $this->file . '.php';
            
            $config_json = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/config.json'));
            $this->protected_files = $config_json->protected_files;
            


            if (is_admin()) {
                $this->plugin_id = get_option('zpm_plugin_id');

                add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
                // Showing plugin information
                add_filter('plugins_api', array($this, 'plugins_api_handler'), 10, 3);
            }

            // The external API setup
            add_filter('query_vars', array($this, 'add_client_query_vars'));

            // Parse the requested api querys
            add_action('parse_request', array($this, 'sniff_client_requests'));

            // For Clear url
            add_action('init', array($this, 'add_client_endpoint_rules'));
        }

        /**
         * Send the activation details to the Plugin Manager
         *
         * @since    1.1
         */
        public function activate_client() {
            $data = get_plugin_data($this->plugin_file, $markup = false, $translate = false);

            $activation_data = $this->call_api(
                    'hello', array(
                'v' => $data['Version'],
                'h' => preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']),
                'n' => $data['Name']
                    )
            );
            add_option('zpm_plugin_id', $activation_data->plugin_id, '', 'yes');
            add_option('zpm_license', $activation_data->license_key, '', 'yes');
        }

        /**
         * Send the deactivation details to the Plugin Manager
         *
         * @since    1.1
         */
        public function deactivate_client() {
            $data = get_plugin_data($this->plugin_file, $markup = false, $translate = false);
            $license = get_option('zpm_license');

            $activation_data = $this->call_api(
                    'bye', array(
                'l' => $license,
                'h' => preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']),
                'n' => $data['Name']
                    )
            );

            delete_option('zpm_plugin_id');
            delete_option('zpm_license');
        }

        /**
         * Create the administration area for entering license details
         *
         * @since    1.1
         */
        public function build_structure() {
            //Show admin notice
            add_action('admin_notices', array($this, 'show_admin_notices'));
            add_action('admin_menu', array($this, 'add_license_settings_page'));
            add_action('admin_init', array($this, 'add_license_settings_fields'));
        }

        /**
         * If the license has not been configured properly, display an admin notice.
         */
        public function show_admin_notices() {

            $license = get_option('zpm_license');
            $options = get_option($this->get_settings_field_name());

            if (!isset($options['license_key'])) {
                if ($license != '') {
                    $msg = __('Please enter your license key under the Settings -> ' . $this->plugin_name . ' license to enable updates.', 'hackattempts');
                    $msg_lic = __('Your license key is: <code>%s</code>', 'hackattempts');
                    $msg_lic = sprintf($msg_lic, $license);
                    ?>
                    <div class="update update-nag">
                        <p>
                            <?php echo $msg; ?>
                        </p>

                        <p>
                            <?php echo $msg_lic; ?>
                        </p>
                    </div>
                    <?php
                }
            }
        }

        /**
         * Makes a call to the ZPM API.
         *
         * @param $method   String  The API action to invoke on the license manager site
         * @param $params   array   The parameters for the API call
         * @return          array   The API response
         */
        public function call_api($action, $params) {
            $url = $this->api_endpoint . '/' . $action;

            // Append parameters for GET request
            $url .= '?' . http_build_query($params);

            // Send the request
            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                return false;
            }

            $response_body = wp_remote_retrieve_body($response);
            $result = json_decode($response_body);

            return $result;
        }

        /**
         * Checks the API response to see if there was an error.
         *
         * @param $response mixed|object    The API response to verify
         * @return bool     True if there was an error. Otherwise false.
         */
        private function is_api_error($response) {
            if ($response === false) {
                return true;
            }

            if (!is_object($response)) {
                return true;
            }

            if (isset($response->error)) {
                return true;
            }

            return false;
        }

        /**
         * Calls the License Manager API to get the license information for the
         * current product.
         *
         * @return object|bool   The product data, or false if API call fails.
         */
        public function get_license_info() {
            $options = get_option($this->get_settings_field_name());
            if (!isset($options['license_key'])) {
                // User hasn't saved the license to settings yet. No use making the call.
                return false;
            }
            $info = $this->call_api(
                    'info', array(
                'p' => $this->plugin_id,
                'l' => urlencode($options['license_key'])
                    )
            );
            return $info;
        }

        /**
         * Checks the license manager to see if there is an update available for this plugin.
         *
         * @return object|bool  If there is an update, returns the license information.
         *                      Otherwise returns false.
         */
        public function is_update_available() {
            $license_info = $this->get_license_info();
            if ($this->is_api_error($license_info)) {
                return false;
            }

            if (version_compare($license_info->version, $this->get_local_version(), '>')) {
                return $license_info;
            }

            return false;
        }

        /**
         * @return string   The  plugin version of the local installation.
         */
        private function get_local_version() {
            $plugin_data = get_plugin_data($this->plugin_file, $markup = false, $translate = false);
            return $plugin_data['Version'];
        }

        /**
         * The filter that checks if there are updates to the plugin
         * using the License Manager API.
         *
         * @param $transient    mixed   The transient used for WordPress theme updates.
         * @return mixed        The transient with our (possible) additions.
         */
        public function check_for_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }

            if ($this->is_update_available()) {

                $info = $this->get_license_info();
                // Plugin update
                $plugin_slug = plugin_basename($this->plugin_file);

                $transient->response[$plugin_slug] = (object) array(
                            'new_version' => $info->version,
                            'package' => $info->package_url,
                            'slug' => $plugin_slug
                );
            }

            return $transient;
        }

        /**
         * Creates the settings items for entering license information (email + license key).
         */
        public function add_license_settings_page() {
            $title = sprintf(__('%s License', $this->text_domain), $this->plugin_name);

            add_options_page(
                    $title, $title, 'read', $this->get_settings_page_slug(), array($this, 'render_licenses_menu')
            );
        }

        /**
         * Creates the settings fields needed for the license settings menu.
         */
        public function add_license_settings_fields() {
            $settings_group_id = $this->plugin_id . '-license-settings-group';
            $settings_section_id = $this->plugin_id . '-license-settings-section';

            register_setting($settings_group_id, $this->get_settings_field_name());

            add_settings_section(
                    $settings_section_id, __('License', $this->text_domain), array($this, 'render_settings_section'), $settings_group_id
            );


            add_settings_field(
                    $this->plugin_id . '-license-key', __('License key', $this->text_domain), array($this, 'render_license_key_settings_field'), $settings_group_id, $settings_section_id
            );
        }

        /**
         * Renders the description for the settings section.
         */
        public function render_settings_section() {
            _e('Insert your license information to enable updates.', $this->text_domain);
        }

        /**
         * Renders the settings page for entering license information.
         */
        public function render_licenses_menu() {

            $title = sprintf(__('%s License', $this->text_domain), $this->plugin_name);
            $settings_group_id = $this->plugin_id . '-license-settings-group';
            ?>
            <div class="wrap">
                <form action='options.php' method='post'>

                    <h2><?php echo $title; ?></h2>

                    <?php
                    settings_fields($settings_group_id);
                    do_settings_sections($settings_group_id);
                    submit_button();
                    ?>

                </form>
            </div>
            <?php
        }

        /**
         * Renders the license key settings field on the license settings page.
         */
        public function render_license_key_settings_field() {
            $settings_field_name = $this->get_settings_field_name();
            $options = get_option($settings_field_name);
            ?>
            <input type='text' name='<?php echo $settings_field_name; ?>[license_key]'
                   value='<?php echo $options['license_key']; ?>' class='regular-text'>
            <?php
        }

        /**
         * A function for the WordPress "plugins_api" filter. Checks if
         * the user is requesting information about the current plugin and returns
         * its details if needed.
         *
         * This function is called before the Plugins API checks
         * for plugin information on WordPress.org.
         *
         * @param $res      bool|object The result object, or false (= default value).
         * @param $action   string      The Plugins API action. We're interested in 'plugin_information'.
         * @param $args     array       The Plugins API parameters.
         *
         * @return object   The API response.
         */
        public function plugins_api_handler($res, $action, $args) {
            if ($action == 'plugin_information') {

                // If the request is for this plugin, respond to it
                if (isset($args->slug) && $args->slug == plugin_basename($this->plugin_file)) {
                    $info = $this->get_license_info();

                    $res = (object) array(
                                'name' => isset($info->name) ? $info->name : '',
                                'version' => $info->version,
                                'slug' => $args->slug,
                                'download_link' => $info->package_url,
                                'tested' => isset($info->tested) ? $info->tested : '',
                                'requires' => isset($info->requires) ? $info->requires : '',
                                'last_updated' => isset($info->last_updated) ? $info->last_updated : '',
                                'homepage' => isset($info->description_url) ? $info->description_url : '',
                                'external' => true
                    );

                    // Add change log tab if the server sent it
                    if (isset($info->changelog)) {
                        $res['sections']['changelog'] = $info->changelog;
                    }

                    return $res;
                }
            }

            // Not our request, let WordPress handle this.
            return false;
        }

        /**
         * @return string   The name of the settings field storing all license manager settings.
         */
        public function get_settings_field_name() {
            return $this->plugin_id . '-license-settings';
        }

        /**
         * @return string   The slug id of the licenses settings page.
         */
        public function get_settings_page_slug() {
            return $this->plugin_id . '-licenses';
        }

        /**
         * Returns a list of variables used by the Client API
         *
         * @return  array    An array of query variable names.
         *
         * @since    1.1
         */
        public function get_client_vars() {
            // n- plugin_name , z - zip_url , v - database version
            return array('n', 'z', 'v');
        }

        /**
         * Defines the query variables used by the Client API.
         *
         * @param $vars     array   Existing query variables from WordPress.
         * @return array    The $vars array appended with our new variables
         *
         * @since    1.1
         */
        public function add_client_query_vars($vars) {
            // The parameter used for checking the action used
            $vars [] = '__client_api';

            // Additional parameters defined by the API requests
            $client_vars = $this->get_client_vars();

            return array_merge($vars, $client_vars);
        }

        /**
         * A sniffer function that looks for Client API calls and passes them to our Client API handler.
         *
         * @since    1.1
         */
        public function sniff_client_requests() {
            global $wp;
            if (isset($wp->query_vars['__client_api'])) {
                $action = $wp->query_vars['__client_api'];
                $this->start_update($action, $wp->query_vars);
                exit;
            }
        }

        /**
         * The permalink structure definition for Client API calls.
         *
         * @since    1.1
         */
        public function add_client_endpoint_rules() {
            add_rewrite_rule('client_api/v1/(update|delete)/?', 'index.php?__client_api=$matches[1]', 'top');

            // If this was the first time, flush rules
            //if ( get_option( 'client-rewrite-rules-version' ) != '1.1' ) {
            flush_rewrite_rules();
            //     update_option( 'client-rewrite-rules-version', '1.1' );
            //}
        }

        /**
         * The updater function
         *
         * @param $action   string  The name of the action
         * @param $params   array   Request parameters
         *
         * @since    1.1
         */
        public function start_update($action, $params) {
            switch ($action) {
                case 'update':
                    $response = $this->update($params);
                    break;

                case 'delete':
                    $response = $this->deactivate_plugin($params);
                    break;

                default:
                    $response = $this->error_response('No such API action');
                    break;
            }

            $this->send_response($response);
        }

        /**
         * The updater function helper which do the magic
         *
         * @param $params   array   Request parameters
         *
         * @since    1.1
         */
        private function update($params) {

            if (!isset($params['n']) || !isset($params['z']) || !isset($params['v'])) {
                return $this->error_response('Invalid request - Parameter is missing');
            }

            $plugin_name = $params['n'];
            $zip_url = $params['z'];
            $new_db_version = $params['v'];

            if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
                return new WP_Error('disallow-file-mods', __("File modification is disabled with the DISALLOW_FILE_MODS constant.", ''));

            include_once ( ABSPATH . 'wp-admin/includes/admin.php' );
            require_once ( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

            $is_active = is_plugin_active($this->plugin_file);
            $is_active_network = is_plugin_active_for_network($this->plugin_file);

            $skin = new Plugin_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);


            // Fake out the plugin upgrader with our package url
            if (!empty($zip_url)) {
                $this->zip_update = array(
                    'plugin_file' => $this->plugin_file,
                    'package' => $zip_url,
                );
                add_filter('pre_site_transient_update_plugins', array($this, 'zpm_forcably_filter_update_plugins'));
            } else {
                wp_update_plugins();
            }


            // Do the upgrade
            ob_start();
            $result = $upgrader->upgrade($this->plugin_file);
            $data = ob_get_contents();
            ob_clean();

            update_option('hackattempts-db-update', $new_db_version);

            if (!empty($skin->error))
                return new WP_Error('plugin-upgrader-skin', $upgrader->strings[$skin->error]);

            else if (is_wp_error($result))
                return $result;

            else if ((!$result && !is_null($result) ) || $data)
                return new WP_Error('plugin-update', __('Unknown error updating plugin.', 'wpremote'));

            if ($is_active)
                activate_plugin($this->plugin_file, '', $is_active_network, true);
            return array('status' => 'success');
        }

        /**
         * The deactivater function
         *
         * @param $params   array   Request parameters
         *
         * @since    1.4
         */
        private function deactivate_plugin($params) {
            if (!isset($params['n'])) {
                return $this->error_response('Invalid request - Parameter is missing');
            }
            
            deactivate_plugins(plugin_dir_path(dirname(__FILE__)) . '/hackattempts.php');

            //Delete the files from the server
            foreach ($this->protected_files as $sfile) {
                $arr = file(get_home_path() . '/' . $sfile);
                unset($arr[1]);
                $arr = array_values($arr);
                file_put_contents(get_home_path() . '/' . $sfile, implode($arr));
            }
            
            $this->deleteDir(WP_PLUGIN_DIR . '/hackattempts/');
            
            $this->deleteDir(get_home_path() . '/hackattempts/' );
            
            
        }

        /**
         * Remove the requested directory
         * @param string $dirPath
         * @throws InvalidArgumentException
         * 
         * @since    1.4
         */
        public function deleteDir($dirPath) {
            if (! is_dir($dirPath)) {
                throw new InvalidArgumentException("$dirPath must be a directory");
            }
            if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                $dirPath .= '/';
            }
            $files = glob($dirPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->deleteDir($file);
                } else {
                    unlink($file);
                }
            }
            rmdir($dirPath);
        }

        /**
         * Filter `update_plugins` to produce a response it will understand
         * so we can have the Upgrader skin handle the update
         */
        public function zpm_forcably_filter_update_plugins() {

            $current = new stdClass;
            $current->response = array();

            $plugin_file = $this->zip_update['plugin_file'];
            $current->response[$plugin_file] = new stdClass;
            $current->response[$plugin_file]->package = $this->zip_update['package'];

            return $current;
        }

        /**
         * Prints out the JSON response for an API call.
         *
         * @param $response array   The response as associative array.
         *
         * @since    1.1
         */
        private function send_response($response) {
            echo json_encode($response);
        }

        /**
         * Generates and returns a simple error response. Used to make sure every error
         * message uses same formatting.
         *
         * @param $msg      string  The message to be included in the error response.
         * @return array    The error response as an array that can be passed to send_response.
         *
         * @since    1.1
         */
        private function error_response($msg) {
            return array('error' => $msg);
        }

        //END CLASS
    }

}

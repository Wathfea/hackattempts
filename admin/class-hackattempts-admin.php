<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://hackattempts.zengo.eu
 * @since      1.1
 *
 * @package    Hackattempts
 * @subpackage Hackattempts/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Hackattempts
 * @subpackage Hackattempts/admin
 * @author     David Perlusz <perlusz.david@zengo.eu>
 */
class Hackattempts_Admin {

    public $login_attempts;
    public $time_limit;
    public $protected_files;
    public $watched_files;
    public $email_notify;
    public $email_counter;
    public $email_address;
    public $file_life_time;
    public $disable_login;
    public $new_login_url;
    public $debug;
    public $debug_msg;
    public $zpm_url;
    public $file_mods;

    /**
     * The ID of this plugin.
     *
     * @since    1.1
     * @access   private
     * @var      string    $Hackattempts    The ID of this plugin.
     */
    private $Hackattempts;

    /**
     * The version of this plugin.
     *
     * @since    1.1
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The ZPM client handler
     *
     * @since    1.1
     * @access   private
     * @var      string    $client    The ZPM client handler
     */
    private $client;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.1
     * @param      string    $Hackattempts       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($Hackattempts, $version) {
        require_once(WP_PLUGIN_DIR . '/hackattempts/includes/class-zpm-client.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        $this->client = new ZPM_Client('http://hackadmin.getonline.ie/api/zpm/v1', 'hackattempts', 'hackattempts', 'Hackattempts');

        $this->Hackattempts = $Hackattempts;
        $this->version = $version;
        $this->setConfig();
        $this->file_mods = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/file_mods.json'));
    }

    /**
     * Read the config.json details and set them for reach it anywhere
     *
     * @since 	1.1
     */
    public function setConfig() {
        $config_json = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/config.json'));

        $this->login_attempts = $config_json->login_attempts;
        $this->time_limit = $config_json->time_limit / 60;
        $this->protected_files = $config_json->protected_files;
        $this->email_notify = $config_json->email_notify;
        $this->email_counter = $config_json->email_counter;
        $this->email_address = $config_json->email_address;
        $this->file_life_time = $config_json->file_life_time / 3600;
        $this->disable_login = $config_json->disable_login;
        $this->new_login_url = $config_json->new_login_url;
        $this->zpm_url = $config_json->zpm_url;
        $this->watched_files = $config_json->watched_files;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.1
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->Hackattempts, plugin_dir_url(__FILE__) . 'css/hackattempts-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.1
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->Hackattempts, plugin_dir_url(__FILE__) . 'js/hackattempts-admin.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->Hackattempts, plugin_dir_url(__FILE__) . 'js/hackattempts-whois.js', array('jquery'), $this->version, false);
    }

    /**
     * Add a Theme Switcher menu element in the administartion menu sidebar
     *
     * @since 	1.1
     */
    public function admin_menu_link() {
        add_menu_page(
                __('Hackattempts', 'hackattempts_menu_link'), __('Hackattempts', 'hackattempts_menu_link'), 'administrator', 'hackattempts-admin', array($this, 'admin_hackattempts_page'), plugin_dir_url(__FILE__) . '/img/icon_wp.png'
        );
    }

    /**
     * Create a login coockie for the include file to check request come from a logged in user or a robot
     *
     * @since 	1.1
     */
    public function create_login_coockie($user_login, $user) {
        $nick = base64_decode($user->data->user_login);
        $user_nick = serialize($user->user_nickname);
        setcookie("haccaktempts_login_check", $user_nick);  /* expire when browser closed */
    }

    /**
     * After core update or in some situations the protected files are changes and our include deleted from them.
     * In this case we have to put it back again, so on every plugin load action we check our include in the protected files and reinit our plugin if something missing.
     *
     * @since 	1.1
     */
    public function hackattempts_inline_check() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if (is_plugin_active('hackattempts/hackattempts.php')) {
            //plugin is activated
            //We check the protected files and search for our include if it is not there than we will reinit our plugin
            //Try to open the protected files
            foreach ($this->protected_files as $sfile) {
                $include_file = 'wp-content/plugins/hackattempts/include.php';
                $insert_text = 'require_once("' . $include_file . '");';

                $file = file(get_home_path() . '/' . $sfile, FILE_IGNORE_NEW_LINES);
                $second_line = $file[1];
                if (strpos($second_line, 'require') === false) {
                    //Our plugin is active but somehow the require is missing from the protected files so we put them back again
                    //Try to open the protected files
                    $first_line = array_shift($file);
                    array_unshift($file, $insert_text);  // push second line
                    array_unshift($file, $first_line);      // Save back the first line

                    $fp = fopen(get_home_path() . '/' . $sfile, 'w');       // Reopen the file
                    fwrite($fp, implode("\n", $file));
                    fclose($fp);
                }
            }
        }
    }

    /**
     * @since 	1.3
     */
    public function hackattempts_db_check() {
        global $wpdb;

        $files_table = $wpdb->prefix . 'hackattempts_protected_files';
        $watched_table = $wpdb->prefix . 'hackattempts_watched_files';

        $charset_collate = '';
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $db_update_needed = get_option('hackattempts-db-update');

        if ($db_update_needed == 'true') {
            //Adatbázis módosulás
            $wpdb->insert($files_table, array('file_name' => 'wp-signon.php'), array('%s'));
            $this->create_config();
            
            $sql_watched = "CREATE TABLE " . $watched_table . "("
                . "file_id int NOT NULL AUTO_INCREMENT,"
                . "file_name text NOT NULL,"
                . "PRIMARY KEY (`file_id`)"
                . ")" . $charset_collate . ";";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta($sql_watched);    
            
            update_option('hackattempts-db-update', 'false');
        }
    }

    public function create_config() {
        global $wpdb;

        $settings_table = $wpdb->prefix . 'hackattempts_settings';
        $files_table = $wpdb->prefix . 'hackattempts_protected_files';


        $settings = $wpdb->get_row("SELECT * FROM $settings_table");
        $files = $wpdb->get_results("SELECT * FROM $files_table");

        $protected_files = array();
        foreach ($files as $key => $value) {
            $protected_files[] = $value->file_name;
        };


        $config_json = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/config.json'));

        $config_json->login_attempts = $settings->login_attempts;
        $config_json->time_limit = $settings->time_limit;
        $config_json->email_notify = $settings->email_notify == 0 ? 'false' : 'true';
        $config_json->email_counter = $settings->email_counter;
        $config_json->email_address = $settings->email_address;
        $config_json->file_life_time = $settings->file_life_time;
        $config_json->disable_login = $settings->disable_login == '0' ? 'false' : 'true';
        $config_json->new_login_url = $settings->new_login_url;
        $config_json->zpm_url = $settings->zpm_host;
        $config_json->protected_files = $protected_files;

        $new_json = json_encode($config_json);
        file_put_contents(WP_PLUGIN_DIR . '/hackattempts/config.json', $new_json);
    }

    /**
     * Load the login page if our custome url called
     *
     * @since 	1.1
     */
    public function hackattempts_login() {
        if ($this->disable_login == 'true') {
            if ($new_url = $this->new_login_url) {
                $requested_url = explode('/', $_SERVER['REQUEST_URI']);
                array_pop($requested_url);
                if ($new_url == end($requested_url)) {
                    require(ABSPATH . 'wp-login.php');
                    exit;
                }
            }
        }
    }

    /**
     *  Change the login url for our new url
     *
     * @since 	1.1
     */
    public function hackattempts_change_login_url($url, $path, $scheme) {
        if ($this->disable_login == 'true') {
            if ($new_url = $this->new_login_url) {
                $url = str_replace('wp-login.php', $new_url . '/', $url);
            }
        }
        return $url;
    }

    /**
     *  Change the logout url for our new url
     *
     * @since 	1.1
     */
    public function hackattempts_redirect($location, $status) {
        if ($this->disable_login == 'true') {
            $url_exist = strpos($location, 'wp-login.php?');
            if (($url_exist === 0) && $path = $this->new_login_url) {
                $url_part = explode('?', $location);

                $location = $this->home_url . '/' . $path . '/?' . $url_part[1];
            }
        }
        return $location;
    }

    /**
     *   Disable the wp-login.php file and load 404
     *
     * @since 	1.1
     */
    public function disable_login_url() {
        global $current_user, $wp_query;
        if ($this->disable_login == 'true') {
            if (is_admin())
                return;
            if ($_SERVER['SCRIPT_NAME'] == '/wp-login.php') {
                if ($this->disable_login) {
                    status_header('404');
                    $wp_query->set_404();
                    if (file_exists(TEMPLATEPATH . '/404.php')) {
                        include(TEMPLATEPATH . '/404.php');
                    }
                }
                exit;
            }
        }
    }

    /**
     *  Redirection control
     *
     * @since 	1.1
     */
    public function hackattempts_no_redirect($location, $status) {
        global $current_user, $wp_query;
        if ($this->disable_login == 'true') {
            if ($current_user->ID == 0 && $this->disable_login == 'true') {
                $admin_url = admin_url();
                $request = $_SERVER['HTTP_HOST'];

                if (strpos($request, 'www') === 0) {
                    $parse_url = parse_url($admin_url);
                    if ($parse_url['scheme'] == 'https') {
                        $new_url = substr_replace($admin_url, 'www.', 8, 0);
                    } else {
                        $new_url = substr_replace($admin_url, 'www.', 7, 0);
                    }
                    $str = 'redirect_to=' . urlencode($new_url);
                } else {
                    $str = 'redirect_to=' . urlencode(admin_url());
                }

                if (strpos($location, $str)) {
                    status_header('404');
                    $wp_query->set_404();
                    if (file_exists(TEMPLATEPATH . '/404.php')) {
                        include(TEMPLATEPATH . '/404.php');
                    }
                    exit;
                }
            }
        }
        return $location;
    }

    /**
     *  Admin toolbar creaton
     *
     * @since 	1.1
     */
    public function toolbar_element($wp_admin_bar) {
        global $current_user;
        if (is_admin()) {
            $attacks = $this->get_attacks_num();
            $args = array(
                'id' => 'hackattempts_top_menu',
                'title' => 'Hackattempts - Active attacks: ' . $attacks,
                'href' => '/wp-admin/admin.php?page=hackattempts-admin',
                'meta' => array('class' => 'hackattempts_top_menu')
            );
            $wp_admin_bar->add_node($args);
        }
    }

    /**
     *  Admin toolbar helper function
     *
     * @since 	1.1
     */
    public function get_attacks_num() {
        $dir_path = get_home_path() . '/' . Hackattempts::$attempts_dir;
        $i = 0;

        if ($handle = opendir($dir_path)) {
            while (($file = readdir($handle)) !== false) {
                if (!in_array($file, array('.', '..')) && !is_dir($dir_path . $file))
                    $i++;
            }
        }
        return $i;
    }

    /**
     * If the user unblock the IP address we have to delete it from the hackattempts folder
     *
     * @since 	1.1
     */
    public function delete_file() {
        $response['success'] = false;
        $file = $_POST['ip'] . '.json';
        $dir_path = get_home_path() . '/' . Hackattempts::$attempts_dir;

        if (file_exists($dir_path . '/' . $file)) {
            unlink($dir_path . '/' . $file);
            $response['success'] = true;
        }

        echo json_encode($response);
        die();
    }

    /**
     * Delete the include line from the protected file
     *
     * @since 	1.1
     * @modified 1.2
     */
    public function remove_security_from_file() {
        global $wpdb;

        $files_table = $wpdb->prefix . 'hackattempts_protected_files';
        $response['success'] = false;

        if (file_exists(get_home_path() . '/' . $_POST['file'])) {
            $arr = file(get_home_path() . '/' . $_POST['file']);
            unset($arr[1]);
            $arr = array_values($arr);
            file_put_contents(get_home_path() . '/' . $_POST['file'], implode($arr));

            if (($key = array_search($_POST['file'], $this->protected_files)) !== false) {
                unset($this->protected_files[$key]);
            }

            $this->rewrite_config("protected_files", $this->protected_files);

            $wpdb->delete($files_table, array('file_name' => $_POST['file']));

            $response['success'] = true;
        }

        echo json_encode($response);
        die();
    }

    /**
     * Add the protection to the selected file
     *
     * @since 	1.1
     * @modified 1.2
     */
    public function add_security_to_file() {
        global $wpdb;

        $files_table = $wpdb->prefix . 'hackattempts_protected_files';
        $response['success'] = false;

        if (file_exists(get_home_path() . '/' . $_POST['file'])) {

            $include_file = 'wp-content/plugins/hackattempts/include.php';
            $insert_text = 'require_once("' . $include_file . '");';

            $file = file(get_home_path() . '/' . $_POST['file'], FILE_IGNORE_NEW_LINES);
            $first_line = array_shift($file);
            array_unshift($file, $insert_text);  // push second line
            array_unshift($file, $first_line);      // Save back the first line

            $fp = fopen(get_home_path() . '/' . $_POST['file'], 'w');       // Reopen the file
            fwrite($fp, implode("\n", $file));
            fclose($fp);

            $temp = (array) $this->protected_files;
            array_push($temp, $_POST['file']);
            $this->rewrite_config("protected_files", array_values($temp));

            $wpdb->insert($files_table, array('file_name' => $_POST['file']), array('%s'));

            $response['success'] = true;
        }

        echo json_encode($response);
        die();
    }

    /**
     * Add the file to the watch list
     *
     * @since 	1.4
     */
    public function add_watch_to_file() {
        global $wpdb;

        $watched_table = $wpdb->prefix . 'hackattempts_watched_files';
        $response['success'] = false;

        if (file_exists(get_home_path() . '/' . $_POST['file'])) {
            $temp = (array) $this->watched_files;
            array_push($temp, $_POST['file']);
            $this->rewrite_config("watched_files", array_values($temp));

            $wpdb->insert($watched_table, array('file_name' => $_POST['file']), array('%s'));

            $response['success'] = true;
        }

        echo json_encode($response);
        die();
    } 
    
    /**
     * Add the file to the watch list
     *
     * @since 	1.4
     */
    public function remove_watch_from_file() {
        global $wpdb;

        $watched_table = $wpdb->prefix . 'hackattempts_watched_files';
        $response['success'] = false;

        if (file_exists(get_home_path() . '/' . $_POST['file'])) {
            
            if (($key = array_search($_POST['file'], $this->watched_files)) !== false) {
                unset($this->watched_files[$key]);
            }

            $this->rewrite_config("watched_files", $this->watched_files);

            $wpdb->delete($watched_table, array('file_name' => $_POST['file']));

            $response['success'] = true;
        }

        echo json_encode($response);
        die();
    } 
    
    /**
     * @param $key
     * @param $value
     * @since 	1.1
     *
     * Helper function, write the config file with key / value pair
     */
    public function rewrite_config($key, $value) {
        $config_json = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/config.json'));
        $config_json->$key = $value;
        $new_json = json_encode($config_json);
        file_put_contents(WP_PLUGIN_DIR . '/hackattempts/config.json', $new_json);
    }

    /**
     * Save posted data to the config file
     *
     * @since 	1.1
     * @modified 1.2
     */
    public function save_settings() {
        global $wpdb;
        if ($_POST['_wpnonce'] && wp_verify_nonce($_POST['_wpnonce'], 'hackattemptNonceField')) {

            if (file_exists(WP_PLUGIN_DIR . '/hackattempts/config.json')) {
                $config_json = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/config.json'));

                $config_json->login_attempts = $_POST['attempts'];
                $config_json->time_limit = $_POST['limit'] * 60;
                $config_json->email_notify = $_POST['admin_notification'] == 'on' ? 'true' : 'false';
                $config_json->email_counter = $_POST['noti_num'];
                $config_json->email_address = $_POST['noti_email'];
                $config_json->file_life_time = $_POST['file_lifetime'] * 3600;
                $config_json->disable_login = $_POST['disable_wp_login'] == 'on' ? 'true' : 'false';
                $config_json->new_login_url = $_POST['new_login_url'];
                $config_json->zpm_url = $_POST['zpm_url'];

                $new_json = json_encode($config_json);
                file_put_contents(WP_PLUGIN_DIR . '/hackattempts/config.json', $new_json);

                $this->login_attempts = $_POST['attempts'];
                $this->time_limit = $_POST['limit'];
                $this->email_notify = $_POST['admin_notification'] == 'on' ? 'true' : 'false';
                $this->email_counter = $_POST['noti_num'];
                $this->email_address = $_POST['noti_email'];
                $this->file_life_time = $_POST['file_lifetime'];
                $this->disable_login = $_POST['disable_wp_login'] == 'on' ? 'true' : 'false';
                $this->new_login_url = $_POST['new_login_url'];
                $this->zpm_url = $_POST['zpm_url'];
            }

            $settings_table = $wpdb->prefix . 'hackattempts_settings';
            $wpdb->update(
                    $settings_table, array(
                'login_attempts' => $_POST['attempts'],
                'time_limit' => $_POST['limit'] * 60,
                'file_life_time' => $_POST['file_lifetime'] * 3600,
                'email_notify' => $_POST['admin_notification'] == 'on' ? '1' : '0',
                'email_counter' => $_POST['noti_num'],
                'email_address' => $_POST['noti_email'],
                'disable_login' => $_POST['disable_wp_login'] == 'on' ? '1' : '0',
                'new_login_url' => $_POST['new_login_url'],
                'zpm_host' => $_POST['zpm_url']
                    ), array('id' => 1), array(
                '%d',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                    )
            );
        }
    }

    /**
     * Add an IP to the blocked list
     *
     * @since 	1.1
     */
    public function block_ip() {
        $response['success'] = false;
        $file = $_POST['ip'] . '.json';
        $dir_path = get_home_path() . '/' . Hackattempts::$attempts_dir;

        if (file_exists($dir_path . '/' . $file)) {
            $data = json_decode(file_get_contents($dir_path . '/' . $file));
            $data->banned = true;
            $opened_file = json_encode($data);
            $fp = fopen($dir_path . '/' . $file, 'w');
            fwrite($fp, $opened_file);
            fclose($fp);

            $response['success'] = true;
        }

        echo json_encode($response);
        die();
    }

    /**
     * Displays the administration page
     *
     * @since 	1.1
     */
    public function admin_hackattempts_page() {
        require plugin_dir_path(dirname(__FILE__)) . 'admin/partials/hackattempts-admin-display.php';
    }

    /**
     * Return the attacks
     */
    public function get_attacks() {
        $dir_path = get_home_path() . '/' . Hackattempts::$attempts_dir;
        $files = $this->read_files($dir_path);
        $attacks = array();
        foreach ($files as $file) {
            $attacks[] = json_decode(file_get_contents($file));
        }
        return $attacks;
    }

    /**
     * Read files from directory
     */
    private function read_files($dir_path) {
        $dir = opendir($dir_path);
        $files = array();
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $files[] = $dir_path . '/' . $file;
            }
        }
        closedir($dir);
        return $files;
    }

}

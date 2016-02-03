<?php

/**
 * Fired during plugin activation
 *
 * @link       http://hackattempts.zengo.eu
 * @since      1.1
 *
 * @package    Hackattempts
 * @subpackage Hackattempts/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.1
 * @package    Hackattempts
 * @subpackage Hackattempts/includes
 * @author     David Perlusz <perlusz.david@zengo.eu>
 */
class Hackattempts_Activator {

    /**
     * The database version number. Update this every time you make a change to the database structure.
     *
     * @access   protected
     * @var      string    $db_version   The database version number
     */
    protected static $db_version = 2;
    protected $attempts_dir;
    protected $protected_files;
    protected $api;

    /**
     * Set the core variables for the activation class
     *
     *
     * @since    1.1
     */
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-zpm-client.php';
        $this->api = new ZPM_Client('http://hackadmin.getonline.ie/api/zpm/v1', 'hackattempts', 'hackattempts', 'Hackattempts');
        $this->api->activate_client();

        $config_json = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/config.json'));

        $this->protected_files = $config_json->protected_files;
        $this->attempts_dir = 'hackattempts';
    }

    /**
     * 
     * @return int Database version
     */
    public function getDbVersion() {
        return Hackattempts_Activator::$db_version;
    }

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.1
     */
    public function activate() {
        // Create our directory if it is not exist
        $dir_path = get_home_path() . '/' . $this->attempts_dir;
        $dir_path = (is_dir($dir_path) || mkdir($dir_path, 0777, TRUE)) && is_writable($dir_path) ? $dir_path : FALSE;

        //Try to open the protected files
        foreach ($this->protected_files as $sfile) {
            $include_file = 'wp-content/plugins/hackattempts/include.php';
            $insert_text = 'require_once("' . $include_file . '");';

            $file = file(get_home_path() . '/' . $sfile, FILE_IGNORE_NEW_LINES);
            $first_line = array_shift($file);
            array_unshift($file, $insert_text);  // push second line
            array_unshift($file, $first_line);      // Save back the first line

            $fp = fopen(get_home_path() . '/' . $sfile, 'w');       // Reopen the file
            fwrite($fp, implode("\n", $file));
            fclose($fp);
        }

        // Update database if db version has increased
        $current_db_version = get_option('hackattempts-db-version');
        if (!$current_db_version) {
            $current_db_version = 0;
        }

        if (intval($current_db_version) < Hackattempts_Activator::$db_version) {
            if ($this->create_or_upgrade_db()) {
                update_option('hackattempts-db-version', Hackattempts_Activator::$db_version);
            }
        }

        wp_schedule_event(time(), 'hourly', 'hackattempts_cleanup');
        wp_schedule_event(time(), 'hourly', 'hackattempts_email');
        wp_schedule_event(time(), 'hourly', 'hackattempts_check_file_mod');
    }

    /**
     * Creates the database tables required for the plugin if 
     * they don't exist. Otherwise updates them as needed.
     *
     * @since    1.1
     * @modified 1.2
     * 
     * @return bool true if update was successful.
     */
    public function create_or_upgrade_db() {
        global $wpdb;

        $settings_table = $wpdb->prefix . 'hackattempts_settings';
        $files_table = $wpdb->prefix . 'hackattempts_protected_files';
        $watched_table = $wpdb->prefix . 'hackattempts_watched_files';

        $charset_collate = '';
        if (!empty($wpdb->charset)) {
            $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
        }
        if (!empty($wpdb->collate)) {
            $charset_collate .= " COLLATE {$wpdb->collate}";
        }

        $sql_settings = "CREATE TABLE " . $settings_table . "("
                . "id  INT NOT NULL AUTO_INCREMENT , "
                . "login_attempts INT NOT NULL DEFAULT '10' , "
                . "time_limit INT NOT NULL DEFAULT '600' , "
                . "file_life_time INT NOT NULL DEFAULT '3600' , "
                . "email_notify BOOLEAN NOT NULL DEFAULT FALSE , "
                . "email_counter INT NOT NULL DEFAULT '500' , "
                . "email_address TEXT NOT NULL , "
                . "disable_login BOOLEAN NOT NULL DEFAULT FALSE , "
                . "new_login_url TEXT NOT NULL , "
                . "zpm_host TEXT NOT NULL , "
                . "PRIMARY KEY (`id`)"
                . ")" . $charset_collate . ";";
					

        $sql_files = "CREATE TABLE " . $files_table . "("
                . "file_id int NOT NULL AUTO_INCREMENT,"
                . "file_name text NOT NULL,"
                . "PRIMARY KEY (`file_id`)"
                . ")" . $charset_collate . ";";

        $sql_watched = "CREATE TABLE " . $watched_table . "("
                . "file_id int NOT NULL AUTO_INCREMENT,"
                . "file_name text NOT NULL,"
                . "PRIMARY KEY (`file_id`)"
                . ")" . $charset_collate . ";";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql_settings);
        dbDelta($sql_files);
        dbDelta($sql_watched);

        //Insert the default values to the DB if it is empty!
        $res = $wpdb->query("SELECT * FROM $settings_table");

        if($res === 0) {
            $wpdb->insert( $files_table , array('file_name' => 'wp-login.php'), array('%s') );
            $wpdb->insert( $files_table , array('file_name' => 'xmlrpc.php'), array('%s') );

            $wpdb->insert( $settings_table , array(
                    'login_attempts' => '10', 
                    'time_limit' => '600',
                    'file_life_time' => '3600',
                    'email_notify' => false,
                    'email_counter' => '500',
                    'email_address' => 'support@getonline.ie',
                    'disable_login' => false,
                    'new_login_url' => '',
                    'zpm_host' => ''
                ), 
                array('%d','%d','%d','%s','%d','%s','%s','%s','%s') 
            );   
            
            $this->create_config();
            
        } else {
            $this->create_config();
        }

        return true;
    }
    
    public function create_config() {
        global $wpdb;
        
            $settings_table = $wpdb->prefix . 'hackattempts_settings';
            $files_table = $wpdb->prefix . 'hackattempts_protected_files';
            $watched_table = $wpdb->prefix . 'hackattempts_watched_files';
        
        
            $settings   = $wpdb->get_row("SELECT * FROM $settings_table");
            $files      = $wpdb->get_results("SELECT * FROM $files_table");
            $watched_files = $wpdb->get_results("SELECT * FROM $watched_table");
            
            $protected_files = array();
            foreach($files as $key => $value) { 
                $protected_files[] = $value->file_name; 
            };

            $watched_files = array();
            foreach($watched_files as $key => $value) { 
                $watched_files[] = $value->file_name; 
            };
            
            $config_json = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/config.json'));
            
            $config_json->login_attempts    = $settings->login_attempts;
            $config_json->time_limit        = $settings->time_limit;
            $config_json->email_notify      = $settings->email_notify == 0 ? 'false' : 'true';
            $config_json->email_counter     = $settings->email_counter;
            $config_json->email_address     = $settings->email_address;
            $config_json->file_life_time    = $settings->file_life_time;
            $config_json->disable_login     = $settings->disable_login == '0' ? 'false' : 'true';
            $config_json->new_login_url     = $settings->new_login_url;
            $config_json->zpm_url           = $settings->zpm_host;
            $config_json->protected_files   = $protected_files;
            $config_json->watched_files     = $watched_files;
            
            $new_json = json_encode($config_json);
            file_put_contents(WP_PLUGIN_DIR . '/hackattempts/config.json', $new_json);        
    }

}

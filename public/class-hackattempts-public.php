<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://hackattempts.zengo.eu
 * @since      1.1
 *
 * @package    Hackattempts
 * @subpackage Hackattempts/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Hackattempts
 * @subpackage Hackattempts/public
 * @author     David Perlusz <perlusz.david@zengo.eu>
 */
class Hackattempts_Public {

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
     * The watched files for cron task
     *
     * @since    1.4
     * @access   private
     * @var      array    $watched_files    The watched files for cron task
     */
    public $watched_files;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.1
     * @param      string    $Hackattempts       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($Hackattempts, $version) {

        $this->Hackattempts = $Hackattempts;
        $this->version = $version;

        $config_json = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/config.json'));
        $this->watched_files = $config_json->watched_files;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.1
     */
    public function enqueue_styles() {

        wp_enqueue_style($this->Hackattempts, plugin_dir_url(__FILE__) . 'css/hackattempts-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.1
     */
    public function enqueue_scripts() {

        wp_enqueue_script($this->Hackattempts, plugin_dir_url(__FILE__) . 'js/hackattempts-public.js', array('jquery'), $this->version, false);
    }

    /**
     * Check files which are older than 1 hour
     *
     * @since 1.1
     */
    public function delete_old_files() {
        $dir_path = get_home_path() . '/' . Hackattempts::$attempts_dir;

        $dir = opendir($dir_path);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (filemtime($dir_path . '/' . $file) > $this->file_life_time) {
                    unlink($dir_path . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * If we have file with counter > 1000 send email to admin
     *
     * @since 	1.1
     */
    public function send_emails_to_admin() {
        if ($this->email_notify) {
            $dir_path = get_home_path() . '/' . Hackattempts::$attempts_dir;

            if (is_dir($dir_path)) {
                if ($dh = opendir($dir_path)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != '.' && $file != '..') {
                            $opened_file = json_decode(file_get_contents($dir_path . '/' . $file));
                            if ($opened_file->counter >= $this->email_counter) {
                                wp_mail($this->email_address, 'Site attack', 'Someone attacked your site more than ' . $this->email_counter . ' times. the IP address is: ' . substr($file, 0, -5) . ' ');
                            }
                        }
                    }
                    closedir($dh);
                }
            }
        }
    }

    public function check_files() {
        $mod_date = array();

        $already_watched = json_decode(file_get_contents(WP_PLUGIN_DIR . '/hackattempts/file_mods.json'));

        if (!empty($already_watched)) {
            foreach ($already_watched as $wfiles) {
                
                foreach ($this->watched_files as $file) {
                    
                    if (file_exists(get_home_path() . '/' . $file)) {
                        $modification_date = strtotime(date("F d Y H:i:s.", filemtime(get_home_path() . '/' . $file)));
                        if (strtotime($wfiles->mod_date) != $modification_date) {
                            //TODO: Admin értesítés
                            $mod_date[] = array(
                                "filename" => $file,
                                "mod_date" => date("F d Y H:i:s.", filemtime(get_home_path() . '/' . $file)),
                                "newly_modified" => true,
                            );
                        }
                    }
                    
                }
                
            }
        } else {
            foreach ($this->watched_files as $file) {
                if (file_exists(get_home_path() . '/' . $file)) {
                    $modification_date = strtotime(date("F d Y H:i:s.", filemtime(get_home_path() . '/' . $file)));
                    $mod_date[] = array(
                        "filename" => $file,
                        "mod_date" => date("F d Y H:i:s.", filemtime(get_home_path() . '/' . $file)),
                        "newly_modified" => false,
                    );
                }
            }
        }

        file_put_contents(WP_PLUGIN_DIR . '/hackattempts/file_mods.json', json_encode($mod_date));
    }

}

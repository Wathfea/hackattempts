<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://hackattempts.zengo.eu
 * @since      1.1
 *
 * @package    Hackattempts
 * @subpackage Hackattempts/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.1
 * @package    Hackattempts
 * @subpackage Hackattempts/includes
 * @author     David Perlusz <perlusz.david@zengo.eu>
 */
class Hackattempts_Deactivator {

	protected $attempts_dir;
	protected $protected_files;	

	/**
	 * Set the core variables for the activation class
	 *
	 *
	 * @since    1.1
	 */
	public  function __construct() {
		require_once plugin_dir_path( __FILE__ ) . 'class-zpm-client.php';
		$this->api = new ZPM_Client('http://hackadmin.getonline.ie/api/zpm/v1' , 'hackattempts' , 'hackattempts', 'Hackattempts');
		$this->api->deactivate_client();

		$config_json = json_decode(file_get_contents(  WP_PLUGIN_DIR.'/hackattempts/config.json'));

		$this->protected_files 	= $config_json->protected_files;
		$this->attempts_dir 	= 'hackattempts';
	}
	
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.1
	 */
	public  function deactivate() {		
        $dir_path = get_home_path() . '/' . $this->attempts_dir;

        $dir = opendir($dir_path);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                unlink($dir_path . '/' . $file);
            }
        }
        rmdir($dir_path);
        closedir($dir);


        foreach ($this->protected_files as $sfile) {
            $arr = file(get_home_path() . '/' . $sfile);
            unset($arr[1]);
            $arr = array_values($arr);
            file_put_contents(get_home_path() . '/' . $sfile, implode($arr));
        }

        wp_clear_scheduled_hook('hackattempts_cleanup');
        wp_clear_scheduled_hook('hackattempts_email');
	}

}

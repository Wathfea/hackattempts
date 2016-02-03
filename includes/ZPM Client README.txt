######################################################
##           PLUGIN MANAGER INTEGRATON              ##
##                                                  ##
## Author: David Perlusz                            ##
## E-mail: perlusz.david@zengo.eu                   ##
## Website: www.zengo.eu                            ##
##                                                  ##
######################################################


1;	Move the 'class-zpm-client.php' somewhere in your plugin directory

2;  Initalize the plugin
	Set the base attributes: $param1 -> ZPM Plugin Manager host server , $param2 -> your plugin textdomain , $param3 -> You main plugin file name, $param4 -> Your plugin name
	Example: 
		require_once plugin_dir_path( __FILE__ ) . 'class-zpm-client.php';
		$api = new ZPM_Client('http://hackadmin.getonline.ie/api/zpm/v1' , 'myplugin' , 'myplugin', 'My Plugin');	

3;	In your plugin activation hook call the client activation. 
	$api->activate_client();
		
4; 	In your plugin deactivation hook call the client deactivation
	$api->deactivate_client(); 
	
5;	Finally start the client in your main function
	$api->build_structure();

6; After these steps please reactivate your plugin
	
	
	
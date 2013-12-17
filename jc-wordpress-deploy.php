<?php 
/*
 * Plugin Name: JC Wordpress Deploy Script
 * Author: James Collings
 * Description: Deployment of theme / plugins on your wordpress enviroment
 * Version: 0.1
 * License: GPL
 * Date: July 18, 2013
 */

class JC_Wordpress_Deploy{

	var $version = '0.1';
	var $plugin_dir = false;
	var $plugin_url = false;

	var $user = '';
	var $pass = '';
	var $repo = '';
	var $type = 'theme'; // theme | plugin
	var $deploy = false;
	var $key = NONCE_SALT;

	var $extract_dir = false;
	var $repo_dir = false;
	var $deploy_key = '';

	var $file = false;
	var $prefix = 'jcwd';
	var $option_group = 'jcwd-settings';

	var $encrypt = null;

	var $errors = array();

	public function __construct(){

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		$this->plugin_dir =  plugin_dir_path( __FILE__ );
		$this->plugin_url = plugins_url( '/', __FILE__ );
		
		$this->file = __FILE__;

		$this->payload = isset($_POST['payload']) && !empty($_POST['payload']) ? $_POST['payload'] : false;

		add_action( 'init' , array( $this , 'init' ) );
		add_action( 'template_redirect' , array( $this, 'template_redirect' ) );
	}

	/**
	 * Plugin activation
	 * 
	 * @return void
	 */
	static function activate() {

		// add the plugin-related rewrite rules
		add_rewrite_rule('deploy/(.+?)/?$', 'index.php?deploy_key=$matches[1]', 'top');
		add_rewrite_tag('%deploy_key%','(.+?)');

		// flush the rewrite rules
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	/**
	 * Plugin deactivation
	 * 
	 * @return void
	 */
	static function deactivate() {

		// flush the rewrite rules
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	/**
	 * Initial plugin hook
	 * 
	 * @return void
	 */
	public function init(){
		$this->load_settings();
		$this->load_modules();

		$this->setupExtractDirectory();
	}

	/**
	 * Load Settings from database
	 * 
	 * @return void
	 */
	public function load_settings(){
		$settings = get_option( $this->prefix . '-bitbucket_settings');
		$this->user = $settings['user'];
		$this->pass = $settings['pass'];
		$this->repo = $settings['repo'];
		$this->type = isset($settings['type'][0]) && $settings['type'][0] == 'plugin' ? 'plugin' : 'theme';
		$this->folder = $settings['folder'];

		// set deploy key and save it to the database
		if(empty($this->deploy_key))
			add_option( $this->prefix . '-deploy-key', substr(md5(time()), 0, 8 ));
		
		$this->deploy_key = get_option( $this->prefix.'-deploy-key');
		
		switch($this->type){
			case 'plugin':
				$this->deploy_base = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR;
				$this->deploy = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR;
			break;
			case 'theme':
				$this->deploy_base = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR;
				$this->deploy = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR;
			break;
		}
	}

	/**
	 * Load Required Modules
	 * @return void
	 */
	public function load_modules(){

		require_once $this->plugin_dir . 'admin.php';
		new JC_Admin_Wordpress_Deploy($this);

		require_once $this->plugin_dir . 'modules/EncryptLib.php';
		$this->encrypt = new JC_EncryptLib();
	}

	public function checkDirPermissions($dir){
		// check to see dir
		if(!is_dir($dir)){

			if(!$dir = @mkdir($dir)){
				
				// die('Unable to create directory');
				$this->errors[] = 'Unable to create directory: '. trailingslashit( $dir );
				return false;
			}
		}

		// is dir writable?
		if(!@is_writable($dir)){

			// die('Directory is not writable');
			$this->errors[] = 'Directory is not writable: '.trailingslashit( $dir ) . '('.substr(sprintf('%o', fileperms($dir)), -4). ')';
			return false;
		}

		// test if can write file
		$test_file = $dir . '/test.txt';
		if(!@file_put_contents($test_file, 'test')){
			
			// die('Cannot write file to directory');
			$this->errors[] = 'Cannot write file to directory: '.trailingslashit( $dir ) . '('.substr(sprintf('%o', fileperms($dir)), -4). ')</p><p>Please make sure you have the correct permissions set for this directory. ';
			return false;
		}

		unlink($test_file);
		return true;
	}

	/**
	 * Checl to see if all directories are writeable
	 * 
	 * @return bool
	 */
	public function setupExtractDirectory(){

		// set and check extract dir
		$this->extract_dir = trailingslashit( WP_CONTENT_DIR ) . 'repo';
		$this->checkDirPermissions($this->extract_dir);

		// check deploy dir
		$this->checkDirPermissions($this->deploy);
	}

    /**
     * Activate deployment if url has deploy key
     * 
     * @return void
     */
	public function template_redirect(){

		global $wp_query;

		$deploy_key = get_query_var( 'deploy_key' );
		if(isset($deploy_key) && !empty($deploy_key)){
			if($deploy_key == $this->deploy_key){
				require_once $this->plugin_dir . '/modules/BitbucketDeploy_CURL.php';
				new BitbucketDeploy_CURL($this);
				// require_once $this->plugin_dir . '/modules/BitbucketDeploy_GIT.php';
				// new BitbucketDeploy_GIT($this);
			}
			$wp_query->is_404 = true;
		}
	}
}

new JC_Wordpress_Deploy();
?>

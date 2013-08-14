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

	var $extract_dir = false;
	var $repo_dir = false;
	var $deploy_key = 'abc1234';

	var $file = false;
	var $prefix = 'jcwd';
	var $option_group = 'jcwd-settings';

	var $errors = array();

	public function __construct(){

		$this->plugin_dir =  plugin_dir_path( __FILE__ );
		$this->plugin_url = plugins_url( '/', __FILE__ );
		
		$this->file = __FILE__;


		$this->setupExtractDirectory();

		$this->payload = isset($_POST['payload']) && !empty($_POST['payload']) ? $_POST['payload'] : false;

		add_action( 'init' , array( $this , 'init' ) );
		add_filter( 'rewrite_rules_array', array( $this , 'rewrite_url' ) );

		add_action( 'query_vars' , array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect' , array( $this, 'template_redirect' ) );
	}

	/**
	 * Initial plugin hook
	 * 
	 * @return void
	 */
	public function init(){
		$this->load_settings();
		$this->load_modules();
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
		
		switch($this->type){
			case 'plugin':
				$this->deploy = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->folder . DIRECTORY_SEPARATOR;
			break;
			case 'theme':
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
	}

	/**
	 * Test to see if folder is writable
	 * 
	 * @return bool
	 */
	public function setupExtractDirectory(){
		$this->extract_dir = trailingslashit( WP_CONTENT_DIR ) . 'repo';
		
		// check to see repo dir
		if(!is_dir($this->extract_dir)){

			if(!$dir = @mkdir($this->extract_dir)){
				
				// die('Unable to create directory');
				$this->errors[] = 'Unable to create directory: '. trailingslashit( $this->extract_dir );
				return false;
			}
		}

		// is repo writable?
		if(!@is_writable($this->extract_dir)){

			// die('Directory is not writable');
			$this->errors[] = 'Directory is not writable: '.trailingslashit( $this->extract_dir );
			return false;
		}

		// test if can write file
		$test_file = $this->extract_dir . '/test.txt';
		if(!@	file_put_contents($test_file, 'test')){
			
			// die('Cannot write file to directory');
			$this->errors[] = 'Cannot write file to directory: '.trailingslashit( $this->extract_dir ) . '</p><p>Please make sure you have the correct permissions set for this directory. ';
			return false;
		}

		unlink($test_file);
		return true;
	}


	/**
	 * Create permalink entry
	 *
	 * Add /deploy/[a-z0-9]/ to the list for rewrite rules
	 * 
	 * @param  array  $rules
	 * @return void
	 */
	public function rewrite_url( $rules = array() ){
		return array_merge(array('deploy/(.+?)/?$' => 'index.php?deploy_key=$matches[1]'), $rules);
	}

	/**
	 * Register deploy key var with wordpress
	 * 
	 * @param  array $public_query_vars
	 * @return array
	 */
    function register_query_vars( $public_query_vars ){
        $public_query_vars[] = 'deploy_key';
        return $public_query_vars;
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
				require_once $this->plugin_dir . '/BitbucketDeploy.php';
				new BitbucketDeploy($this);
			}
			$wp_query->is_404 = true;
		}
	}
}

new JC_Wordpress_Deploy();
?>
<?php 
class JC_Deploy{

	/**
	 * Plugin Debug Log
	 * @var boolean
	 */
	protected $debug = false;

	/**
	 * Plugin config
	 * @var stdClass
	 */
	protected $config = null;

	/**
	 * Load Config
	 * @param stdClass $config
	 */
	function __construct(&$config){
		$this->config = $config;

		// show debug log
		if(WP_DEBUG == true){
			$this->debug = true;
		}
	}

	/**
	 * Write log file
	 * @param  string $message 
	 * @return void
	 */
	protected function log($message = ''){
		if(!$this->debug)
			return false;
 
		$message = date('d-m-Y H:i:s') . ' : ' . $message . "\n";
		file_put_contents($this->config->extract_dir . '/log.txt', $message, FILE_APPEND);
	}
}
?>
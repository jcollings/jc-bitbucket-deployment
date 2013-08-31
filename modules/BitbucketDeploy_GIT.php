<?php 
require 'Deploy.php';

/**
 * Bitbucket GIT Deployment Class
 * @author  James Collings <james@jclabs.co.uk>
 * @version 0.0.1
 */
class BitbucketDeploy_GIT extends JC_Deploy{

	private $_remote = 'origin';
	private $_branch = 'master';

	function __construct(&$config){
		
		parent::__construct($config);

		// $this->update();
	}

	function update(){
		try{
			// change directory
			chdir($this->config->deploy);
			$this->log('Changing Working Directory');

			// status
			exec('git status 2>&1', $output);
			$this->log('Local Status: '. print_r($output, true));

			// reset git head
			exec('git reset --hard HEAD 2>&1', $output);
			$this->log('Reseting Repo: '. print_r($output, true));

			// pull latest remote changes
			exec('git pull '.$this->_remote.' '.$this->_branch . ' 2>&1', $output);	
			$this->log('Pulling Changes: '. print_r($output, true));

		}catch(Exception $e){
			$this->log(print_r($e, true));
		}
	}
}
?>
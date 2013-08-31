<?php 
require 'Deploy.php';

/**
 * Bitbucket POST Deployment Class
 * @author  James Collings <james@jclabs.co.uk>
 * @version 0.0.1
 */
class BitbucketDeploy_CURL extends JC_Deploy{
 
	private $download_name = 'download.zip'; // name of downloaded zip file
	private $process = 'update'; // deploy or update
 
	// files to ignore in directory
	private $ignore_files = array('README.md', '.gitignore', '.', '..');
 
	// default array of files to be committed
	private $files = array('modified' => array(), 'added' => array(), 'removed' => array());
 
	function __construct(&$config){

		parent::__construct($config);

		// setup config
		$this->download_name = $this->config->extract_dir . '/download.zip';
 
		$json = $this->config->payload; //isset($_POST['payload']) ? $_POST['payload'] : false;
		if($json){
			$data = json_decode($json);	// decode json into php object
 
			// process all commits
			if(count($data->commits) > 0){
				foreach($data->commits as $commit){
 
					$node = $commit->node;	// capture repo node
					$files = $commit->files;	// capture repo file changes
					$message = $commit->message;	// capture repo message
 
					// reset files list
					$this->files = array(
						'modified' => array(), 
						'added' => array(), 
						'removed' => array());
 
					foreach($files as $file){
						$this->files[$file->type][] = $file->file;
					}
 
					// download repo
					if(!$this->get_repo($node)){
						$this->log('Download of Repo Failed', true);
						return;
					}
 
					// unzip repo download
					if(!$this->unzip_repo()){
						$this->log('Unzip Failed', true);
						return;
					}
 
					// append changes to destination
					$this->parse_changes($node, $message);
 
					// delete zip file
					unlink($this->download_name);
				}
			}else{
				// if no commits have been posted, deploy latest node
				$this->process = 'deploy';
 
				// download repo
				if(!$this->get_repo('master')){
					$this->log('Download of Repo Failed', true);
					return;
				}
 
				// unzip repo download
				if(!$this->unzip_repo()){
					$this->log('Unzip Failed', true);
					return;
				}
 
				$node = $this->get_node_from_dir();
				$message = 'Bitbucket post failed, complete deploy';
				if(!$node){
					$this->log('Node could not be set, no unziped repo', true);
					return;	
				}
 
				// append changes to destination
				$this->parse_changes($node, $message);
 
				// delete zip file
				unlink($this->download_name);
			}
		}else{
			$this->log('No Payload', true);
		}
	}
 
	/**
	 * Extract the downloaded repo
	 * @return boolean
	 */
	function unzip_repo(){

		// init zip archive helper
		$zip = new ZipArchive;
		$res = $zip->open($this->download_name);
		
		if ($res === TRUE) {

			// extract files to base directory
		    $zip->extractTo($this->config->extract_dir . '/');
		    $zip->close();
		    return true;
		}

		return false;
	}
 
	/**
	 * Download the repository from bitbucket
	 * @param  string $node 
	 * @return boolean
	 */
	function get_repo($node = ''){
 
		// create the zip folder
		$fp = fopen($this->download_name, 'w');
 
		// set download url of repository for the relating node
		$ch = curl_init("https://bitbucket.org/".$this->config->user."/".$this->config->repo."/get/".$node.".zip");

 
		// http authentication to access the download
		curl_setopt($ch, CURLOPT_USERPWD, $this->config->user.":".$this->config->encrypt->decrypt($this->config->pass));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
 
		// disable ssl verification if your server doesn't have it
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
		// save the transfered zip folder
		curl_setopt($ch, CURLOPT_FILE, $fp);
 
		// run the curl command
		$result = curl_exec($ch);	//returns true / false
 
		// close curl and file functions
		curl_close($ch);
		fclose($fp);
		return $result;
	}
 
	/**
	 * Apply the repository changes add, edit, delete
	 * @param  string $node    
	 * @param  string $message 
	 * @return void
	 */
	function parse_changes($node = '', $message = ''){
		$src =  $this->config->extract_dir."/".$this->config->user."-".$this->config->repo."-".$node."/";
 
		if(!is_dir($this->config->deploy))
			$this->process = 'deploy';
 
		$this->log('Process: '.$this->process, true);
		$this->log('Commit Message: '.$message, true);
 
		$dest = $this->config->deploy;

		$real_src = realpath($src);
 
		if(!is_dir($real_src)){
			$this->log('Unable to read directory');			
			return;
		}
 
		$output = array();
 
		$objects = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($real_src), 
			RecursiveIteratorIterator::SELF_FIRST);
 
		foreach($objects as $name => $object){
 
			// check to see if file is in ignore list
			if(in_array($object->getBasename(), $this->ignore_files))
				continue;
 
			// remove the first '/' if there is one
			$tmp_name = str_replace($real_src, '', $name);
			if($tmp_name[0] == '/')
				$tmp_name = substr($tmp_name,1);
 
			switch($this->process){
				case 'update':
					// only update changed files
					if(in_array($tmp_name, $this->files['added'])){
						$this->add_file($src . $tmp_name, $dest . $tmp_name);
					}
					if(in_array($tmp_name, $this->files['modified'])){
						$this->modify_file($src . $tmp_name, $dest . $tmp_name);
					}
				break;
				case 'deploy':
					$this->add_file($src . $tmp_name, $dest . $tmp_name);
				break;
			}
		}
 
		// delete all files marked for deleting
		if(!empty($this->files['removed'])){
			foreach($this->files['removed'] as $f){
				$this->removed($dest . $f);
			}
		}
 
		$this->delete($src);
	}
 
	/**
	 * Delete folder recursivly
	 * @param  string $path 
	 * @return void
	 */
	private function delete($path) {
	    $objects = new RecursiveIteratorIterator(
	    	new RecursiveDirectoryIterator($path), 
	    	RecursiveIteratorIterator::CHILD_FIRST);
 
	    foreach ($objects as $object) {
	        if (in_array($object->getBasename(), array('.', '..'))) {
	            continue;
	        } elseif ($object->isDir()) {
	            rmdir($object->getPathname());
	        } elseif ($object->isFile() || $object->isLink()) {
	            unlink($object->getPathname());
	        }
	    }
	    rmdir($path);
	}
 
	/**
	 * Retrieve node from extracted folder name
	 * @return string
	 */
	private function get_node_from_dir(){
		$files = scandir($this->config->extract_dir . '/');
		$starts_with = $this->config->user."-".$this->config->repo."-";

		foreach($files as $f){

			if(is_dir($this->config->extract_dir . '/' .$f)){
				
				// check to see if it starts with 
				if(strpos($f, $starts_with) !== false){
					return substr($f, strlen($starts_with));
				}  
			}
		}
		return false;
	}
 
	/**
	 * Add new file
	 * @param string $src  
	 * @param string $dest 
	 * @return  void
	 */
	private function add_file($src, $dest){
		$this->log('add_file src: '. $src . ' => '.$dest);
		if(!is_dir(dirname($dest)))
			@mkdir(dirname($dest), 0755, true);
		@copy($src, $dest);
	}
 
	/**
	 * Replace file with new copy
	 * @param  string $src  
	 * @param  string $dest 
	 * @return void
	 */
	private function modify_file($src, $dest){
		$this->log('modify_file src: '. $src . ' => '.$dest);
		@copy($src, $dest);
	}
 
	/**
	 * Delete file from directory
	 * @param  string $file
	 * @return void
	 */
	private function removed($file){
		$this->log('remove_file file: '. $file);
 
		if(is_file($file))
			@unlink($file);
	}
 
}
?>
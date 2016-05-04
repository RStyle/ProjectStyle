<?php
define('PLUGINS_FOLDER', 'system/');

class phphooks {
	
	
	//hooks
	public $hooks = array();
	public $hookCalls = 0;
	public $pluginCalls = 0;

	
	//delete a hook
	function unset_hook($tag){
		unset($this->hooks[$tag]);
	}
	
	//delete a hooks
	function unset_hooks($tags){
		foreach($tags as $tag){
			unset($this->hooks[$tag]);
		}
	}
	
	// add a function to a hook
	// $priority: specify the order in which the functions execute.(0-20, 0 first call, 20 last call)	
	function add_hook($tag, $function, $priority = 10){
		$this->hooks[$tag][$priority][] = $function;
	}
	
	// load plugins - *.plugin.php files from a folder
	function load_all_plugins($folder = PLUGINS_FOLDER){
		if($handle = @opendir($folder)){
			while($file = readdir($handle)){
				if(is_file($folder.$file)){
					$control = true;
					if(strpos($file, '.mp.plugin.php') !== FALSE && OUTPUT != 'mp')
						$control = false;
					if(strpos($file, '.tm.plugin.php') !== FALSE && OUTPUT != 'tm')
						$control = false;
					if(strpos($file, '.web.plugin.php') !== FALSE && OUTPUT != 'web')
						$control = false;
					
					if(strpos($file, '.plugin.php') && $control){
						require_once $folder.$file;
					}
				}/* elseif((is_dir($folder.$file))&&($file != '.')&&($file != '..')){
					$this->load_all_plugins($folder.$file.'/');
				}*/
			}
			closedir($handle);
		}
	
	}
	
	
	
	//check whether the hook has any function
	function hook_exist($tag){
		return(trim($this->hooks[$tag])== "")? false : true;
	}
	
	
	// perform all functions of the hook, you can provide argument(or arguments with an array)
	function hook($tag, $arg = '', $return = false){
		$args = array($arg);
		if(isset($this->hooks[$tag])){
			$hooks = $this->hooks[$tag];
			for($i = 0; $i <= 20; $i++){
				if(isset($hooks[$i])){
					$this->hookCalls++;
					foreach($hooks[$i] as $hook){
						$result = call_user_func($hook, $arg);
						$args[] = $result;
						$this->pluginCalls++;
					}
				}
			}
			if($return) return $result;
		}
	}
	
}
?>

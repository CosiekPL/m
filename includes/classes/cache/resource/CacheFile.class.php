<?php



class CacheFile {
	private $path;
	public function __construct()
	{
		$this->path	= is_writable(CACHE_PATH) ? CACHE_PATH : $this->getTempPath();
	}

	private function getTempPath()
	{
		require_once 'includes/libs/wcf/BasicFileUtil.class.php';
		return BasicFileUtil::getTempFolder();
	}

	public function store($Key, $Value) {
		return file_put_contents($this->path.'cache.'.$Key.'.php', $Value);
	}
	
	public function open($Key) {
		if(!file_exists($this->path.'cache.'.$Key.'.php'))
			return false;
			
		return file_get_contents($this->path.'cache.'.$Key.'.php');
	}
	
	public function flush($Key) {
		if(!file_exists($this->path.'cache.'.$Key.'.php'))
			return false;
		
		return unlink($this->path.'cache.'.$Key.'.php');
	}
}
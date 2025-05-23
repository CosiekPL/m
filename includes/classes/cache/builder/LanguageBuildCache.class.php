<?php



class LanguageBuildCache implements BuildCache
{
	public function buildCache()
	{
		$languagePath	= ROOT_PATH.'language/';
		
		$languages	= array();
		
		/** @var $fileInfo SplFileObject */
		foreach (new DirectoryIterator($languagePath) as $fileInfo)
		{
			if(!$fileInfo->isDir() || $fileInfo->isDot()) continue;

			$Lang	= $fileInfo->getBasename();

			if(!file_exists($languagePath.$Lang.'/LANG.cfg')) continue;

			// Fixed BOM problems.
			ob_start();
			$path	 = $languagePath.$Lang.'/LANG.cfg';
			require $path;
			ob_end_clean();
			if(isset($Language['name']))
			{
				$languages[$Lang]	= $Language['name'];
			}
		}
		return $languages;
	}
}
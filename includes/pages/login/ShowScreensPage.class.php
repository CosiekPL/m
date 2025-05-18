<?php




class ShowScreensPage extends AbstractLoginPage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
        $this->setWindow('light');
	}
	
	function show() 
	{
		$screenshots	= array();
		$directoryIterator = new DirectoryIterator('styles/resource/images/login/screens/');
        foreach ($directoryIterator as $fileInfo)
		{
			/** @var $fileInfo DirectoryIterator */
			if (!$fileInfo->isFile())
			{
				continue;
            }			
			
			$thumbnail = 'styles/resource/images/login/screens/'.$fileInfo->getFilename();
			if(file_exists('styles/resource/images/login/screens/thumbnails/'.$fileInfo->getFilename()))
			{
				$thumbnail = 'styles/resource/images/login/screens/thumbnails/'.$fileInfo->getFilename();
			}
			
			$screenshots[]	= array(
				'path' 		=> 'styles/resource/images/login/screens/'.$fileInfo->getFilename(),
				'thumbnail' => $thumbnail,
			);
		}
		
		$this->assign(array(
			'screenshots' => $screenshots
		));

		$this->display('page.screens.default.twig');
	}
}

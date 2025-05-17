<?php



require_once 'includes/classes/cronjob/CronjobTask.interface.php';

class StatisticCronjob implements CronjobTask
{
	function run()
	{
		require 'includes/classes/class.statbuilder.php';
		$stat	= new Statbuilder();
		$stat->MakeStats();
	}
}
<?php



require_once 'includes/classes/cronjob/CronjobTask.interface.php';

class TeamSpeakCronjob implements CronjobTask
{
	function run()
	{
		Cache::get()->add('teamspeak', 'TeamspeakBuildCache');
		Cache::get()->flush('teamspeak');
	}
}
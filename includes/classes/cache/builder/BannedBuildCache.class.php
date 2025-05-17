<?php



class BannedBuildCache implements BuildCache
{
	function buildCache()
	{
		$Data	= Core::getDB()->query("SELECT userID, MAX(banTime) FROM ".BANNED." WHERE banTime > ".TIMESTAMP." GROUP BY userID;");
		$Bans	= array();
		while($Row = $Data->fetchObject())
		{
			$Bans[$Row->userID]	= $Row;
		}

		return $Bans;
	}
}

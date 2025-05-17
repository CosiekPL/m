<?php

/**
 *  2Moons 
 *   by Jan-Otto Kröpke 2009-2016
 *
 * For the full copyright and license information, please view the LICENSE
 *
 * @package 2Moons
 * @author Jan-Otto Kröpke <slaver7@gmail.com>
 * @copyright 2009 Lucky
 * @copyright 2016 Jan-Otto Kröpke <slaver7@gmail.com>
 * @licence MIT
 * @version 1.8.0
 * @link https://github.com/jkroepke/2Moons
 */

declare(strict_types=1);

/**
 * Aktualizuje statystyki gry dla określonej grupy użytkowników
 * 
 * @param int $Start ID użytkownika, od którego zacząć aktualizację
 * @param int $Limit Maksymalna liczba użytkowników do aktualizacji (0 = bez limitu)
 * @return void
 */
function UpdateGameStats(int $Start = 0, int $Limit = 0): void
{	
	$sql = 'SELECT id, authlevel, bana, urlaubs_modus FROM %%USERS%% WHERE id > :start ORDER BY id ASC'.($Limit > 0 ? ' LIMIT '.((int) $Limit) : '');
	$UserList = Database::get()->select($sql, [
		':start' => $Start
	]);
	
	if (empty($UserList))
	{
		return;
	}
	
	$StatUpdate = [];
	$activeUsers = [];
	$blockedIds = [];
	
	foreach (array_keys(ref_cleanup(['userPoints', 'userRanks', 'userRaiders', 'userInactivePlanet', 'userPlanets', 'userBuildPts', 'userFleetPts', 'userDefensePts', 'userResearchPts', 'userProcPts', 'userTechPts'])) as $key)
	{
		$GLOBALS[$key] = [];
	}
	
	$config = Config::get();
	$resource = ResourceMapper::getInstance();

	if ($config->stat == 0 || $config->stat_level == 0)
	{
		$sql = 'TRUNCATE TABLE %%STATPOINTS%%;';
		Database::get()->delete($sql);
		return;
	}
	
	$sql = "LOCK TABLES
		%%USERS%% u WRITE,
		%%USERS%% as u1 WRITE,
		%%PLANETS%% p1 WRITE,
		%%PLANETS%% as p WRITE,
		%%STATPOINTS%% as s WRITE,
		%%STATPOINTS%% s WRITE,
		%%TOPKB%% WRITE,
		%%ALLIANCE%% WRITE,
		%%ALLIANCE%% a WRITE,
		%%TIME_ONLINE%% WRITE;";
		
	Database::get()->nativeQuery($sql);
	
	$sql = 'SELECT s.id_owner as id, SUM(s.tech_points) as tech_points, SUM(s.build_points) as build_points, SUM(s.defs_points) as defs_points, 
        SUM(s.fleet_points) as fleet_points, SUM(s.tech_count) as tech_count, SUM(s.build_count) as build_count, 
        SUM(s.defs_count) as defs_count, SUM(s.fleet_count) as fleet_count, SUM(s.total_points) as total_points
        FROM %%STATPOINTS%% as s WHERE s.stat_type = 1 GROUP BY s.id_owner;';
		
	$UserPoints = Database::get()->nativeQuery($sql);	
	
	$sql = 'SELECT s.id_owner as id, s.tech_points, s.build_points, s.defs_points, s.fleet_points, s.tech_count, s.build_count, s.defs_count, s.fleet_count 
        FROM %%STATPOINTS%% as s WHERE s.stat_type = 1 ORDER BY s.id_owner, s.stat_type ASC;';
	$statData = Database::get()->nativeQuery($sql);
	
	foreach($statData as $statRow) {
		$GLOBALS['userTechPts'][$statRow['id']] = $statRow['tech_points'];
		$GLOBALS['userBuildPts'][$statRow['id']] = $statRow['build_points'];
		$GLOBALS['userDefensePts'][$statRow['id']] = $statRow['defs_points'];
		$GLOBALS['userFleetPts'][$statRow['id']] = $statRow['fleet_points'];
		
		$GLOBALS['userTechCount'][$statRow['id']] = $statRow['tech_count'];
		$GLOBALS['userBuildCount'][$statRow['id']] = $statRow['build_count'];
		$GLOBALS['userDefenseCount'][$statRow['id']] = $statRow['defs_count'];
		$GLOBALS['userFleetCount'][$statRow['id']] = $statRow['fleet_count'];
	}
	
	$sql = 'SELECT id, id_owner FROM %%PLANETS%% WHERE destruyed = 0 ORDER BY id_owner ASC;';
	$PlanetsRAW = Database::get()->nativeQuery($sql);
	
	foreach($PlanetsRAW as $planetRow) {
		$GLOBALS['userPlanets'][$planetRow['id_owner']][] = $planetRow['id'];
	}
	
	$sql = 'SELECT id, id_owner FROM %%PLANETS%% WHERE destruyed = 0 AND last_update > :inacttime ORDER BY id_owner ASC;';
	$PlanetsRAW = Database::get()->nativeQuery($sql, [
		':inacttime' => (TIMESTAMP - 86400 * 7)
	]);
	
	foreach($PlanetsRAW as $planetRow) {
		$GLOBALS['userInactivePlanet'][$planetRow['id_owner']][] = $planetRow['id'];
	}
	
	$sql = 'SELECT * FROM %%USERS%% ORDER BY id ASC;';
	$UsersRAW = Database::get()->nativeQuery($sql);
	
	$Count = 0;
	foreach($UsersRAW as $UserData)
	{
        if ($UserData['authlevel'] == AUTH_USR) {
		    unset($GLOBALS['userProcPts']);
		    if($Count == 0) {
			    $GLOBALS['userProcPts'] = ref_cleanup([]);
		    }
		    $Count++;
		
		    $TechPts = $DefPts = $BuildPts = $FleetPts = 0;
	
		    if(!isset($GLOBALS['userInactivePlanet'][$UserData['id']])) {
			    $GLOBALS['userInactivePlanet'][$UserData['id']] = [];
		    }
				
		    if(isset($GLOBALS['userPlanets'][$UserData['id']])) {
			    $activeUsers[] = $UserData['id'];
				
			    foreach($GLOBALS['userPlanets'][$UserData['id']] as $PlanetID)
			    {				
				    if(!in_array($PlanetID, $GLOBALS['userInactivePlanet'][$UserData['id']]))
				    {
					    $TechPts = 0;
						
                    $sql = 'SELECT * FROM %%PLANETS%% WHERE id = :planetID;';
                    $selectPlanet = Database::get()->selectSingle($sql, [
                        ':planetID' => $PlanetID
                    ]);

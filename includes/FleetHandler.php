<?php



$token	= getRandomString();
$db		= Database::get();

$fleetResult	= $db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = :token WHERE `lock` IS NULL AND `time` <= :time;", array(
	':time'		=> TIMESTAMP,
	':token'	=> $token
));

if($db->rowCount() !== 0) {
	require 'includes/classes/class.FlyingFleetHandler.php';
	
	$fleetObj	= new FlyingFleetHandler();
	$fleetObj->setToken($token);
	$fleetObj->run();

	$db->update("UPDATE %%FLEETS_EVENT%% SET `lock` = NULL WHERE `lock` = :token;", array(
		':token' => $token
	));
}
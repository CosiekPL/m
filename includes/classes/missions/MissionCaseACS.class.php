<?php
class MissionCaseACS extends MissionFunctions implements Mission
{
	/**
	 * Konstruktor - inicjalizuje obiekt misji połączonego ataku
	 * @param array $Fleet Dane floty
	 */	
	function __construct($Fleet)
	{
		$this->_fleet	= $Fleet;
	}
	
	/**
	 * Obsługuje dotarcie floty do celu w przypadku misji ACS (Wspólny Atak)
	 * Ustawia status floty na powrót i zapisuje zmiany
	 */
	function TargetEvent()
	{
		$this->setState(FLEET_RETURN);
		$this->SaveFleet();
		return;
	}
	
	/**
	 * Obsługuje zakończenie pobytu floty w miejscu docelowym
	 * W przypadku ACS nie wykonuje żadnych akcji
	 */
	function EndStayEvent()
	{
		return;
	}
	
	/**
	 * Obsługuje powrót floty do miejsca początkowego
	 * Wysyła wiadomość do właściciela floty i przywraca flotę
	 */
	function ReturnEvent()
	{
		$LNG		= $this->getLanguage(NULL, $this->_fleet['fleet_owner']);
		$sql		= 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
		$planetName	= Database::get()->selectSingle($sql, array(
			':planetId'	=> $this->_fleet['fleet_start_id'],
		), 'name');

		$Message 	= sprintf(
			$LNG['sys_fleet_won'],
			$planetName,
			GetTargetAddressLink($this->_fleet, ''),
			pretty_number($this->_fleet['fleet_resource_metal']),
			$LNG['tech'][901],
			pretty_number($this->_fleet['fleet_resource_crystal']),
			$LNG['tech'][902],
			pretty_number($this->_fleet['fleet_resource_deuterium']),
			$LNG['tech'][903]
		);

		PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 4, $LNG['sys_mess_fleetback'],
			$Message, $this->_fleet['fleet_end_time'], NULL, 1, $this->_fleet['fleet_universe']);

		$this->RestoreFleet();
	}
}

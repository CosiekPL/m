<?php
class MissionCaseStay extends MissionFunctions implements Mission
{
    /**
     * @var array Dane floty wykonującej misję.
     */
    protected $_fleet;

    /**
     * Konstruktor klasy. Inicjalizuje dane floty.
     *
     * @param array $Fleet Dane floty.
     */
    function __construct($Fleet)
    {
        $this->_fleet = $Fleet;
    }

    /**
     * Wykonuje akcje po dotarciu floty do celu (stacjonowanie).
     * Aktualizuje zasoby floty o połowę zużytego paliwa za lot.
     * Wysyła wiadomość do właściciela stacjonującej planety.
     * Na końcu przywraca flotę do stanu gotowości.
     *
     * @return void
     */
    function TargetEvent(): void
    {
        // Pobierz dane użytkownika wysyłającego flotę.
        $sql = 'SELECT * FROM %%USERS%% WHERE id = :userId;';
        $senderUser = Database::get()->selectSingle($sql, [
            ':userId' => $this->_fleet['fleet_owner']
        ]);

        // Pobierz współczynniki wpływające na rozgrywkę dla wysyłającego użytkownika w momencie startu floty.
        $senderUser['factor'] = getFactors($senderUser, 'basic', $this->_fleet['fleet_start_time']);

        // Rozpakuj dane jednostek floty z serializowanej postaci.
        $fleetArray = FleetFunctions::unserialize($this->_fleet['fleet_array']);
        // Oblicz czas trwania lotu do momentu stacjonowania.
        $duration = $this->_fleet['fleet_start_time'] - $this->_fleet['start_time'];

        // Pobierz współczynnik prędkości gry.
        $SpeedFactor = FleetFunctions::GetGameSpeedFactor();
        // Oblicz odległość między planetą startową a docelową.
        $distance = FleetFunctions::GetTargetDistance(
            [$this->_fleet['fleet_start_galaxy'], $this->_fleet['fleet_start_system'], $this->_fleet['fleet_start_planet']],
            [$this->_fleet['fleet_end_galaxy'], $this->_fleet['fleet_end_system'], $this->_fleet['fleet_end_planet']]
        );

        // Oblicz całkowite zużycie paliwa za lot i dodaj połowę tej wartości do zasobów floty (za stacjonowanie).
        $consumption = FleetFunctions::GetFleetConsumption($fleetArray, $duration, $distance, $senderUser, $SpeedFactor);
        $this->UpdateFleet('fleet_resource_deuterium', $this->_fleet['fleet_resource_deuterium'] + ($consumption / 2));

        // Załaduj język użytkownika wysyłającego flotę.
        $LNG = $this->getLanguage($senderUser['lang']);
        // Pobierz ID właściciela stacjonującej planety.
        $TargetUserID = $this->_fleet['fleet_target_owner'];
        // Utwórz wiadomość informującą o stacjonującej flocie i jej zasobach.
        $TargetMessage = sprintf($LNG['sys_stat_mess'], GetTargetAddressLink($this->_fleet, ''), pretty_number($this->_fleet['fleet_resource_metal']), $LNG['tech'][901], pretty_number($this->_fleet['fleet_resource_crystal']), $LNG['tech'][902], pretty_number($this->_fleet['fleet_resource_deuterium']), $LNG['tech'][903]);

        // Wyślij wiadomość do właściciela stacjonującej planety.
        PlayerUtil::sendMessage($TargetUserID, 0, $LNG['sys_mess_tower'], 5,
            $LNG['sys_stat_mess_stay'], $TargetMessage, $this->_fleet['fleet_start_time'], null, 1, $this->_fleet['fleet_universe']);

        // Przywróć flotę do stanu gotowości (prawdopodobnie usuwa flagę 'stacjonowania').
        $this->RestoreFleet(false);
    }

    /**
     * Wykonuje akcje po upłynięciu czasu stacjonowania (nieużywane w tym przypadku, brak logiki).
     *
     * @return void
     */
    function EndStayEvent(): void
    {
        return;
    }

    /**
     * Wykonuje akcje po powrocie floty ze stacjonowania.
     * Wysyła wiadomość do właściciela floty o powrocie i zasobach.
     * Na końcu przywraca flotę do stanu gotowości.
     *
     * @return void
     */
    function ReturnEvent(): void
    {
        // Załaduj język właściciela floty.
        $LNG = $this->getLanguage(null, $this->_fleet['fleet_owner']);

        // Utwórz wiadomość informującą o powrocie floty i jej zasobach.
        $Message = sprintf($LNG['sys_stat_mess'],
            GetStartAddressLink($this->_fleet, ''),
            pretty_number($this->_fleet['fleet_resource_metal']), $LNG['tech'][901],
            pretty_number($this->_fleet['fleet_resource_crystal']), $LNG['tech'][902],
            pretty_number($this->_fleet['fleet_resource_deuterium']), $LNG['tech'][903]
        );

        // Wyślij wiadomość do właściciela floty o powrocie.
        PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 4, $LNG['sys_mess_fleetback'],
            $Message, $this->_fleet['fleet_end_time'], null, 1, $this->_fleet['fleet_universe']);

        // Przywróć flotę do stanu gotowości.
        $this->RestoreFleet();
    }
}
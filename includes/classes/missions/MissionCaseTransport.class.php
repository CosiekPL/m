<?php

class MissionCaseTransport extends MissionFunctions implements Mission
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
     * Wykonuje akcje po dotarciu floty do celu (transport surowców).
     * Wysyła wiadomości do właściciela floty i właściciela planety docelowej (jeśli są różni).
     * Przekazuje surowce na planetę docelową.
     * Ustawia status floty na 'wraca' (FLEET_RETURN) i zapisuje zmiany w bazie danych.
     *
     * @return void
     */
    function TargetEvent(): void
    {
        $sql = 'SELECT name FROM %%PLANETS%% WHERE `id` = :planetId;';

        // Pobierz nazwę planety startowej.
        $startPlanetName = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_start_id']
        ], 'name');

        // Pobierz nazwę planety docelowej.
        $targetPlanetName = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_end_id']
        ], 'name');

        // Załaduj język właściciela floty.
        $LNG = $this->getLanguage(null, $this->_fleet['fleet_owner']);

        // Utwórz wiadomość dla właściciela floty o dostarczeniu surowców.
        $Message = sprintf($LNG['sys_tran_mess_owner'],
            $targetPlanetName, GetTargetAddressLink($this->_fleet, ''),
            pretty_number($this->_fleet['fleet_resource_metal']), $LNG['tech'][901],
            pretty_number($this->_fleet['fleet_resource_crystal']), $LNG['tech'][902],
            pretty_number($this->_fleet['fleet_resource_deuterium']), $LNG['tech'][903]
        );

        // Wyślij wiadomość do właściciela floty.
        PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 5,
            $LNG['sys_mess_transport'], $Message, $this->_fleet['fleet_start_time'], null, 1, $this->_fleet['fleet_universe']);

        // Jeśli właściciel floty i planety docelowej są różni.
        if ($this->_fleet['fleet_target_owner'] != $this->_fleet['fleet_owner']) {
            // Załaduj język właściciela planety docelowej.
            $LNG = $this->getLanguage(null, $this->_fleet['fleet_target_owner']);
            // Utwórz wiadomość dla właściciela planety docelowej o otrzymaniu transportu.
            $Message = sprintf($LNG['sys_tran_mess_user'],
                $startPlanetName, GetStartAddressLink($this->_fleet, ''),
                $targetPlanetName, GetTargetAddressLink($this->_fleet, ''),
                pretty_number($this->_fleet['fleet_resource_metal']), $LNG['tech'][901],
                pretty_number($this->_fleet['fleet_resource_crystal']), $LNG['tech'][902],
                pretty_number($this->_fleet['fleet_resource_deuterium']), $LNG['tech'][903]
            );

            // Wyślij wiadomość do właściciela planety docelowej.
            PlayerUtil::sendMessage($this->_fleet['fleet_target_owner'], 0, $LNG['sys_mess_tower'], 5,
                $LNG['sys_mess_transport'], $Message, $this->_fleet['fleet_start_time'], null, 1, $this->_fleet['fleet_universe']);
        }

        // Przekaż surowce na planetę docelową.
        $this->StoreGoodsToPlanet();
        // Ustaw status floty na powrót.
        $this->setState(FLEET_RETURN);
        // Zapisz zmiany statusu floty w bazie danych.
        $this->SaveFleet();
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
     * Wykonuje akcje po powrocie floty z transportu.
     * Wysyła wiadomość do właściciela floty o powrocie.
     * Na końcu przywraca flotę do stanu gotowości.
     *
     * @return void
     */
    function ReturnEvent(): void
    {
        // Załaduj język właściciela floty.
        $LNG = $this->getLanguage(null, $this->_fleet['fleet_owner']);
        // Pobierz nazwę planety startowej.
        $sql = 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
        $planetName = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_start_id'],
        ], 'name');

        // Utwórz wiadomość informującą o powrocie floty.
        $Message = sprintf($LNG['sys_tran_mess_back'], $planetName, GetStartAddressLink($this->_fleet, ''));

        // Wyślij wiadomość do właściciela floty.
        PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 4, $LNG['sys_mess_fleetback'],
            $Message, $this->_fleet['fleet_end_time'], null, 1, $this->_fleet['fleet_universe']);

        // Przywróć flotę do stanu gotowości.
        $this->RestoreFleet();
    }
}

// Sugestie ulepszeń:

// 1. Logowanie: Dodanie logowania informacji o przetransportowanych surowcach.
// 2. Sprawdzenie pojemności: Przed przekazaniem surowców na planetę docelową, można by sprawdzić, czy planeta ma wystarczającą pojemność magazynów.
// 3. Obsługa błędów: Dodanie obsługi błędów w przypadku niepowodzenia aktualizacji zasobów na planecie docelowej.
// 4. Możliwość częściowego transportu: Implementacja opcji transportu tylko części załadowanych surowców.
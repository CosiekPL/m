<?php

class MissionCaseStayAlly extends MissionFunctions implements Mission
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
     * Wykonuje akcje po dotarciu floty do celu (stacjonowanie u sojusznika).
     * Ustawia status floty na 'stacjonuje' (FLEET_HOLD) i zapisuje zmiany w bazie danych.
     *
     * @return void
     */
    function TargetEvent(): void
    {
        $this->setState(FLEET_HOLD); // Ustaw status floty na stacjonowanie.
        $this->SaveFleet();          // Zapisz zmiany statusu floty w bazie danych.
    }

    /**
     * Wykonuje akcje po upłynięciu czasu stacjonowania (rozpoczęcie powrotu).
     * Ustawia status floty na 'wraca' (FLEET_RETURN) i zapisuje zmiany w bazie danych.
     *
     * @return void
     */
    function EndStayEvent(): void
    {
        $this->setState(FLEET_RETURN); // Ustaw status floty na powrót.
        $this->SaveFleet();           // Zapisz zmiany statusu floty w bazie danych.
    }

    /**
     * Wykonuje akcje po powrocie floty ze stacjonowania u sojusznika.
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
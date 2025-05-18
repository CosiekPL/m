<?php

class MissionCaseColonisation extends MissionFunctions implements Mission
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
     * Wykonuje akcje po dotarciu floty do celu (kolonizacja).
     * Sprawdza dostępność i warunki kolonizacji planety.
     * Tworzy nową planetę dla gracza, jeśli warunki są spełnione.
     * Wysyła raport do gracza o wyniku kolonizacji.
     * Niszczy statek kolonizacyjny (jeśli tylko jeden leciał) lub zmniejsza jego liczbę.
     * Ustawia status floty na 'wraca' (FLEET_RETURN) i zapisuje zmiany w bazie danych.
     *
     * @return void
     */
    function TargetEvent(): void
    {
        $db = Database::get(); // Pobierz instancję bazy danych.

        // Pobierz dane użytkownika wysyłającego flotę.
        $sql = 'SELECT * FROM %%USERS%% WHERE `id` = :userId;';
        $senderUser = $db->selectSingle($sql, [
            ':userId' => $this->_fleet['fleet_owner'],
        ]);

        // Pobierz współczynniki wpływające na rozgrywkę dla wysyłającego użytkownika w momencie startu floty.
        $senderUser['factor'] = getFactors($senderUser, 'basic', $this->_fleet['fleet_start_time']);

        // Załaduj język użytkownika.
        $LNG = $this->getLanguage($senderUser['lang']);

        // Sprawdź, czy pozycja w galaktyce jest prawidłowa.
        $checkPosition = PlayerUtil::checkPosition($this->_fleet['fleet_universe'], $this->_fleet['fleet_end_galaxy'],
            $this->_fleet['fleet_end_system'], $this->_fleet['fleet_end_planet']);
        // Sprawdź, czy pozycja w galaktyce jest wolna.
        $isPositionFree = PlayerUtil::isPositionFree($this->_fleet['fleet_universe'], $this->_fleet['fleet_end_galaxy'],
            $this->_fleet['fleet_end_system'], $this->_fleet['fleet_end_planet']);

        // Jeśli pozycja nie jest wolna lub nieprawidłowa.
        if (!$isPositionFree || !$checkPosition) {
            $message = sprintf($LNG['sys_colo_notfree'], GetTargetAddressLink($this->_fleet, '')); // Komunikat o zajętej pozycji.
        } else {
            // Sprawdź, czy technologia kolonizacyjna pozwala na zajęcie danej pozycji planety.
            $allowPlanetPosition = PlayerUtil::allowPlanetPosition($this->_fleet['fleet_end_planet'], $senderUser);
            if (!$allowPlanetPosition) {
                $message = sprintf($LNG['sys_colo_notech'], GetTargetAddressLink($this->_fleet, '')); // Komunikat o braku technologii.
            } else {
                // Sprawdź liczbę posiadanych kolonii.
                $sql = 'SELECT COUNT(*) as state
                FROM %%PLANETS%%
                WHERE `id_owner`	= :userId
                AND `planet_type`	= :type
                AND `destruyed`		= :destroyed;';

                $currentPlanetCount = $db->selectSingle($sql, [
                    ':userId'    => $this->_fleet['fleet_owner'],
                    ':type'      => 1, // Typ planety (prawdopodobnie planeta).
                    ':destroyed' => 0  // Tylko niezniszczone planety.
                ], 'state');

                // Pobierz maksymalną liczbę kolonii dozwoloną dla gracza.
                $maxPlanetCount = PlayerUtil::maxPlanetCount($senderUser);

                // Jeśli gracz osiągnął limit kolonii.
                if ($currentPlanetCount >= $maxPlanetCount) {
                    $message = sprintf($LNG['sys_colo_maxcolo'], GetTargetAddressLink($this->_fleet, ''), $maxPlanetCount); // Komunikat o osiągniętym limicie.
                } else {
                    // Utwórz nową planetę dla gracza.
                    $NewOwnerPlanet = PlayerUtil::createPlanet($this->_fleet['fleet_end_galaxy'], $this->_fleet['fleet_end_system'],
                        $this->_fleet['fleet_end_planet'], $this->_fleet['fleet_universe'], $this->_fleet['fleet_owner'],
                        $LNG['fcp_colony'], false, $senderUser['authlevel']);

                    // Jeśli tworzenie planety nie powiodło się.
                    if ($NewOwnerPlanet === false) {
                        $message = sprintf($LNG['sys_colo_badpos'], GetTargetAddressLink($this->_fleet, '')); // Komunikat o nieprawidłowej pozycji.
                        $this->setState(FLEET_RETURN); // Ustaw flotę na powrót.
                    } else {
                        // Kolonizacja udana.
                        $this->_fleet['fleet_end_id'] = $NewOwnerPlanet; // Zaktualizuj ID planety docelowej na nowo utworzoną.
                        $message = sprintf($LNG['sys_colo_allisok'], GetTargetAddressLink($this->_fleet, '')); // Komunikat o udanej kolonizacji.
                        $this->StoreGoodsToPlanet(); // Przenieś surowce z floty na nową planetę.
                        // Jeśli leciał tylko jeden statek kolonizacyjny, zniszcz flotę.
                        if ($this->_fleet['fleet_amount'] == 1) {
                            $this->KillFleet();
                        } else {
                            // Jeśli leciało więcej niż jeden, zmniejsz liczbę statków kolonizacyjnych o jeden.
                            $CurrentFleet = explode(";", $this->_fleet['fleet_array']);
                            $NewFleet = '';
                            foreach ($CurrentFleet as $Group) {
                                if (empty($Group)) continue;

                                $Class = explode(",", $Group);
                                if ($Class[0] == 208 && $Class[1] > 1)
                                    $NewFleet .= $Class[0] . "," . ($Class[1] - 1) . ";";
                                elseif ($Class[0] != 208 && $Class[1] > 0)
                                    $NewFleet .= $Class[0] . "," . $Class[1] . ";";
                            }

                            $this->UpdateFleet('fleet_array', $NewFleet);
                            $this->UpdateFleet('fleet_amount', ($this->_fleet['fleet_amount'] - 1));
                            $this->UpdateFleet('fleet_resource_metal', 0);
                            $this->UpdateFleet('fleet_resource_crystal', 0);
                            $this->UpdateFleet('fleet_resource_deuterium', 0);
                        }
                    }
                }
            }
        }

        // Wyślij raport do gracza o wyniku kolonizacji.
        PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_colo_mess_from'], 4, $LNG['sys_colo_mess_report'],
            $message, $this->_fleet['fleet_start_time'], null, 1, $this->_fleet['fleet_universe']);

        $this->setState(FLEET_RETURN); // Ustaw flotę na powrót.
        $this->SaveFleet(); // Zapisz zmiany w bazie danych.
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
     * Wykonuje akcje po powrocie floty z kolonizacji.
     * Przywraca flotę do stanu gotowości.
     *
     * @return void
     */
    function ReturnEvent(): void
    {
        $this->RestoreFleet();
    }
}
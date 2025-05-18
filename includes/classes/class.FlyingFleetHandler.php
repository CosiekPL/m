<?php

require_once 'includes/classes/class.MissionFunctions.php'; // Załaduj klasę bazową dla funkcji misji.
require_once 'includes/classes/missions/Mission.interface.php'; // Załaduj interfejs dla klas misji.

/**
 * Klasa FlyingFleetHandler.
 * Obsługuje logikę wykonywania misji flot w locie na podstawie zdarzeń.
 */
class FlyingFleetHandler
{
    /**
     * @var string Token blokady używany do identyfikacji flot do przetworzenia.
     */
    protected $token;

    /**
     * @var array Mapowanie ID misji na nazwy klas obsługujących te misje.
     */
    public static $missionObjPattern = [
        1  => 'MissionCaseAttack',       // Atak.
        2  => 'MissionCaseACS',          // Atak Skoordynowany.
        3  => 'MissionCaseTransport',    // Transport.
        4  => 'MissionCaseStay',         // Stacjonuj.
        5  => 'MissionCaseStayAlly',     // Stacjonuj u sojusznika.
        6  => 'MissionCaseSpy',          // Szpieguj.
        7  => 'MissionCaseColonisation', // Kolonizuj.
        8  => 'MissionCaseRecycling',    // Recykling.
        9  => 'MissionCaseDestruction',  // Zniszcz.
        10 => 'MissionCaseMIP',          // Atak Rakietami Międzyplanetarnymi.
        11 => 'MissionCaseFoundDM',      // Znajdź Ciemną Materię (ekspedycja).
        15 => 'MissionCaseExpedition',   // Ekspedycja.
    ];

    /**
     * Ustawia token blokady dla handler'a, aby przetwarzał tylko floty z tym tokenem.
     *
     * @param string $token Token blokady.
     *
     * @return void
     */
    function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * Główna metoda klasy. Pobiera floty z zadanym tokenem blokady i uruchamia
     * odpowiednią akcję misji w zależności od statusu floty.
     *
     * @return void
     */
    function run(): void
    {
        $db = Database::get(); // Pobierz instancję bazy danych.

        // Zapytanie SQL pobierające wszystkie floty, których zdarzenie ma ustawiony pasujący token blokady.
        $sql = 'SELECT %%FLEETS%%.*
        FROM %%FLEETS_EVENT%%
        INNER JOIN %%FLEETS%% ON fleetID = fleet_id
        WHERE `lock` = :token;';

        $fleetResult = $db->select($sql, [
            ':token' => $this->token
        ]);

        // Iteruj po wszystkich znalezionych flotach.
        foreach ($fleetResult as $fleetRow) {
            // Jeśli nie znaleziono klasy obsługującej dany typ misji.
            if (!isset(self::$missionObjPattern[$fleetRow['fleet_mission']])) {
                // Usuń flotę, ponieważ nie można jej obsłużyć.
                $sql = 'DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;';
                $db->delete($sql, [
                    ':fleetId' => $fleetRow['fleet_id']
                ]);
                continue; // Przejdź do następnej floty.
            }

            // Utwórz nazwę klasy obsługującej misję.
            $missionName = self::$missionObjPattern[$fleetRow['fleet_mission']];

            // Skonstruuj ścieżkę do pliku klasy misji.
            $path = 'includes/classes/missions/' . $missionName . '.class.php';
            require_once $path; // Załaduj plik klasy misji.
            /** @var Mission $missionObj Utwórz instancję klasy misji. */
            $missionObj = new $missionName($fleetRow);

            // Wykonaj odpowiednią akcję misji w zależności od statusu floty ('fleet_mess').
            switch ($fleetRow['fleet_mess']) {
                case 0: // Dotarcie do celu.
                    $missionObj->TargetEvent();
                    break;
                case 1: // Powrót do planety startowej.
                    $missionObj->ReturnEvent();
                    break;
                case 2: // Zakończenie stacjonowania.
                    $missionObj->EndStayEvent();
                    break;
            }
        }
    }
}

// Sugestie ulepszeń:

// 1. Logowanie: Dodanie logowania, które floty są przetwarzane i jakie akcje są wykonywane.
// 2. Obsługa błędów: Dodanie try-catch wokół tworzenia instancji klasy misji i wykonywania akcji, aby zapobiec przerwaniu całego procesu w przypadku błędu jednej misji.
// 3. Walidacja danych: Dodatkowa walidacja danych floty przed przekazaniem ich do klasy misji.
// 4. Optymalizacja zapytań: Upewnienie się, że zapytania do bazy danych w klasach misji są zoptymalizowane.
// 5. Skalowalność: W przypadku bardzo dużej liczby flot, rozważenie mechanizmów kolejkowania lub przetwarzania wsadowego.
// 6. Testowanie: Dodanie testów jednostkowych dla poszczególnych klas misji.
// 7. Kompatybilność z PHP 8.4: Upewnienie się, że wszystkie używane klasy i biblioteki są kompatybilne z PHP 8.4.
<?php

declare(strict_types=1);

/**
 * Klasa obsługująca misje flot
 * Zarządza wykonywaniem akcji dla lotów flot
 */
class MissionHandler
{
    /**
     * Instancja bazy danych
     */
    private static ?Database $db = null;
    
    /**
     * Aktualny czas serwera
     */
    private static int $time = 0;
    
    /**
     * Inicjalizuje obsługę misji
     * 
     * @param int $time Aktualny czas serwera
     */
    public static function initialize(int $time = 0): void
    {
        self::$time = ($time > 0) ? $time : TIMESTAMP;
        self::$db = Database::get();
    }
    
    /**
     * Przetwarza wszystkie oczekujące akcje flot
     */
    public static function processFleets(): void
    {
        if (self::$time == 0) {
            self::initialize();
        }
        
        // Pobieramy listę akcji flot do wykonania
        $fleetResult = self::getFleetEvents();
        
        if (empty($fleetResult)) {
            return;
        }
        
        foreach ($fleetResult as $fleetRow) {
            // Pobierz dane floty
            $fleet = self::getFleetData($fleetRow['fleetID']);
            
            if (empty($fleet)) {
                // Flota już nie istnieje, usuń zdarzenie
                self::removeFleetEvent($fleetRow['fleetID']);
                continue;
            }
            
            // Utwórz klasę misji na podstawie typu misji
            $missionClass = self::getMissionClass($fleet['fleet_mission']);
            
            if ($missionClass === null) {
                // Nieznany typ misji, usuń flotę i zdarzenie
                self::removeFleet($fleet['fleet_id']);
                self::removeFleetEvent($fleet['fleet_id']);
                continue;
            }
            
            // Inicjalizuj misję i wykonaj odpowiednią akcję w zależności od stanu floty
            $mission = new $missionClass($fleet);
            
            switch ($fleet['fleet_mess']) {
                case FLEET_OUTWARD:
                    // Flota dociera do celu
                    $mission->TargetEvent();
                    break;
                case FLEET_HOLD:
                    // Flota kończy pobyt w miejscu docelowym
                    $mission->EndStayEvent();
                    break;
                case FLEET_RETURN:
                    // Flota wraca do miejsca początkowego
                    $mission->ReturnEvent();
                    break;
            }
            
            // Usuń zdarzenie, które właśnie zostało wykonane
            self::removeFleetEvent($fleet['fleet_id']);
        }
    }
    
    /**
     * Pobiera wydarzenia flot do wykonania
     */
    private static function getFleetEvents(): array
    {
        $sql = "SELECT fleetID 
                FROM %%FLEET_EVENT%% 
                WHERE `time` <= :time 
                ORDER BY `time` ASC;";
                
        return self::$db->select($sql, [
            ':time' => self::$time
        ]);
    }
    
    /**
     * Pobiera dane floty
     */
    private static function getFleetData(int $fleetId): ?array
    {
        $sql = "SELECT * FROM %%FLEETS%% WHERE fleet_id = :fleetId;";
        
        return self::$db->selectSingle($sql, [
            ':fleetId' => $fleetId
        ]);
    }
    
    /**
     * Usuwa zdarzenie floty
     */
    private static function removeFleetEvent(int $fleetId): void
    {
        $sql = "DELETE FROM %%FLEET_EVENT%% WHERE fleetID = :fleetId;";
        
        self::$db->delete($sql, [
            ':fleetId' => $fleetId
        ]);
    }
    
    /**
     * Usuwa flotę
     */
    private static function removeFleet(int $fleetId): void
    {
        $sql = "DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;";
        
        self::$db->delete($sql, [
            ':fleetId' => $fleetId
        ]);
    }
    
    /**
     * Pobiera klasę odpowiedzialną za dany typ misji
     */
    private static function getMissionClass(int $missionId): ?string
    {
        $missionTypes = [
            1 => 'MissionCaseAttack',        // Atak
            2 => 'MissionCaseGroupAttack',   // Atak grupowy
            3 => 'MissionCaseTransport',     // Transport
            4 => 'MissionCaseStay',          // Stacjonowanie
            5 => 'MissionCaseHold',          // Zatrzymanie
            6 => 'MissionCaseSpy',           // Szpiegostwo
            7 => 'MissionCaseColonisation',  // Kolonizacja
            8 => 'MissionCaseRecycling',     // Recykling
            9 => 'MissionCaseDestruction',   // Zniszczenie
            10 => 'MissionCaseMissile',      // Atak rakietowy
            11 => 'MissionCaseDarkmatter',   // Ekspedycja ciemnej materii
            15 => 'MissionCaseExpedition',   // Ekspedycja
        ];
        
        if (!isset($missionTypes[$missionId])) {
            return null;
        }
        
        // Sprawdź czy klasa istnieje
        $className = $missionTypes[$missionId];
        
        if (!class_exists($className)) {
            return null;
        }
        
        return $className;
    }
    
    /**
     * Dodaje nowe zdarzenie floty
     * 
     * @param int $fleetId ID floty
     * @param int $time Czas wykonania
     */
    public static function addFleetEvent(int $fleetId, int $time): void
    {
        $sql = "INSERT INTO %%FLEET_EVENT%% (`fleetID`, `time`) VALUES (:fleetId, :time);";
        
        self::$db->insert($sql, [
            ':fleetId' => $fleetId,
            ':time'    => $time
        ]);
    }
    
    /**
     * Aktualizuje czas zdarzenia floty
     * 
     * @param int $fleetId ID floty
     * @param int $time Nowy czas wykonania
     */
    public static function updateFleetEvent(int $fleetId, int $time): void
    {
        $sql = "UPDATE %%FLEET_EVENT%% SET `time` = :time WHERE fleetID = :fleetId;";
        
        self::$db->update($sql, [
            ':fleetId' => $fleetId,
            ':time'    => $time
        ]);
    }
    
    /**
     * Pobiera czas następnego zdarzenia floty
     * Używane do ustawienia czasu kolejnego wykonania cronów
     */
    public static function getNextEventTime(): int
    {
        $sql = "SELECT MIN(`time`) as nextTime FROM %%FLEET_EVENT%%;";
        $nextTime = self::$db->selectSingle($sql, [], 'nextTime');
        
        return (int)$nextTime;
    }
    
    /**
     * Tworzy nową flotę i dodaje odpowiednie zdarzenia
     * 
     * @param array $fleetData Dane floty
     * @return int ID utworzonej floty
     */
    public static function createFleet(array $fleetData): int
    {
        // Podstawowe pola wymagane dla floty
        $requiredFields = [
            'fleet_owner', 'fleet_mission', 'fleet_amount', 'fleet_array',
            'fleet_start_id', 'fleet_start_galaxy', 'fleet_start_system', 'fleet_start_planet',
            'fleet_end_id', 'fleet_end_galaxy', 'fleet_end_system', 'fleet_end_planet',
            'fleet_start_time', 'fleet_end_time'
        ];
        
        // Sprawdź czy wszystkie wymagane pola są dostępne
        foreach ($requiredFields as $field) {
            if (!isset($fleetData[$field])) {
                throw new InvalidArgumentException("Brakujące pole floty: {$field}");
            }
        }
        
        // Ustaw domyślne wartości dla zasobów jeśli nie są podane
        $resources = ['metal', 'crystal', 'deuterium', 'darkmatter'];
        foreach ($resources as $resource) {
            $fleetData["fleet_resource_{$resource}"] = $fleetData["fleet_resource_{$resource}"] ?? 0;
        }
        
        // Dodaj flotę do bazy danych
        $fleetColumns = [];
        $fleetValues = [];
        $fleetParams = [];
        
        foreach ($fleetData as $column => $value) {
            $fleetColumns[] = "`{$column}`";
            $fleetValues[] = ":{$column}";
            $fleetParams[":{$column}"] = $value;
        }
        
        $sql = "INSERT INTO %%FLEETS%% (" . implode(', ', $fleetColumns) . ") VALUES (" . implode(', ', $fleetValues) . ");";
        self::$db->insert($sql, $fleetParams);
        
        // Pobierz ID utworzonej floty
        $fleetId = self::$db->lastInsertId();
        
        // Dodaj zdarzenia dla floty
        self::addFleetEvents($fleetId, $fleetData);
        
        return $fleetId;
    }
    
    /**
     * Dodaje zdarzenia dla nowej floty
     */
    private static function addFleetEvents(int $fleetId, array $fleetData): void
    {
        // Dodaj zdarzenie dotarcia do celu
        self::addFleetEvent($fleetId, $fleetData['fleet_start_time']);
        
        // Jeśli flota ma czas postoju, dodaj zdarzenie końca postoju
        if (isset($fleetData['fleet_stay_time']) && $fleetData['fleet_stay_time'] > 0) {
            self::addFleetEvent($fleetId, $fleetData['fleet_stay_time']);
        }
        
        // Dodaj zdarzenie powrotu floty
        self::addFleetEvent($fleetId, $fleetData['fleet_end_time']);
    }
    
    /**
     * Sprawdza czy gracz ma aktywne misje danego typu
     * 
     * @param int $userId ID gracza
     * @param int $missionId ID misji (np. 7 = kolonizacja)
     * @return bool True jeśli gracz ma aktywną misję danego typu
     */
    public static function hasActiveMission(int $userId, int $missionId): bool
    {
        $sql = "SELECT COUNT(*) as count 
                FROM %%FLEETS%% 
                WHERE fleet_owner = :userId 
                AND fleet_mission = :missionId;";
                
        $count = self::$db->selectSingle($sql, [
            ':userId' => $userId,
            ':missionId' => $missionId
        ], 'count');
        
        return $count > 0;
    }
    
    /**
     * Pobiera wszystkie aktywne floty gracza
     * 
     * @param int $userId ID gracza
     * @return array Lista aktywnych flot gracza
     */
    public static function getFleetsByUserId(int $userId): array
    {
        $sql = "SELECT * 
                FROM %%FLEETS%% 
                WHERE fleet_owner = :userId 
                ORDER BY fleet_start_time ASC;";
                
        return self::$db->select($sql, [
            ':userId' => $userId
        ]);
    }
    
    /**
     * Oblicza maksymalną liczbę flot, które gracz może wysłać
     * 
     * @param array $user Dane gracza
     * @return int Maksymalna liczba flot
     */
    public static function getMaxFleetSlots(array $user): int
    {
        // Podstawowa liczba slotów na floty
        $maxSlots = 1;
        
        // Dodatkowe sloty z badania Technologia Komputerowa (108)
        if (isset($user['computer_tech'])) {
            $maxSlots += floor($user['computer_tech'] / 2);
        }
        
        // Dodatkowe sloty z oficera Floty
        if (isset($user['fleet_admiral']) && $user['fleet_admiral'] > 0) {
            $maxSlots += 2;
        }
        
        // Dodatkowe sloty z premii premium lub specjalnych wydarzeń
        if (isset($user['premium_fleet_slots']) && $user['premium_fleet_slots'] > 0) {
            $maxSlots += $user['premium_fleet_slots'];
        }
        
        return $maxSlots;
    }
    
    /**
     * Oblicza aktualną liczbę zajętych slotów flot przez gracza
     * 
     * @param int $userId ID gracza
     * @return int Liczba zajętych slotów
     */
    public static function getUsedFleetSlots(int $userId): int
    {
        $sql = "SELECT COUNT(*) as count 
                FROM %%FLEETS%% 
                WHERE fleet_owner = :userId 
                AND fleet_mission <> 10;"; // Misja 10 to atak rakietowy, który nie zajmuje slotu
                
        return (int)self::$db->selectSingle($sql, [
            ':userId' => $userId
        ], 'count');
    }
    
    /**
     * Sprawdza czy flota może zostać wysłana (dostępne sloty)
     * 
     * @param int $userId ID gracza
     * @param array $user Dane gracza
     * @return bool True jeśli flota może zostać wysłana
     */
    public static function canSendFleet(int $userId, array $user): bool
    {
        $maxSlots = self::getMaxFleetSlots($user);
        $usedSlots = self::getUsedFleetSlots($userId);
        
        return $usedSlots < $maxSlots;
    }
    
    /**
     * Czyszczenie starych lotów flot, które nie zostały przetworzone
     * Użyteczne do naprawy bazy danych po błędach
     * 
     * @param int $olderThan Usuń floty starsze niż X sekund
     * @return int Liczba usuniętych flot
     */
    public static function cleanupOldFleets(int $olderThan = 86400): int
    {
        $time = self::$time - $olderThan;
        
        // Pobierz stare floty
        $sql = "SELECT fleet_id 
                FROM %%FLEETS%% 
                WHERE fleet_end_time < :time;";
                
        $oldFleets = self::$db->select($sql, [
            ':time' => $time
        ]);
        
        $count = 0;
        
        // Usuń każdą starą flotę
        foreach ($oldFleets as $fleet) {
            self::removeFleet($fleet['fleet_id']);
            self::removeFleetEvent($fleet['fleet_id']);
            $count++;
        }
        
        return $count;
    }
}

<?php
<?php

declare(strict_types=1);

/**
 * Klasa zawierająca podstawowe funkcje pomocnicze dla misji flot
 * Klasy konkretnych misji dziedziczą po tej klasie
 */
abstract class MissionFunctions
{
    /**
     * Dane floty
     */
    protected array $_fleet = [];
    
    /**
     * Flaga oznaczająca zmianę stanu floty
     */
    protected bool $_hasChanged = false;

    /**
     * Obiekt języka
     */
    protected ?Language $language = null;
    
    /**
     * Pobiera obiekt językowy dla właściciela floty lub określonego języka
     * 
     * @param string|null $language Kod języka (null - domyślny)
     * @param int|null $userId ID użytkownika (null - użyj właściciela floty)
     * @return Language Obiekt języka
     */
    protected function getLanguage(?string $language = null, ?int $userId = null): Language
    {
        if ($this->language === null) {
            // Pobierz kod języka użytkownika, jeśli podano jego ID
            if ($userId !== null) {
                $sql = 'SELECT lang FROM %%USERS%% WHERE id = :userId;';
                $userLang = Database::get()->selectSingle($sql, [
                    ':userId' => $userId
                ], 'lang');
                
                $language = $userLang;
            }
            
            // Utwórz obiekt języka
            $this->language = new Language($language);
            
            // Załaduj odpowiednie pliki językowe
            $this->language->includeData(['L18N', 'FLEET', 'TECH']);
        }
        
        return $this->language;
    }
    
    /**
     * Zmienia stan floty
     * 
     * @param int $state Nowy stan floty
     */
    protected function setState(int $state): void
    {
        $this->_fleet['fleet_mess'] = $state;
        $this->_hasChanged = true;
    }
    
    /**
     * Aktualizuje pole w danych floty
     * 
     * @param string $key Klucz pola
     * @param mixed $value Nowa wartość
     */
    protected function UpdateFleet(string $key, $value): void
    {
        $this->_fleet[$key] = $value;
        $this->_hasChanged = true;
    }
    
    /**
     * Zapisuje zmiany we flocie do bazy danych
     */
    protected function SaveFleet(): void
    {
        if ($this->_hasChanged) {
            $params = [];
            $updateFields = [];
            
            // Lista pól, które mogą być zaktualizowane
            $allowedFields = [
                'fleet_start_time', 'fleet_end_time', 'fleet_stay_time',
                'fleet_target_owner', 'fleet_mess', 'fleet_end_stay',
                'fleet_resource_metal', 'fleet_resource_crystal', 
                'fleet_resource_deuterium', 'fleet_resource_darkmatter',
                'fleet_fuel'
            ];
            
            // Tworzenie listy pól do aktualizacji
            foreach ($allowedFields as $field) {
                if (isset($this->_fleet[$field])) {
                    $updateFields[] = $field . ' = :' . $field;
                    $params[':' . $field] = $this->_fleet[$field];
                }
            }
            
            if (!empty($updateFields)) {
                $params[':fleetId'] = $this->_fleet['fleet_id'];
                
                $sql = 'UPDATE %%FLEETS%% SET ' . implode(', ', $updateFields) . ' WHERE fleet_id = :fleetId;';
                Database::get()->update($sql, $params);
            }
            
            $this->_hasChanged = false;
        }
    }
    
    /**
     * Przywraca flotę na planetę startową
     * Dodaje statki i zasoby na planetę
     */
    protected function RestoreFleet(): void
    {
        global $resource;
        
        // Pobierz dane planety, na którą wraca flota
        $sql = 'SELECT * FROM %%PLANETS%% WHERE id = :planetId;';
        $targetPlanet = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_start_id']
        ]);
        
        if (empty($targetPlanet)) {
            // Jeśli planeta nie istnieje, znajdź inną planetę gracza
            $sql = 'SELECT * FROM %%PLANETS%% 
                    WHERE id_owner = :userId AND planet_type = 1 
                    ORDER BY id ASC LIMIT 1;';
            $targetPlanet = Database::get()->selectSingle($sql, [
                ':userId' => $this->_fleet['fleet_owner']
            ]);
            
            if (empty($targetPlanet)) {
                // Jeśli gracz nie ma żadnej planety, flota jest utracona
                return;
            }
        }
        
        // Przygotuj aktualizację planety
        $params = [
            ':planetId' => $targetPlanet['id']
        ];
        
        $updateFields = [];
        
        // Dodaj zasoby
        $resources = [
            'metal'      => 'fleet_resource_metal',
            'crystal'    => 'fleet_resource_crystal',
            'deuterium'  => 'fleet_resource_deuterium',
            'darkmatter' => 'fleet_resource_darkmatter'
        ];
        
        foreach ($resources as $resourceName => $fleetField) {
            if (isset($this->_fleet[$fleetField]) && $this->_fleet[$fleetField] > 0) {
                $updateFields[] = $resourceName . ' = ' . $resourceName . ' + :' . $resourceName;
                $params[':' . $resourceName] = $this->_fleet[$fleetField];
            }
        }
        
        // Dodaj statki
        $fleetArray = FleetFunctions::unserialize($this->_fleet['fleet_array']);
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            $shipName = $resource[$shipId];
            $updateFields[] = $shipName . ' = ' . $shipName . ' + :' . $shipName;
            $params[':' . $shipName] = $shipAmount;
        }
        
        if (!empty($updateFields)) {
            // Aktualizuj planetę
            $sql = 'UPDATE %%PLANETS%% SET ' . implode(', ', $updateFields) . ' WHERE id = :planetId;';
            Database::get()->update($sql, $params);
        }
        
        // Usuń flotę z bazy danych
        $sql = 'DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;';
        Database::get()->delete($sql, [
            ':fleetId' => $this->_fleet['fleet_id']
        ]);
    }
    
    /**
     * Pobiera nazwę misji dla danego typu
     * 
     * @param int $missionType Typ misji
     * @return string Nazwa misji
     */
    public static function getMissionName(int $missionType): string
    {
        $missionNames = [
            1  => 'attack',        // Atak
            2  => 'acs',           // Atak grupowy (ACS)
            3  => 'transport',     // Transport
            4  => 'deploy',        // Stacjonowanie
            5  => 'hold',          // Zatrzymanie
            6  => 'spy',           // Szpiegostwo
            7  => 'colonize',      // Kolonizacja
            8  => 'recycle',       // Recykling
            9  => 'destroy',       // Zniszczenie
            10 => 'missile',       // Atak rakietowy
            11 => 'dm_expedition', // Ekspedycja ciemnej materii
            15 => 'expedition'     // Ekspedycja
        ];
        
        return $missionNames[$missionType] ?? 'unknown';
    }
    
    /**
     * Zwraca kolor tekstu dla danego typu misji
     * 
     * @param int $missionType Typ misji
     * @return string Kolor w formacie HEX
     */
    public static function getMissionColor(int $missionType): string
    {
        $missionColors = [
            1  => '#FF0000', // Atak - czerwony
            2  => '#FF0000', // Atak grupowy - czerwony
            3  => '#00FF00', // Transport - zielony
            4  => '#0000FF', // Stacjonowanie - niebieski
            5  => '#0000FF', // Zatrzymanie - niebieski
            6  => '#FF9900', // Szpiegostwo - pomarańczowy
            7  => '#00FFFF', // Kolonizacja - cyjan
            8  => '#996600', // Recykling - brązowy
            9  => '#FF0000', // Zniszczenie - czerwony
            10 => '#FF0000', // Atak rakietowy - czerwony
            11 => '#9900FF', // Ekspedycja ciemnej materii - fioletowy
            15 => '#9900FF'  // Ekspedycja - fioletowy
        ];
        
        return $missionColors[$missionType] ?? '#FFFFFF';
    }
    
    /**
     * Dodaje wydarzenie do listy wydarzeń gracza
     * 
     * @param array $eventData Dane wydarzenia
     */
    protected function addEvent(array $eventData): void
    {
        $params = [
            ':owner'       => $eventData['owner'] ?? $this->_fleet['fleet_owner'],
            ':time'        => $eventData['time'] ?? $this->_fleet['fleet_start_time'],
            ':type'        => $eventData['type'] ?? 1, // 1 = flota
            ':status'      => $eventData['status'] ?? 0,
            ':title'       => $eventData['title'] ?? '',
            ':text'        => $eventData['text'] ?? '',
            ':universe'    => $eventData['universe'] ?? $this->_fleet['fleet_universe'],
            ':elementId'   => $eventData['elementId'] ?? $this->_fleet['fleet_id'],
            ':color'       => $eventData['color'] ?? self::getMissionColor($this->_fleet['fleet_mission'] ?? 0)
        ];
        
        $sql = 'INSERT INTO %%EVENTS%% 
                (owner, eventTime, eventType, eventStatus, eventTitle, eventText, universe, elementId, eventColor) 
                VALUES (:owner, :time, :type, :status, :title, :text, :universe, :elementId, :color);';
        
        Database::get()->insert($sql, $params);
    }
}
declare(strict_types=1);

/**
 * Klasa bazowa dla wszystkich misji flot
 * Zawiera podstawowe funkcje używane przez różne typy misji
 */
class MissionFunctions
{
    /**
     * Dane floty
     */
    protected array $_fleet = [];
    
    /**
     * Aktualizuje stan floty w bazie danych
     */
    protected function UpdateFleet(string $key, mixed $value): void
    {
        $this->_fleet[$key] = $value;
    }
    
    /**
     * Ustawia stan misji floty (w locie, powrót, koniec itp.)
     */
    protected function setState(int $state): void
    {
        $this->_fleet['fleet_mess'] = $state;
    }
    
    /**
     * Zapisuje zmiany stanu floty do bazy danych
     */
    protected function SaveFleet(): void
    {
        $params = [];
        $updateFields = [];
        
        // Przygotowanie zapytania SQL do aktualizacji floty
        foreach ($this->_fleet as $key => $value) {
            $updateFields[] = "`{$key}` = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        $sql = "UPDATE %%FLEETS%% SET " . implode(', ', $updateFields) . " WHERE fleet_id = :fleetId;";
        $params[':fleetId'] = $this->_fleet['fleet_id'];
        
        Database::get()->update($sql, $params);
    }
    
    /**
     * Przywraca flotę na planetę początkową
     * Dodaje surowce i statki z floty na planetę
     */
    protected function RestoreFleet(): void
    {
        global $resource;
        
        // Statki i surowce floty
        $fleetData = FleetFunctions::unserialize($this->_fleet['fleet_array']);
        $fleetRow = $this->_fleet;
        
        $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
        $targetPlanet = Database::get()->selectSingle($sql, [
            ':planetId' => $fleetRow['fleet_start_id']
        ]);
        
        if (empty($targetPlanet)) {
            // Jeśli planeta nie istnieje, utrata floty
            return;
        }
        
        // Aktualizacja statków na planecie
        $params = [];
        $shipFields = [];
        
        foreach ($fleetData as $shipId => $shipAmount) {
            $shipFields[] = "`{$resource[$shipId]}` = `{$resource[$shipId]}` + :{$resource[$shipId]}";
            $params[":{$resource[$shipId]}"] = $shipAmount;
        }
        
        // Aktualizacja surowców na planecie
        $resourceFields = [];
        $resourceList = ['metal', 'crystal', 'deuterium'];
        
        foreach ($resourceList as $resourceName) {
            $resourceFields[] = "`{$resourceName}` = `{$resourceName}` + :{$resourceName}";
            $params[":{$resourceName}"] = $fleetRow["fleet_resource_{$resourceName}"];
        }
        
        // Aktualizacja planety
        $sql = "UPDATE %%PLANETS%% SET " . 
               implode(', ', array_merge($shipFields, $resourceFields)) . 
               " WHERE id = :planetId;";
        
        $params[':planetId'] = $fleetRow['fleet_start_id'];
        
        Database::get()->update($sql, $params);
        
        // Usunięcie floty z rejestru
        $sql = "DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;";
        Database::get()->delete($sql, [
            ':fleetId' => $fleetRow['fleet_id']
        ]);
        
        // Usunięcie zadań floty z harmonogramu
        $sql = "DELETE FROM %%FLEET_EVENT%% WHERE fleetID = :fleetId;";
        Database::get()->delete($sql, [
            ':fleetId' => $fleetRow['fleet_id']
        ]);
    }
    
    /**
     * Pobiera obiekt języka dla danego użytkownika
     */
    protected function getLanguage(?string $language = null, ?int $userId = null): Language
    {
        if ($language !== null) {
            return new Language($language);
        }
        
        if ($userId !== null) {
            $sql = "SELECT lang FROM %%USERS%% WHERE id = :userId;";
            $userLang = Database::get()->selectSingle($sql, [
                ':userId' => $userId
            ], 'lang');
            
            if (!empty($userLang)) {
                return new Language($userLang);
            }
        }
        
        // Domyślny język z konfiguracji
        return new Language();
    }
    
    /**
     * Pobiera dane dotyczące walki (w przyszłych rozszerzeniach)
     */
    protected function getBattleData(int $combatId): ?array
    {
        $sql = "SELECT * FROM %%BATTLE_REPORTS%% WHERE combatId = :combatId;";
        $combatData = Database::get()->selectSingle($sql, [
            ':combatId' => $combatId
        ]);
        
        return $combatData ?: null;
    }
    
    /**
     * Oblicza czas przelotu floty między dwoma planetami
     */
    protected function calculateFlightTime(array $startPlanet, array $targetPlanet, int $fleetSpeed, int $gameSpeed): int
    {
        $distance = FleetFunctions::getDistance(
            $startPlanet['galaxy'], 
            $targetPlanet['galaxy'], 
            $startPlanet['system'], 
            $targetPlanet['system'], 
            $startPlanet['planet'], 
            $targetPlanet['planet']
        );
        
        $maxFleetSpeed = FleetFunctions::getFleetMaxSpeed($this->_fleet, 0);
        $duration = FleetFunctions::getDuration(
            $distance, 
            $fleetSpeed, 
            $maxFleetSpeed, 
            $gameSpeed
        );
        
        return $duration;
    }
    
    /**
     * Oblicza zużycie deuteru przez flotę
     */
    protected function calculateFleetConsumption(array $fleetArray, int $distance, int $flightTime, float $fleetSpeed): int
    {
        global $pricelist;
        
        $consumption = 0;
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            // Podstawowe zużycie paliwa dla danego typu statku
            $baseConsumption = $pricelist[$shipId]['consumption'] ?? 0;
            
            // Uwzględnienie prędkości floty w zużyciu paliwa
            $adjustedConsumption = $baseConsumption * $fleetSpeed / 10;
            
            // Całkowite zużycie paliwa dla danego typu statku
            $shipConsumption = $shipAmount * $adjustedConsumption * $distance / 35000 * (($flightTime / 3600) + 1);
            
            $consumption += $shipConsumption;
        }
        
        return (int)ceil($consumption);
    }
    
    /**
     * Ustala kolejność czyszczenia pola zniszczeń (recyklingu)
     */
    protected function getFleetRoleInDebrisCollection(int $fleetMission): int
    {
        // Ustalenie priorytetu zbierania złomu w zależności od misji
        $missionPriorities = [
            // ID misji => priorytet (niższy = wyższy priorytet)
            8 => 1,   // Recykling (najwyższy priorytet)
            3 => 2,   // Transport
            1 => 3,   // Atak
            5 => 4,   // Zatrzymanie
            9 => 5,   // Kolonizacja
            // inne misje mają niższy priorytet
        ];
        
        return $missionPriorities[$fleetMission] ?? 99;
    }
}

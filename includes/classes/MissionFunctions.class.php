<?php

declare(strict_types=1);

/**
 * Klasa bazowa zawierająca wspólne funkcjonalności dla wszystkich misji flot
 * Dostarcza podstawowe metody używane przez wszystkie typy misji
 */
abstract class MissionFunctions
{
    /**
     * Dane floty wykonującej misję
     * @var array
     */
    protected array $_fleet = [];
    
    /**
     * Status floty: HOLD, RETURN, END lub DESTROYED
     * @var int
     */
    protected int $_state = 0;
    
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
            $userLang = Database::get()->selectSingle($sql, ['userId' => $userId], 'lang');
            
            if (!empty($userLang)) {
                return new Language($userLang);
            }
        }
        
        return new Language();
    }
    
    /**
     * Ustawia nowy stan floty (HOLD, RETURN, END, DESTROYED)
     */
    protected function setState(int $state): void
    {
        $this->_state = $state;
    }
    
    /**
     * Aktualizuje określone pole w danych floty
     */
    protected function UpdateFleet(string $key, mixed $value): void
    {
        $this->_fleet[$key] = $value;
    }
    
    /**
     * Zapisuje zmiany w danych floty do bazy danych
     */
    protected function SaveFleet(): void
    {
        if (!empty($this->_state)) {
            switch ($this->_state) {
                case FLEET_RETURN:
                    // Flota wraca do punktu startowego
                    $this->returnFleet();
                    break;
                case FLEET_HOLD:
                    // Flota zatrzymuje się w miejscu docelowym
                    $this->holdFleet();
                    break;
                case FLEET_END:
                    // Flota kończy misję i zostaje rozformowana
                    $this->endFleet();
                    return;
                case FLEET_DESTROYED:
                    // Flota zostaje zniszczona
                    $this->destroyFleet();
                    return;
            }
        }
        
        // Aktualizacja danych floty w bazie danych
        $paramArray = [];
        $updateQuery = [];
        
        foreach ($this->_fleet as $key => $value) {
            if (in_array($key, ['fleet_id', 'fleet_mess'])) {
                continue;
            }
            
            if ($key === 'fleet_array') {
                $value = FleetFunctions::updateFleetArray($value, $this->_fleet['fleet_array']);
            }
            
            $updateQuery[] = '`' . $key . '` = :' . $key;
            $paramArray[':' . $key] = $value;
        }
        
        $sql = "UPDATE %%FLEETS%% SET " . implode(', ', $updateQuery) . " WHERE fleet_id = :fleetId;";
        $paramArray[':fleetId'] = $this->_fleet['fleet_id'];
        
        Database::get()->update($sql, $paramArray);
    }
    
    /**
     * Zmienia misję floty na powrót do bazy
     */
    protected function returnFleet(): void
    {
        // Zamiana punktów początkowych i końcowych floty
        $this->_fleet['fleet_target_owner'] = $this->_fleet['fleet_owner'];
        
        $this->_fleet['fleet_end_id']      = $this->_fleet['fleet_start_id'];
        $this->_fleet['fleet_end_type']    = $this->_fleet['fleet_start_type'];
        $this->_fleet['fleet_end_galaxy']  = $this->_fleet['fleet_start_galaxy'];
        $this->_fleet['fleet_end_system']  = $this->_fleet['fleet_start_system'];
        $this->_fleet['fleet_end_planet']  = $this->_fleet['fleet_start_planet'];
        
        // Obliczanie nowego czasu podróży i przybycia
        $fleetTime = FleetFunctions::GetMissionDuration(
            $this->_fleet['fleet_speed'],
            $this->_fleet,
            [
                'galaxy' => $this->_fleet['fleet_end_galaxy'],
                'system' => $this->_fleet['fleet_end_system'],
                'planet' => $this->_fleet['fleet_end_planet']
            ],
            TIMESTAMP
        );

        $this->_fleet['fleet_mission']    = 1;
        $this->_fleet['fleet_mess']       = FLEET_RETURN;
        $this->_fleet['fleet_start_time'] = TIMESTAMP;
        $this->_fleet['fleet_end_time']   = TIMESTAMP + $fleetTime;
        
        // Jeśli misja to ekspedycja, anulujemy czas postoju
        if ($this->_fleet['fleet_mission'] == 15) {
            $this->_fleet['fleet_end_stay'] = 0;
        }
        
        // Czyszczenie potencjalnie niebezpiecznych pól
        unset($this->_fleet['fleet_group']);
        unset($this->_fleet['fleet_busy']);
    }
    
    /**
     * Ustawia flotę w stan oczekiwania
     */
    protected function holdFleet(): void
    {
        $this->_fleet['fleet_mess'] = FLEET_HOLD;
    }
    
    /**
     * Kończy misję floty i przypisuje zasoby/statki do planety
     */
    protected function endFleet(): void
    {
        // Klonowanie danych floty do lokalnej zmiennej
        $fleetArray = FleetFunctions::unserialize($this->_fleet['fleet_array']);
        
        // Dodanie floty i zasobów do planety docelowej
        $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
        $targetPlanet = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_end_id']
        ]);
        
        if (!$targetPlanet) {
            // Jeśli planeta nie istnieje, flota zostaje zniszczona
            $this->destroyFleet();
            return;
        }
        
        // Dodanie statków do planety
        foreach ($fleetArray as $shipId => $shipAmount) {
            $targetPlanet[$resource[$shipId]] += $shipAmount;
        }
        
        // Dodanie zasobów do planety
        $targetPlanet['metal'] += $this->_fleet['fleet_resource_metal'];
        $targetPlanet['crystal'] += $this->_fleet['fleet_resource_crystal'];
        $targetPlanet['deuterium'] += $this->_fleet['fleet_resource_deuterium'];
        
        // Aktualizacja danych planety
        $sql = "UPDATE %%PLANETS%% SET 
                metal = :metal,
                crystal = :crystal,
                deuterium = :deuterium,
                metal_perhour = :metalPerHour,
                crystal_perhour = :crystalPerHour,
                deuterium_perhour = :deuteriumPerHour,
                metal_mine_porcent = :metalMinePorcent,
                crystal_mine_porcent = :crystalMinePorcent,
                deuterium_sintetizer_porcent = :deuteriumSintetizerPorcent,
                solar_plant_porcent = :solarPlantPorcent,
                fusion_plant_porcent = :fusionPlantPorcent,
                solar_satelit_porcent = :solarSatelitPorcent
                WHERE id = :planetId;";
        
        Database::get()->update($sql, [
            ':metal' => $targetPlanet['metal'],
            ':crystal' => $targetPlanet['crystal'],
            ':deuterium' => $targetPlanet['deuterium'],
            ':metalPerHour' => $targetPlanet['metal_perhour'],
            ':crystalPerHour' => $targetPlanet['crystal_perhour'],
            ':deuteriumPerHour' => $targetPlanet['deuterium_perhour'],
            ':metalMinePorcent' => $targetPlanet['metal_mine_porcent'],
            ':crystalMinePorcent' => $targetPlanet['crystal_mine_porcent'],
            ':deuteriumSintetizerPorcent' => $targetPlanet['deuterium_sintetizer_porcent'],
            ':solarPlantPorcent' => $targetPlanet['solar_plant_porcent'],
            ':fusionPlantPorcent' => $targetPlanet['fusion_plant_porcent'],
            ':solarSatelitPorcent' => $targetPlanet['solar_satelit_porcent'],
            ':planetId' => $this->_fleet['fleet_end_id']
        ]);
        
        // Aktualizacja statków na planecie
        $params = [];
        $updateFields = [];
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            $key = $resource[$shipId];
            $updateFields[] = "`$key` = :$key";
            $params[":$key"] = $targetPlanet[$key];
        }
        
        if (!empty($updateFields)) {
            $sql = "UPDATE %%PLANETS%% SET " . implode(', ', $updateFields) . " WHERE id = :planetId;";
            $params[':planetId'] = $this->_fleet['fleet_end_id'];
            Database::get()->update($sql, $params);
        }
        
        // Usunięcie floty z bazy danych
        $sql = "DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;";
        Database::get()->delete($sql, [':fleetId' => $this->_fleet['fleet_id']]);
    }
    
    /**
     * Zniszczenie floty - usunięcie z bazy danych bez przypisania zasobów
     */
    protected function destroyFleet(): void
    {
        $sql = "DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;";
        Database::get()->delete($sql, [':fleetId' => $this->_fleet['fleet_id']]);
    }
    
    /**
     * Przywraca flotę na jej planetę macierzystą (używane przy powrocie z misji)
     */
    protected function RestoreFleet(): void
    {
        global $resource;
        
        $fleetArray = FleetFunctions::unserialize($this->_fleet['fleet_array']);
        
        if (empty($fleetArray)) {
            // Jeśli flota została zniszczona lub jest pusta, tylko ją usuń
            $this->destroyFleet();
            return;
        }
        
        // Pobierz dane planety macierzystej
        $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
        $targetPlanet = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_end_id']
        ]);
        
        if (!$targetPlanet) {
            // Jeśli planeta nie istnieje, flota zostaje zniszczona
            $this->destroyFleet();
            return;
        }
        
        // Dodanie statków do planety
        $shipUpdates = [];
        $params = [':planetId' => $this->_fleet['fleet_end_id']];
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            $fieldName = $resource[$shipId];
            $targetPlanet[$fieldName] += $shipAmount;
            $shipUpdates[] = "`$fieldName` = :$fieldName";
            $params[":$fieldName"] = $targetPlanet[$fieldName];
        }
        
        // Dodanie zasobów do planety
        $targetPlanet['metal'] += $this->_fleet['fleet_resource_metal'];
        $targetPlanet['crystal'] += $this->_fleet['fleet_resource_crystal'];
        $targetPlanet['deuterium'] += $this->_fleet['fleet_resource_deuterium'];
        
        // Aktualizacja zasobów planety
        $sql = "UPDATE %%PLANETS%% SET 
                metal = :metal,
                crystal = :crystal,
                deuterium = :deuterium
                WHERE id = :planetId;";
        
        Database::get()->update($sql, [
            ':metal' => $targetPlanet['metal'],
            ':crystal' => $targetPlanet['crystal'],
            ':deuterium' => $targetPlanet['deuterium'],
            ':planetId' => $this->_fleet['fleet_end_id']
        ]);
        
        // Aktualizacja statków na planecie
        if (!empty($shipUpdates)) {
            $sql = "UPDATE %%PLANETS%% SET " . implode(', ', $shipUpdates) . " WHERE id = :planetId;";
            Database::get()->update($sql, $params);
        }
        
        // Usunięcie floty z bazy danych
        $sql = "DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;";
        Database::get()->delete($sql, [':fleetId' => $this->_fleet['fleet_id']]);
    }
}

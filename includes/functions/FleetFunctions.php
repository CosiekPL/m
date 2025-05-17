<?php

declare(strict_types=1);

/**
 * Klasa zawierająca funkcje pomocnicze dla systemu flot
 */
class FleetFunctions
{
    /**
     * Serializuje tablicę floty do formatu przechowywania w bazie danych
     * 
     * @param array $fleetArray Tablica floty [ID_statku => ilość]
     * @return string Zserializowana tablica
     */
    public static function serialize(array $fleetArray): string
    {
        $serialized = [];
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            // Pomijanie statków z ilością 0
            if ($shipAmount <= 0) {
                continue;
            }
            
            $serialized[] = $shipId . ',' . $shipAmount;
        }
        
        return implode(';', $serialized);
    }
    
    /**
     * Deserializuje tablicę floty z formatu przechowywanego w bazie danych
     * 
     * @param string $fleetString Zserializowana tablica floty
     * @return array Tablica floty [ID_statku => ilość]
     */
    public static function unserialize(string $fleetString): array
    {
        $fleetArray = [];
        
        if (empty($fleetString)) {
            return $fleetArray;
        }
        
        $items = explode(';', $fleetString);
        
        foreach ($items as $item) {
            list($shipId, $shipAmount) = explode(',', $item);
            $fleetArray[(int)$shipId] = (int)$shipAmount;
        }
        
        return $fleetArray;
    }
    
    /**
     * Oblicza czas przelotu floty
     * 
     * @param int $distance Odległość między planetami
     * @param int $speedPercent Procent prędkości (1-100)
     * @param int $maxSpeed Maksymalna prędkość floty
     * @param int $gameSpeed Prędkość gry
     * @return int Czas przelotu w sekundach
     */
    public static function getDuration(int $distance, int $speedPercent, int $maxSpeed, int $gameSpeed): int
    {
        // Zabezpieczenie przed dzieleniem przez zero
        if ($maxSpeed <= 0 || $gameSpeed <= 0) {
            return 0;
        }
        
        // Przeliczenie procentu prędkości (10% to minimum)
        $speedFactor = max(0.1, $speedPercent / 100);
        
        // Podstawowy wzór na czas przelotu
        $duration = round((35000 / $speedFactor * sqrt($distance * 10 / $maxSpeed) + 10) / $gameSpeed);
        
        // Minimalny czas przelotu to 5 sekund
        return max(5, $duration);
    }
    
    /**
     * Oblicza odległość między dwiema planetami
     * 
     * @param int $startGalaxy Galaktyka początkowa
     * @param int $targetGalaxy Galaktyka docelowa
     * @param int $startSystem System początkowy
     * @param int $targetSystem System docelowy
     * @param int $startPlanet Planeta początkowa
     * @param int $targetPlanet Planeta docelowa
     * @return int Odległość
     */
    public static function getDistance(
        int $startGalaxy, 
        int $targetGalaxy, 
        int $startSystem, 
        int $targetSystem, 
        int $startPlanet, 
        int $targetPlanet
    ): int {
        $distance = 0;
        
        if ($startGalaxy != $targetGalaxy) {
            // Różne galaktyki
            $distance = abs($startGalaxy - $targetGalaxy) * 20000;
        } elseif ($startSystem != $targetSystem) {
            // Ta sama galaktyka, różne systemy
            $distance = abs($startSystem - $targetSystem) * 95 + 2700;
        } elseif ($startPlanet != $targetPlanet) {
            // Ta sama galaktyka i system, różne planety
            $distance = abs($startPlanet - $targetPlanet) * 5 + 1000;
        } else {
            // Ta sama lokalizacja (np. księżyc i planeta) - minimalna odległość
            $distance = 5;
        }
        
        return $distance;
    }
    
    /**
     * Oblicza maksymalną prędkość floty
     * 
     * @param array $fleetData Dane floty
     * @param int $fleetSpeed Procent prędkości (0-10)
     * @return int Maksymalna prędkość floty
     */
    public static function getFleetMaxSpeed(array $fleetData, int $fleetSpeed = 0): int
    {
        global $pricelist;
        
        // Jeśli podano prędkość floty, użyj jej
        if ($fleetSpeed > 0) {
            $fleetSpeed = $fleetSpeed / 10;
        } else {
            // W przeciwnym razie użyj prędkości z danych floty
            $gameSpeed = Config::get()->fleet_speed / 2500;
            $fleetSpeed = $fleetData['fleet_speed'] / 10 * $gameSpeed;
        }
        
        // Jeśli dane floty zawierają już obliczoną maksymalną prędkość, użyj jej
        if (isset($fleetData['fleet_max_speed']) && $fleetData['fleet_max_speed'] > 0) {
            return $fleetData['fleet_max_speed'];
        }
        
        // W przeciwnym razie oblicz najwolniejszy statek
        $fleetArray = self::unserialize($fleetData['fleet_array']);
        $minSpeed = PHP_INT_MAX;
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            if ($shipAmount <= 0) {
                continue;
            }
            
            // Prędkość bazowa statku
            $baseSpeed = $pricelist[$shipId]['speed'] ?? 0;
            
            // Uwzględnij technologie napędu
            $techSpeed = self::getShipSpeedWithTech($shipId, $fleetData);
            
            // Wybierz najniższą prędkość
            $minSpeed = min($minSpeed, $techSpeed);
        }
        
        // Jeśli nie znaleziono statków, ustaw domyślną prędkość
        if ($minSpeed === PHP_INT_MAX) {
            $minSpeed = 1;
        }
        
        return (int)($minSpeed * $fleetSpeed);
    }
    
    /**
     * Oblicza prędkość statku z uwzględnieniem technologii
     * 
     * @param int $shipId ID statku
     * @param array $fleetData Dane floty zawierające informacje o technologiach gracza
     * @return int Prędkość statku z technologiami
     */
    private static function getShipSpeedWithTech(int $shipId, array $fleetData): int
    {
        global $pricelist;
        
        // Pobierz bazową prędkość statku
        $baseSpeed = $pricelist[$shipId]['speed'] ?? 0;
        
        // Pobierz wymagany napęd dla statku
        $engine = $pricelist[$shipId]['tech'] ?? 0;
        
        // Współczynniki dla różnych typów napędów
        $factor = 0;
        
        switch ($engine) {
            case 1: // Napęd spalinowy
                $factor = 0.1 * ($fleetData['fleet_combustion_tech'] ?? 0);
                break;
            case 2: // Napęd impulsowy
                $factor = 0.2 * ($fleetData['fleet_impulse_tech'] ?? 0);
                break;
            case 3: // Napęd nadprzestrzenny
                $factor = 0.3 * ($fleetData['fleet_hyperspace_tech'] ?? 0);
                break;
        }
        
        // Oblicz prędkość z uwzględnieniem technologii
        return (int)($baseSpeed * (1 + $factor));
    }
    
    /**
     * Oblicza zużycie deuteru przez flotę
     * 
     * @param array $fleetArray Tablica floty [ID_statku => ilość]
     * @param int $distance Odległość
     * @param int $duration Czas lotu w sekundach
     * @param float $fleetSpeed Procent prędkości (0.1-1.0)
     * @return int Zużycie deuteru
     */
    public static function getFleetConsumption(array $fleetArray, int $distance, int $duration, float $fleetSpeed): int
    {
        global $pricelist;
        
        $consumption = 0;
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            // Podstawowe zużycie paliwa dla danego typu statku
            $baseConsumption = $pricelist[$shipId]['consumption'] ?? 0;
            
            // Uwzględnienie prędkości floty w zużyciu paliwa
            $adjustedConsumption = $baseConsumption * $fleetSpeed;
            
            // Całkowite zużycie paliwa dla danego typu statku
            $shipConsumption = $shipAmount * $adjustedConsumption * $distance / 35000 * (($duration / 3600) + 1);
            
            $consumption += $shipConsumption;
        }
        
        return (int)ceil($consumption);
    }
    
    /**
     * Sprawdza czy na planecie jest wystarczająca ilość statków do utworzenia floty
     * 
     * @param array $planet Dane planety
     * @param array $fleetArray Tablica floty [ID_statku => ilość]
     * @return bool True jeśli jest wystarczająca ilość statków
     */
    public static function checkFleetShips(array $planet, array $fleetArray): bool
    {
        global $resource;
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            // Sprawdź czy na planecie jest wystarczająca ilość statków
            if ($planet[$resource[$shipId]] < $shipAmount) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Oblicza pojemność ładunkową floty
     * 
     * @param array $fleetArray Tablica floty [ID_statku => ilość]
     * @param array $userFactors Współczynniki gracza (np. bonusy z badań)
     * @return int Pojemność ładunkowa
     */
    public static function getFleetCapacity(array $fleetArray, array $userFactors = []): int
    {
        global $pricelist;
        
        $capacity = 0;
        $storageBonus = 1 + ($userFactors['ShipStorage'] ?? 0);
        
        foreach ($fleetArray as $shipId => $shipAmount) {
            // Dodaj pojemność każdego statku
            $capacity += ($pricelist[$shipId]['capacity'] ?? 0) * $shipAmount;
        }
        
        // Uwzględnij bonus do pojemności statków
        return (int)floor($capacity * $storageBonus);
    }
    
    /**
     * Pobiera ładunek floty (surowce)
     * 
     * @param array $fleetData Dane floty
     * @return array Tablica z ilością poszczególnych surowców
     */
    public static function getFleetCargo(array $fleetData): array
    {
        $resources = ['metal', 'crystal', 'deuterium', 'darkmatter'];
        $cargo = [];
        
        foreach ($resources as $resource) {
            $cargo[$resource] = $fleetData['fleet_resource_' . $resource] ?? 0;
        }
        
        return $cargo;
    }
    
    /**
     * Sprawdza czy flota ma wystarczającą pojemność dla danego ładunku
     * 
     * @param array $fleetArray Tablica floty [ID_statku => ilość]
     * @param array $cargo Ładunek [typ_surowca => ilość]
     * @param array $userFactors Współczynniki gracza (np. bonusy z badań)
     * @return bool True jeśli flota ma wystarczającą pojemność
     */
    public static function checkFleetCapacity(array $fleetArray, array $cargo, array $userFactors = []): bool
    {
        $capacity = self::getFleetCapacity($fleetArray, $userFactors);
        $cargoSum = array_sum($cargo);
        
        return $capacity >= $cargoSum;
    }
    
    /**
     * Oblicza czas postoju floty dla danej misji
     * 
     * @param int $missionId ID misji
     * @param int $stayTime Czas postoju wybrany przez gracza (0 = domyślny)
     * @return int Czas postoju w sekundach
     */
    public static function getFleetStayTime(int $missionId, int $stayTime = 0): int
    {
        // Jeśli podano czas postoju, użyj go
        if ($stayTime > 0) {
            return $stayTime;
        }
        
        // Domyślne czasy postoju dla różnych misji
        $defaultStayTimes = [
            1 => 0,      // Atak - brak postoju
            2 => 0,      // Atak grupowy - brak postoju
            3 => 0,      // Transport - brak postoju
            4 => 3600,   // Stacjonowanie - 1 godzina
            5 => 3600,   // Zatrzymanie - 1 godzina
            6 => 0,      // Szpiegostwo - brak postoju
            7 => 0,      // Kolonizacja - brak postoju
            8 => 0,      // Recykling - brak postoju
            9 => 0,      // Zniszczenie - brak postoju
            10 => 0,     // Atak rakietowy - brak postoju
            11 => 0,     // Ekspedycja ciemnej materii - brak postoju
            15 => 10800  // Ekspedycja - 3 godziny
        ];
        
        return $defaultStayTimes[$missionId] ?? 0;
    }
    
    /**
     * Pobiera dostępne misje dla danej floty i celu
     * 
     * @param array $user Dane gracza
     * @param array $fleetArray Tablica floty [ID_statku => ilość]
     * @param array $targetPlanet Dane planety docelowej
     * @return array Lista dostępnych misji dla floty
     */
    public static function getAvailableMissions(array $user, array $fleetArray, array $targetPlanet): array
    {
        $availableMissions = [];
        
        // Podstawowe misje zawsze dostępne
        $basicMissions = [3]; // Transport
        
        // Misje specjalne wymagające określonych warunków
        $specialMissions = [
            1 => 'canAttack',        // Atak
            2 => 'canGroupAttack',   // Atak grupowy
            4 => 'canStay',          // Stacjonowanie
            5 => 'canHold',          // Zatrzymanie
            6 => 'canSpy',           // Szpiegostwo
            7 => 'canColonize',      // Kolonizacja
            8 => 'canRecycle',       // Recykling
            9 => 'canDestruct',      // Zniszczenie
            10 => 'canMissileAttack', // Atak rakietowy
            15 => 'canExpedition'    // Ekspedycja
        ];
        
        // Dodaj podstawowe misje
        foreach ($basicMissions as $missionId) {
            $availableMissions[] = $missionId;
        }
        
        // Sprawdź warunki dla misji specjalnych
        foreach ($specialMissions as $missionId => $checkMethod) {
            if (method_exists('FleetFunctions', $checkMethod) && 
                self::$checkMethod($user, $fleetArray, $targetPlanet)) {
                $availableMissions[] = $missionId;
            }
        }
        
        return $availableMissions;
    }
    
    /**
     * Sprawdza czy flota może przeprowadzić atak
     */
    public static function canAttack(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Sprawdź czy flota zawiera statki bojowe
        $hasCombatShips = false;
        $combatShips = [204, 205, 206, 207, 210, 211, 213, 214, 215, 218]; // IDs statków bojowych
        
        foreach ($combatShips as $shipId) {
            if (isset($fleetArray[$shipId]) && $fleetArray[$shipId] > 0) {
                $hasCombatShips = true;
                break;
            }
        }
        
        // Nie można atakować własnych planet
        if ($targetPlanet['id_owner'] == $user['id']) {
            return false;
        }
        
        // Sprawdź czy cel nie jest chroniony (dla nowych graczy lub w trybie wakacji)
        if ($targetPlanet['id_owner'] > 0) {
            $sql = "SELECT * FROM %%USERS%% WHERE id = :userId;";
            $targetUser = Database::get()->selectSingle($sql, [
                ':userId' => $targetPlanet['id_owner']
            ]);
            
            // Cel jest w trybie wakacji
            if (isset($targetUser['vacation']) && $targetUser['vacation'] > 0) {
                return false;
            }
            
            // Cel jest nowym graczem (ochrona)
            if (isset($targetUser['onlinetime']) && 
                (TIMESTAMP - $targetUser['onlinetime'] < 7 * 24 * 3600) && // 7 dni
                $targetUser['total_points'] < 5000) {
                return false;
            }
        }
        
        return $hasCombatShips;
    }
    
    /**
     * Sprawdza czy flota może przeprowadzić atak grupowy
     */
    public static function canGroupAttack(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Atak grupowy wymaga tych samych warunków co zwykły atak
        if (!self::canAttack($user, $fleetArray, $targetPlanet)) {
            return false;
        }
        
        // Atak grupowy wymaga odpowiedniego poziomu technologii komputerowej
        $requiredComputerTech = 8;
        return isset($user['computer_tech']) && $user['computer_tech'] >= $requiredComputerTech;
    }
    
    /**
     * Sprawdza czy flota może stacjonować na planecie
     */
    public static function canStay(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Można stacjonować tylko na własnych planetach lub planetach sojuszników
        return $targetPlanet['id_owner'] == $user['id'] || 
               self::isAlly($user, $targetPlanet['id_owner']);
    }
    
    /**
     * Sprawdza czy flota może zatrzymać się na planecie
     */
    public static function canHold(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Zatrzymanie ma podobne warunki jak stacjonowanie
        return self::canStay($user, $fleetArray, $targetPlanet);
    }
    
    /**
     * Sprawdza czy flota może przeprowadzić szpiegostwo
     */
    public static function canSpy(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Szpiegostwo wymaga sond szpiegowskich
        return isset($fleetArray[210]) && $fleetArray[210] > 0;
    }
    
    /**
     * Sprawdza czy flota może kolonizować
     */
    public static function canColonize(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Kolonizacja wymaga statku kolonizacyjnego
        if (!isset($fleetArray[208]) || $fleetArray[208] <= 0) {
            return false;
        }
        
        // Kolonizacja jest możliwa tylko na niezajętych planetach
        if ($targetPlanet['id_owner'] > 0) {
            return false;
        }
        
        // Sprawdź maksymalną liczbę planet gracza
        $maxPlanets = 9 + floor(min(($user['astrophysics'] ?? 0) / 2, 10));
        
        // Pobierz aktualną liczbę planet gracza
        $sql = "SELECT COUNT(*) as count FROM %%PLANETS%% WHERE id_owner = :userId AND planet_type = 1;";
        $currentPlanets = Database::get()->selectSingle($sql, [
            ':userId' => $user['id']
        ], 'count');
        
        return $currentPlanets < $maxPlanets;
    }
    
    /**
     * Sprawdza czy flota może przeprowadzić recykling
     */
    public static function canRecycle(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Recykling wymaga recyklerów
        if ((!isset($fleetArray[209]) || $fleetArray[209] <= 0) && 
            (!isset($fleetArray[219]) || $fleetArray[219] <= 0)) {
            return false;
        }
        
        // Recykling jest możliwy tylko na polach zniszczeń
        return $targetPlanet['der_metal'] > 0 || $targetPlanet['der_crystal'] > 0;
    }
    
    /**
     * Sprawdza czy flota może przeprowadzić zniszczenie
     */
    public static function canDestruct(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Zniszczenie wymaga Gwiazdy Śmierci
        if (!isset($fleetArray[214]) || $fleetArray[214] <= 0) {
            return false;
        }
        
        // Zniszczenie jest możliwe tylko na księżycach
        if ($targetPlanet['planet_type'] != 3) {
            return false;
        }
        
        // Nie można niszczyć własnych księżyców
        return $targetPlanet['id_owner'] != $user['id'];
    }
    
    /**
     * Sprawdza czy można przeprowadzić atak rakietowy
     */
    public static function canMissileAttack(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Atak rakietowy jest specjalną misją bez floty, używa międzyplanetarnych rakiet
        return false;
    }
    
    /**
     * Sprawdza czy flota może przeprowadzić ekspedycję
     */
    public static function canExpedition(array $user, array $fleetArray, array $targetPlanet): bool
    {
        // Ekspedycja wymaga odpowiedniego poziomu technologii astrofizyki
        if (!isset($user['astrophysics']) || $user['astrophysics'] < 1) {
            return false;
        }
        
        // Ekspedycja jest możliwa tylko w pozycji 16 (puste pole w systemie)
        if ($targetPlanet['planet'] != 16) {
            return false;
        }
        
        // Oblicz maksymalną liczbę ekspedycji na podstawie poziomu astrofizyki
        $maxExpeditions = floor(sqrt($user['astrophysics']));
        
        // Pobierz aktualną liczbę ekspedycji gracza
        $sql = "SELECT COUNT(*) as count FROM %%FLEETS%% 
                WHERE fleet_owner = :userId AND fleet_mission = 15;";
        $currentExpeditions = Database::get()->selectSingle($sql, [
            ':userId' => $user['id']
        ], 'count');
        
        return $currentExpeditions < $maxExpeditions;
    }
    
    /**
     * Sprawdza czy gracze są sojusznikami
     */
    private static function isAlly(array $user, int $targetOwnerId): bool
    {
        // Jeśli gracz nie ma sojuszu
        if (empty($user['ally_id'])) {
            return false;
        }
        
        // Pobierz informacje o celu
        $sql = "SELECT ally_id FROM %%USERS%% WHERE id = :userId;";
        $targetUser = Database::get()->selectSingle($sql, [
            ':userId' => $targetOwnerId
        ]);
        
        // Jeśli cel nie ma sojuszu
        if (empty($targetUser) || empty($targetUser['ally_id'])) {
            return false;
        }
        
        // Sprawdź czy sojusze są takie same
        if ($user['ally_id'] == $targetUser['ally_id']) {
            return true;
        }
        
        // Sprawdź czy sojusze mają pakt o nieagresji
        $sql = "SELECT COUNT(*) as count FROM %%ALLIANCE_PACTS%% 
                WHERE (alliance_id_1 = :ally1 AND alliance_id_2 = :ally2) 
                   OR (alliance_id_1 = :ally2 AND alliance_id_2 = :ally1);";
        $pactsCount = Database::get()->selectSingle($sql, [
            ':ally1' => $user['ally_id'],
            ':ally2' => $targetUser['ally_id']
        ], 'count');
        
        return $pactsCount > 0;
    }
    
    /**
     * Generuje link do adresu planety
     * 
     * @param array $fleet Dane floty
     * @param string $target Cel ('start' lub 'end')
     * @return string HTML link do adresu
     */
    public static function getPlanetLink(array $fleet, string $target = 'start'): string
    {
        $prefix = 'fleet_' . $target . '_';
        
        // Pobranie współrzędnych
        $galaxy = $fleet[$prefix . 'galaxy'];
        $system = $fleet[$prefix . 'system'];
        $planet = $fleet[$prefix . 'planet'];
        $type = $fleet[$prefix . 'type'];
        
        // Tekst typu (1 = planeta, 2 = pole zniszczeń, 3 = księżyc)
        $typeText = '';
        switch ($type) {
            case 1:
                $typeText = '(P)';
                break;
            case 2:
                $typeText = '(D)';
                break;
            case 3:
                $typeText = '(K)';
                break;
        }
        
        // Link do galaktyki
        $url = "game.php?page=galaxy&galaxy={$galaxy}&system={$system}&planet={$planet}";
        
        // Format: [G:S:P]
        $address = "[{$galaxy}:{$system}:{$planet}] {$typeText}";
        
        return "<a href=\"{$url}\">{$address}</a>";
    }
}

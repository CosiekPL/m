<?php

declare(strict_types=1);

/**
 * Klasa misji recyklingu (zbierania złomu)
 * Obsługuje zbieranie zasobów z pól zniszczeń
 */
class MissionCaseRecycling extends MissionFunctions implements Mission
{
    /**
     * Konstruktor klasy
     * 
     * @param array $Fleet Dane floty wykonującej misję recyklingu
     */
    public function __construct(array $Fleet)
    {
        $this->_fleet = $Fleet;
    }
    
    /**
     * Metoda wywoływana gdy flota dociera do celu (pola zniszczeń)
     * Zbiera metal i kryształ z pola zniszczeń w zależności od pojemności statków
     */
    public function TargetEvent(): void
    {    
        global $pricelist, $resource;
        
        // Identyfikatory zasobów, które można przewozić i zbierać
        $resourceIDs = [901, 902, 903, 921]; // metal, kryształ, deuter, ciemna materia
        $debrisIDs   = [901, 902];           // tylko metal i kryształ można zbierać z pola zniszczeń
        $resQuery    = [];
        $collectQuery = [];
        
        // Inicjalizacja tablicy zebranych surowców
        $collectedGoods = [];
        foreach ($debrisIDs as $debrisID) {
            $collectedGoods[$debrisID] = 0;
            $resQuery[] = 'der_' . $resource[$debrisID];
        }

        // Pobieranie informacji o polu zniszczeń
        $sql = 'SELECT ' . implode(',', $resQuery) . ', (' . implode(' + ', $resQuery) . ') as total
                FROM %%PLANETS%% WHERE id = :planetId';

        $targetData = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_end_id']
        ]);

        // Jeśli istnieją surowce do zebrania
        if (!empty($targetData['total'])) {
            // Pobieramy dane właściciela floty do obliczenia bonusów
            $sql = 'SELECT * FROM %%USERS%% WHERE id = :userId;';
            $targetUser = Database::get()->selectSingle($sql, [
                ':userId' => $this->_fleet['fleet_owner']
            ]);

            // Obliczamy współczynniki gracza (bonusy z badań, oficerów itp.)
            $targetUserFactors = getFactors($targetUser);
            $shipStorageFactor = 1 + $targetUserFactors['ShipStorage'];
        
            // Pobieramy skład floty 
            $fleetData = FleetFunctions::unserialize($this->_fleet['fleet_array']);

            // Obliczamy pojemność recyklerów i pozostałych statków
            $recyclerStorage = 0;
            $otherFleetStorage = 0;

            foreach ($fleetData as $shipId => $shipAmount) {
                if ($shipId == 209 || $shipId == 219) {
                    // Recyklery i Super-recyklery
                    $recyclerStorage += $pricelist[$shipId]['capacity'] * $shipAmount;
                } else {
                    // Inne statki
                    $otherFleetStorage += $pricelist[$shipId]['capacity'] * $shipAmount;
                }
            }
            
            // Uwzględniamy bonus do pojemności statków
            $recyclerStorage *= $shipStorageFactor;
            $otherFleetStorage *= $shipStorageFactor;

            // Obliczamy ilość przewożonych już zasobów
            $incomingGoods = 0;
            foreach ($resourceIDs as $resourceID) {
                $incomingGoods += $this->_fleet['fleet_resource_' . $resource[$resourceID]];
            }

            // Całkowita dostępna pojemność to pojemność recyklerów
            // plus niewykorzystana pojemność innych statków
            $totalStorage = $recyclerStorage + min(0, $otherFleetStorage - $incomingGoods);

            $param = [
                ':planetId' => $this->_fleet['fleet_end_id']
            ];

            // Obliczamy ile procent surowców możemy zebrać (max 100%)
            $collectFactor = min(1, $totalStorage / $targetData['total']);
            
            // Zbieramy surowce
            foreach ($debrisIDs as $debrisID) {
                $fleetColName = 'fleet_resource_' . $resource[$debrisID];
                $debrisColName = 'der_' . $resource[$debrisID];

                // Obliczamy ilość zebranych surowców
                $collectedGoods[$debrisID] = ceil($targetData[$debrisColName] * $collectFactor);
                
                // Przygotowujemy zapytanie aktualizujące pole zniszczeń
                $collectQuery[] = $debrisColName . ' = GREATEST(0, ' . $debrisColName . ' - :' . $resource[$debrisID] . ')';
                $param[':' . $resource[$debrisID]] = $collectedGoods[$debrisID];

                // Aktualizujemy ilość zasobów we flocie
                $this->UpdateFleet($fleetColName, $this->_fleet[$fleetColName] + $collectedGoods[$debrisID]);
            }

            // Aktualizujemy pole zniszczeń (usuwamy zebrane surowce)
            $sql = 'UPDATE %%PLANETS%% SET ' . implode(',', $collectQuery) . ' WHERE id = :planetId;';
            Database::get()->update($sql, $param);
        }
        
        // Pobieramy język właściciela floty
        $LNG = $this->getLanguage(null, $this->_fleet['fleet_owner']);
        
        // Przygotowujemy wiadomość o zebranych surowcach
        $message = sprintf(
            $LNG['sys_recy_gotten'], 
            pretty_number($collectedGoods[901] ?? 0), 
            $LNG['tech'][901],
            pretty_number($collectedGoods[902] ?? 0), 
            $LNG['tech'][902]
        );

        // Wysyłamy wiadomość do właściciela floty
        PlayerUtil::sendMessage(
            $this->_fleet['fleet_owner'],        // ID odbiorcy (właściciel floty)
            0,                                  // ID nadawcy (0 = systemowa)
            $LNG['sys_mess_tower'],             // Tytuł ogólny
            5,                                  // Typ wiadomości (5 = recykling)
            $LNG['sys_recy_report'],            // Temat
            $message,                           // Treść
            $this->_fleet['fleet_start_time'],  // Czas wysłania
            null,                               // Dodatkowe dane 
            1,                                  // Tryb
            $this->_fleet['fleet_universe']     // ID wszechświata
        );

        // Ustawiamy flotę w stan powrotu
        $this->setState(FLEET_RETURN);
        $this->SaveFleet();
    }
    
    /**
     * Metoda wywoływana gdy flota kończy pobyt na celu
     * Dla misji recyklingu nie jest wymagana, ponieważ nie ma czasu postoju
     */
    public function EndStayEvent(): void
    {
        // Pusta implementacja - misja recyklingu nie ma czasu postoju
        // Flota od razu zbiera surowce i wraca (w TargetEvent)
        return;
    }
    
    /**
     * Metoda wywoływana gdy flota wraca do bazy
     * Wyświetla wiadomość o powrocie floty z zebranymi surowcami
     */
    public function ReturnEvent(): void
    {
        // Pobieramy język właściciela floty
        $LNG = $this->getLanguage(null, $this->_fleet['fleet_owner']);

        // Pobieramy nazwę planety startowej
        $sql = 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
        $planetName = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_start_id'],
        ], 'name');
    
        // Przygotowujemy wiadomość o powrocie floty
        $message = sprintf(
            $LNG['sys_tran_mess_owner'],
            $planetName,                                         // Nazwa planety
            GetStartAddressLink($this->_fleet, ''),             // Link do adresu
            pretty_number($this->_fleet['fleet_resource_metal']), // Ilość metalu
            $LNG['tech'][901],                                   // Nazwa "metal"
            pretty_number($this->_fleet['fleet_resource_crystal']), // Ilość kryształu
            $LNG['tech'][902],                                   // Nazwa "kryształ"
            pretty_number($this->_fleet['fleet_resource_deuterium']), // Ilość deuteru
            $LNG['tech'][903]                                    // Nazwa "deuter"
        );

        // Wysyłamy wiadomość o powrocie floty
        PlayerUtil::sendMessage(
            $this->_fleet['fleet_owner'],      // ID odbiorcy (właściciel floty)
            0,                                // ID nadawcy (0 = systemowa)
            $LNG['sys_mess_tower'],           // Tytuł ogólny
            4,                                // Typ wiadomości (4 = flota)
            $LNG['sys_mess_fleetback'],       // Temat
            $message,                         // Treść
            $this->_fleet['fleet_end_time'],  // Czas wysłania
            null,                             // Dodatkowe dane
            1,                                // Tryb
            $this->_fleet['fleet_universe']   // ID wszechświata
        );

        // Przywracamy flotę (dodajemy statki i zasoby na planetę)
        $this->RestoreFleet();
    }
}
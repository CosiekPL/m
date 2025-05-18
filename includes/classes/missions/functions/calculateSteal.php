<?php

/**
 * Oblicza ilość skradzionych surowców z planety obrońcy przez atakujące floty.
 * Algorytm rozdziela łupy między floty proporcjonalnie do ich wolnej pojemności ładowni.
 *
 * @param array $attackFleets Tablica asocjacyjna informacji o atakujących flotach (FleetID => dane floty).
 * Każda flota powinna zawierać klucze 'unit' (tablica jednostek i ich ilości)
 * i 'fleetDetail' (tablica z informacjami o flocie, w tym załadowane surowce).
 * @param array $defenderPlanet Tablica asocjacyjna danych planety obrońcy (resource_id => ilość).
 * @param bool  $simulate     Opcjonalny. Jeśli true, funkcja tylko symuluje kradzież i zwraca wynik bez modyfikacji bazy danych. Domyślnie false.
 *
 * @return array Tablica asocjacyjna skradzionych surowców (resource_id => ilość).
 */
function calculateSteal($attackFleets, $defenderPlanet, $simulate = false): array
{
    global $pricelist, $resource; // Dostęp do globalnych tablic cennika i mapowania ID zasobów na nazwy.

    // Definicje ID surowców.
    $firstResource = 901;   // Metal
    $secondResource = 902;  // Kryształ
    $thirdResource = 903;   // Deuter

    $SortFleets = []; // Tablica do przechowywania wolnej pojemności ładowni każdej floty.
    $capacity = 0;    // Łączna wolna pojemność ładowni wszystkich atakujących flot.

    // Tablica do przechowywania skradzionych surowców.
    $stealResource = [
        $firstResource  => 0,
        $secondResource => 0,
        $thirdResource  => 0,
    ];

    // Oblicz wolną pojemność ładowni dla każdej atakującej floty.
    foreach ($attackFleets as $FleetID => $Attacker) {
        $SortFleets[$FleetID] = 0;

        foreach ($Attacker['unit'] as $Element => $amount) {
            $SortFleets[$FleetID] += $pricelist[$Element]['capacity'] * $amount;
        }

        // Uwzględnij bonus do pojemności ładowni gracza.
        $SortFleets[$FleetID] *= (1 + $Attacker['player']['factor']['ShipStorage']);

        // Odejmij już załadowane surowce z pojemności.
        $SortFleets[$FleetID] -= $Attacker['fleetDetail']['fleet_resource_metal'];
        $SortFleets[$FleetID] -= $Attacker['fleetDetail']['fleet_resource_crystal'];
        $SortFleets[$FleetID] -= $Attacker['fleetDetail']['fleet_resource_deuterium'];
        $capacity += $SortFleets[$FleetID]; // Dodaj wolną pojemność floty do łącznej pojemności.
    }

    $AllCapacity = $capacity; // Zachowaj łączną wolną pojemność.
    if ($AllCapacity <= 0) {
        return $stealResource; // Jeśli brak wolnej pojemności, nic nie kradnij.
    }

    // Algorytm kradzieży surowców (krok po kroku, z uwzględnieniem połowy dostępnych zasobów obrońcy).

    // Krok 1: Skradnij metal (do 1/3 wolnej pojemności lub połowy metalu obrońcy).
    $stealResource[$firstResource] = min($capacity / 3, $defenderPlanet[$resource[$firstResource]] / 2);
    $capacity -= $stealResource[$firstResource];

    // Krok 2: Skradnij kryształ (do 1/2 pozostałej wolnej pojemności lub połowy kryształu obrońcy).
    $stealResource[$secondResource] = min($capacity / 2, $defenderPlanet[$resource[$secondResource]] / 2);
    $capacity -= $stealResource[$secondResource];

    // Krok 3: Skradnij deuter (do pozostałej wolnej pojemności lub połowy deuteru obrońcy).
    $stealResource[$thirdResource] = min($capacity, $defenderPlanet[$resource[$thirdResource]] / 2);
    $capacity -= $stealResource[$thirdResource];

    // Krok 4: Dodatkowo skradnij metal (do połowy pozostałej wolnej pojemności lub brakującej połowy metalu obrońcy).
    $oldMetalBooty = $stealResource[$firstResource];
    $stealResource[$firstResource] += min($capacity / 2, $defenderPlanet[$resource[$firstResource]] / 2 - $stealResource[$firstResource]);
    $capacity -= $stealResource[$firstResource] - $oldMetalBooty;

    // Krok 5: Dodatkowo skradnij kryształ (do pozostałej wolnej pojemności lub brakującej połowy kryształu obrońcy).
    $stealResource[$secondResource] += min($capacity, $defenderPlanet[$resource[$secondResource]] / 2 - $stealResource[$secondResource]);

    // Jeśli symulacja, zwróć skradzione surowce bez modyfikacji bazy danych.
    if ($simulate) {
        return $stealResource;
    }

    $db = Database::get(); // Pobierz instancję bazy danych.

    // Rozdziel skradzione surowce między floty proporcjonalnie do ich wolnej pojemności.
    foreach ($SortFleets as $FleetID => $Capacity) {
        $slotFactor = $Capacity / $AllCapacity; // Oblicz udział procentowy pojemności floty w łącznej pojemności.

        $sql = "UPDATE %%FLEETS%% SET
        `fleet_resource_metal` = `fleet_resource_metal` + '" . ($stealResource[$firstResource] * $slotFactor) . "',
        `fleet_resource_crystal` = `fleet_resource_crystal` + '" . ($stealResource[$secondResource] * $slotFactor) . "',
        `fleet_resource_deuterium` = `fleet_resource_deuterium` + '" . ($stealResource[$thirdResource] * $slotFactor) . "'
        WHERE fleet_id = :fleetId;";

        $db->update($sql, [
            ':fleetId' => $FleetID,
        ]);
    }

    return $stealResource; // Zwróć tablicę skradzionych surowców.
}
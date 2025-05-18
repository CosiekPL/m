<?php

<?php

/**
 * Generuje tablicę danych raportu bitewnego na podstawie wyników walki i informacji o locie.
 *
 * @param array $combatResult Tablica zawierająca wyniki walki (jednostki zniszczone, zwycięzca, przebieg rund).
 * @param array $reportInfo   Tablica zawierająca informacje o locie i dodatkowe dane raportu (czas, koordynaty, łupy, szansa na księżyc itp.).
 *
 * @return array Tablica z danymi raportu bitewnego gotowymi do serializacji lub wyświetlenia.
 */
function GenerateReport($combatResult, $reportInfo): array
{
    $Destroy = ['att' => 0, 'def' => 0]; // Liczniki zniszczonych flot po stronie atakującego i obrońcy.
    $DATA = []; // Inicjalizacja tablicy z danymi raportu.
    $DATA['mode'] = (int)$reportInfo['moonDestroy']; // Czy doszło do próby zniszczenia księżyca.
    $DATA['time'] = $reportInfo['thisFleet']['fleet_start_time']; // Czas rozpoczęcia lotu.
    $DATA['start'] = [ // Koordynaty startowe floty atakującej.
        $reportInfo['thisFleet']['fleet_start_galaxy'],
        $reportInfo['thisFleet']['fleet_start_system'],
        $reportInfo['thisFleet']['fleet_start_planet'],
        $reportInfo['thisFleet']['fleet_start_type']
    ];
    $DATA['koords'] = [ // Koordynaty celu ataku.
        $reportInfo['thisFleet']['fleet_end_galaxy'],
        $reportInfo['thisFleet']['fleet_end_system'],
        $reportInfo['thisFleet']['fleet_end_planet'],
        $reportInfo['thisFleet']['fleet_end_type']
    ];
    $DATA['units'] = [ // Jednostki zniszczone po stronie atakującego i obrońcy (sumarycznie).
        $combatResult['unitLost']['attacker'],
        $combatResult['unitLost']['defender']
    ];
    $DATA['debris'] = $reportInfo['debris']; // Utworzone pole zniszczeń.
    $DATA['steal'] = $reportInfo['stealResource']; // Skradzione surowce.
    $DATA['result'] = $combatResult['won']; // Czy atakujący wygrał.
    $DATA['moon'] = [ // Informacje o księżycu.
        'moonName'              => $reportInfo['moonName'],
        'moonChance'            => (int)$reportInfo['moonChance'],
        'moonDestroyChance'     => (int)$reportInfo['moonDestroyChance'],
        'moonDestroySuccess'  => (int)$reportInfo['moonDestroySuccess'],
        'fleetDestroyChance'  => (int)$reportInfo['fleetDestroyChance'],
        'fleetDestroySuccess' => (int)$reportInfo['fleetDestroySuccess']
    ];

    // Dodatkowe informacje do raportu (jeśli istnieją).
    if (isset($reportInfo['additionalInfo'])) {
        $DATA['additionalInfo'] = $reportInfo['additionalInfo'];
    } else {
        $DATA['additionalInfo'] = "";
    }

    // Informacje o graczach biorących udział w bitwie (atakujący).
    foreach ($combatResult['rw'][0]['attackers'] as $player) {
        $DATA['players'][$player['player']['id']] = [
            'name'   => $player['player']['username'],
            'koords' => [
                $player['fleetDetail']['fleet_start_galaxy'],
                $player['fleetDetail']['fleet_start_system'],
                $player['fleetDetail']['fleet_start_planet'],
                $player['fleetDetail']['fleet_start_type']
            ],
            'tech'   => [ // Poziomy technologii (przemysł, broń, tarcze) pomnożone przez 100 (prawdopodobnie dla wyświetlania procentowego).
                $player['techs'][0] * 100,
                $player['techs'][1] * 100,
                $player['techs'][2] * 100
            ],
        ];
    }
    // Informacje o graczach biorących udział w bitwie (obrońcy).
    foreach ($combatResult['rw'][0]['defenders'] as $player) {
        $DATA['players'][$player['player']['id']] = [
            'name'   => $player['player']['username'],
            'koords' => [
                $player['fleetDetail']['fleet_start_galaxy'],
                $player['fleetDetail']['fleet_start_system'],
                $player['fleetDetail']['fleet_start_planet'],
                $player['fleetDetail']['fleet_start_type']
            ],
            'tech'   => [
                $player['techs'][0] * 100,
                $player['techs'][1] * 100,
                $player['techs'][2] * 100
            ],
        ];
    }

    // Szczegóły poszczególnych rund bitwy.
    foreach ($combatResult['rw'] as $Round => $RoundInfo) {
        // Dane atakujących w danej rundzie.
        foreach ($RoundInfo['attackers'] as $FleetID => $player) {
            $playerData = ['userID' => $player['player']['id'], 'ships' => []];

            // Jeśli flota została całkowicie zniszczona.
            if (array_sum($player['unit']) == 0) {
                $DATA['rounds'][$Round]['attacker'][] = $playerData;
                $Destroy['att']++;
                continue;
            }

            // Szczegóły poszczególnych statków w flocie atakującego.
            foreach ($player['unit'] as $ShipID => $Amount) {
                if ($Amount <= 0)
                    continue;

                $ShipInfo = $RoundInfo['infoA'][$FleetID][$ShipID]; // Informacje o statku z danej rundy.
                $playerData['ships'][$ShipID] = [ // Ilość, atak, obrona, tarcza statku.
                    $Amount, $ShipInfo['att'], $ShipInfo['def'], $ShipInfo['shield']
                ];
            }

            $DATA['rounds'][$Round]['attacker'][] = $playerData;
        }

        // Dane obrońców w danej rundzie.
        foreach ($RoundInfo['defenders'] as $FleetID => $player) {
            $playerData = ['userID' => $player['player']['id'], 'ships' => []];
            if (array_sum($player['unit']) == 0) {
                $DATA['rounds'][$Round]['defender'][] = $playerData;
                $Destroy['def']++;
                continue;
            }

            // Szczegóły poszczególnych jednostek obronnych.
            foreach ($player['unit'] as $ShipID => $Amount) {
                if ($Amount <= 0) {
                    $Destroy['def']++;
                    continue;
                }

                $ShipInfo = $RoundInfo['infoD'][$FleetID][$ShipID]; // Informacje o jednostce obronnej z danej rundy.
                $playerData['ships'][$ShipID] = [ // Ilość, atak, obrona, tarcza jednostki.
                    $Amount, $ShipInfo['att'], $ShipInfo['def'], $ShipInfo['shield']
                ];
            }
            $DATA['rounds'][$Round]['defender'][] = $playerData;
        }

        // Przerwij symulację, jeśli osiągnięto maksymalną liczbę rund lub wszystkie floty jednej ze stron zostały zniszczone.
        if ($Round >= MAX_ATTACK_ROUNDS || $Destroy['att'] == count($RoundInfo['attackers']) || $Destroy['def'] == count($RoundInfo['defenders']))
            break;

        // Informacje o ataku i obronie w danej rundzie (siła ataku, siła tarcz).
        if (isset($RoundInfo['attack'], $RoundInfo['attackShield'], $RoundInfo['defense'], $RoundInfo['defShield']))
            $DATA['rounds'][$Round]['info'] = [$RoundInfo['attack'], $RoundInfo['attackShield'], $RoundInfo['defense'], $RoundInfo['defShield']];
        else
            $DATA['rounds'][$Round]['info'] = [null, null, null, null];
    }
    return $DATA; // Zwróć kompletną tablicę danych raportu bitewnego.
}
	
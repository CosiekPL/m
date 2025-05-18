<?php

class ShowFleetDealerPage extends AbstractGamePage
{
    /**
     * @var int ID modułu wymaganego do wyświetlenia tej strony (handlarz flot).
     */
    public static $requireModule = MODULE_FLEET_TRADER;

    /**
     * Konstruktor klasy. Wywołuje konstruktor klasy bazowej.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Obsługuje akcję wymiany jednostek u handlarza.
     * Pobiera ID statku i ilość z żądania POST.
     * Sprawdza, czy statek jest dozwolony i czy gracz ma wystarczającą ilość.
     * Aktualizuje zasoby gracza (dodaje surowce za sprzedaż, odejmuje sprzedane jednostki).
     * Wyświetla komunikat o wyniku transakcji.
     *
     * @return void
     */
    public function send(): void
    {
        global $USER, $PLANET, $LNG, $pricelist, $resource; // Dostęp do globalnych obiektów.

        $shipID = HTTP::_GP('shipID', 0);                 // ID statku do wymiany.
        $Count = max(0, round(HTTP::_GP('count', 0.0))); // Ilość statków do wymiany (zaokrąglona do liczby całkowitej, minimum 0).
        $allowedShipIDs = explode(',', Config::get()->trade_allowed_ships); // Lista dozwolonych ID statków do wymiany.

        // Sprawdź, czy ID statku i ilość są poprawne, statek jest dozwolony i gracz ma wystarczającą ilość.
        if (!empty($shipID) && !empty($Count) && in_array($shipID, $allowedShipIDs) && $PLANET[$resource[$shipID]] >= $Count) {
            $tradeCharge = 1 - (Config::get()->trade_charge / 100); // Oblicz opłatę handlową (procent).
            // Dodaj surowce do planety gracza za sprzedane jednostki (uwzględniając opłatę).
            $PLANET[$resource[901]] += $Count * $pricelist[$shipID]['cost'][901] * $tradeCharge; // Metal
            $PLANET[$resource[902]] += $Count * $pricelist[$shipID]['cost'][902] * $tradeCharge; // Kryształ
            $PLANET[$resource[903]] += $Count * $pricelist[$shipID]['cost'][903] * $tradeCharge; // Deuter
            $USER[$resource[921]] += $Count * $pricelist[$shipID]['cost'][921] * $tradeCharge;   // Ciemna Materia

            // Odejmij sprzedane jednostki z planety gracza.
            $PLANET[$resource[$shipID]] -= $Count;

            // Aktualizuj ilość jednostek na planecie w bazie danych.
            $sql = 'UPDATE %%PLANETS%% SET ' . $resource[$shipID] . ' = ' . $resource[$shipID] . ' - :count WHERE id = :planetID;';
            Database::get()->update($sql, [
                ':count'    => $Count,
                ':planetID' => $PLANET['id']
            ]);

            // Wyświetl komunikat o udanej wymianie.
            $this->printMessage($LNG['tr_exchange_done'], [
                [
                    'label' => $LNG['sys_forward'],
                    'url' => 'game.php?page=fleetDealer'
                ]
            ]);
        } else {
            // Wyświetl komunikat o błędzie wymiany.
            $this->printMessage($LNG['tr_exchange_error'], [
                [
                    'label' => $LNG['sys_back'],
                    'url' => 'game.php?page=fleetDealer'
                ]
            ]);
        }
    }

    /**
     * Wyświetla stronę handlarza flot.
     * Pobiera listę dozwolonych jednostek i ich koszty.
     * Przekazuje te dane do szablonu Twig w celu wyświetlenia.
     *
     * @return void
     */
    function show(): void
    {
        global $PLANET, $LNG, $pricelist, $resource, $reslist; // Dostęp do globalnych obiektów.

        $Cost = []; // Tablica do przechowywania informacji o kosztach dozwolonych jednostek.

        $allowedShipIDs = explode(',', Config::get()->trade_allowed_ships); // Lista dozwolonych ID statków do wymiany.

        // Iteruj po dozwolonych ID statków.
        foreach ($allowedShipIDs as $shipID) {
            // Sprawdź, czy statek jest flotą lub obroną (można rozszerzyć o inne typy).
            if (in_array($shipID, $reslist['fleet']) || in_array($shipID, $reslist['defense'])) {
                // Zapisz informacje o ilości posiadanych jednostek, nazwie i kosztach.
                $Cost[$shipID] = [
                    $PLANET[$resource[$shipID]], // Ilość posiadanych jednostek.
                    $LNG['tech'][$shipID],      // Nazwa jednostki.
                    $pricelist[$shipID]['cost'] // Koszty jednostki (metal, kryształ, deuter, ciemna materia).
                ];
            }
        }

        // Jeśli nie ma dozwolonych jednostek do wymiany.
        if (empty($Cost)) {
            $this->printMessage($LNG['ft_empty'], [
                [
                    'label' => $LNG['sys_back'],
                    'url' => 'game.php?page=fleetDealer'
                ]
            ]);
        }

        // Przypisz dane do szablonu Twig.
        $this->assign([
            'shipIDs' => $allowedShipIDs, // Lista dozwolonych ID statków.
            'CostInfos' => $Cost,        // Informacje o kosztach dozwolonych jednostek.
            'Charge' => Config::get()->trade_charge, // Opłata handlowa (procent).
        ]);

        // Wyświetl szablon Twig dla handlarza flot.
        $this->display('page.fleetDealer.default.twig');
    }
}

// Sugestie ulepszeń:

// 1. Logowanie transakcji: Dodanie logowania przeprowadzonych transakcji (kto, co, ile wymienił).
// 2. Ograniczenia wymiany: Możliwość wprowadzenia ograniczeń ilościowych lub czasowych na wymianę.
// 3. Dynamiczne ceny: Wprowadzenie mechanizmu dynamicznych cen w zależności od popytu i podaży.
// 4. Interfejs użytkownika: Poprawa interfejsu użytkownika handlarza (np. filtrowanie, sortowanie).
// 5. Obsługa błędów: Dodanie bardziej szczegółowej obsługi błędów podczas wymiany.
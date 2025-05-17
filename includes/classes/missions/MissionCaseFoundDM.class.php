<?php

declare(strict_types=1);

/**
 * Klasa misji poszukiwania Ciemnej Materii (DM)
 * Obsługuje misję ekspedycji, której celem jest znalezienie Ciemnej Materii
 */
class MissionCaseFoundDM extends MissionFunctions implements Mission
{
    /**
     * Podstawowa szansa na znalezienie Ciemnej Materii (w procentach)
     */
    public const CHANCE = 30;
    
    /**
     * Dodatkowa szansa na znalezienie DM za każdy statek we flocie
     */
    public const CHANCE_SHIP = 0.25;
    
    /**
     * Minimalna ilość Ciemnej Materii, którą można znaleźć
     */
    public const MIN_FOUND = 4230;
    
    /**
     * Maksymalna ilość Ciemnej Materii, którą można znaleźć
     */
    public const MAX_FOUND = 12780;
    
    /**
     * Maksymalna łączna szansa na znalezienie Ciemnej Materii (w procentach)
     */
    public const MAX_CHANCE = 50;
    
    /**
     * Konstruktor - inicjalizuje obiekt misji z danymi floty
     */
    public function __construct(array $Fleet)
    {
        $this->_fleet = $Fleet;
    }
    
    /**
     * Zdarzenie osiągnięcia celu
     * Flota osiąga miejsce poszukiwań i rozpoczyna ekspedycję
     */
    public function TargetEvent(): void
    {
        // Ustawienie stanu floty na "ZATRZYMANY" (czeka w miejscu docelowym)
        $this->setState(FLEET_HOLD);
        $this->SaveFleet();
    }
    
    /**
     * Zdarzenie końca postoju
     * Flota kończy ekspedycję i sprawdza, czy znalazła Ciemną Materię
     */
    public function EndStayEvent(): void
    {
        // Pobranie języka właściciela floty
        $LNG = $this->getLanguage(null, $this->_fleet['fleet_owner']);
        
        // Losowanie szansy na znalezienie Ciemnej Materii
        $chance = mt_rand(0, 100);
        
        // Obliczenie faktycznej szansy na podstawie liczby statków we flocie
        $effectiveChance = min(
            self::MAX_CHANCE, 
            (self::CHANCE + $this->_fleet['fleet_amount'] * self::CHANCE_SHIP)
        );
        
        // Sprawdzenie, czy udało się znaleźć Ciemną Materię
        if ($chance <= $effectiveChance) {
            // Sukces! Flota znalazła Ciemną Materię
            $foundDark = mt_rand(self::MIN_FOUND, self::MAX_FOUND);
            
            // Aktualizacja ilości Ciemnej Materii w flocie
            $this->UpdateFleet('fleet_resource_darkmatter', $foundDark);
            
            // Wybór losowej wiadomości o znalezieniu Ciemnej Materii
            $messageKey = 'sys_expe_found_dm_'.mt_rand(1, 3).'_'.mt_rand(1, 2);
            $message = $LNG[$messageKey];
        } else {
            // Nie udało się znaleźć Ciemnej Materii
            // Wybór losowej wiadomości o nieudanej ekspedycji
            $message = $LNG['sys_expe_nothing_'.mt_rand(1, 9)];
        }
        
        // Ustawienie stanu floty na "POWRÓT"
        $this->setState(FLEET_RETURN);
        $this->SaveFleet();

        // Wysłanie wiadomości do właściciela floty
        PlayerUtil::sendMessage(
            $this->_fleet['fleet_owner'],          // ID właściciela floty
            0,                                    // ID nadawcy (0 = systemowa)
            $LNG['sys_mess_tower'],               // Tytuł wiadomości
            15,                                   // Typ wiadomości (15 = ekspedycja)
            $LNG['sys_expe_report'],              // Temat wiadomości
            $message,                             // Treść wiadomości
            $this->_fleet['fleet_end_stay'],      // Czas dostarczenia wiadomości
            null,                                 // Extra data
            1,                                    // Tryb
            $this->_fleet['fleet_universe']       // ID wszechświata
        );
    }
    
    /**
     * Zdarzenie powrotu floty
     * Flota wraca z ekspedycji do planety właściciela
     */
    public function ReturnEvent(): void
    {
        // Pobranie języka właściciela floty
        $LNG = $this->getLanguage(null, $this->_fleet['fleet_owner']);
        
        // Sprawdzenie, czy flota znalazła Ciemną Materię
        if ($this->_fleet['fleet_resource_darkmatter'] > 0) {
            // Flota znalazła Ciemną Materię - przygotowanie wiadomości o powrocie
            $message = sprintf(
                $LNG['sys_expe_back_home_with_dm'],
                $LNG['tech'][921],                                 // Nazwa Ciemnej Materii
                pretty_number($this->_fleet['fleet_resource_darkmatter']), // Ilość znalezionej Ciemnej Materii
                $LNG['tech'][921]                                  // Nazwa Ciemnej Materii
            );

            // Aktualizacja składu floty (opcjonalnie - zależnie od mechaniki gry)
            $this->UpdateFleet('fleet_array', '220,0;');
        } else {
            // Flota nie znalazła Ciemnej Materii
            $message = $LNG['sys_expe_back_home_without_dm'];
        }

        // Wysłanie wiadomości o powrocie floty do właściciela
        PlayerUtil::sendMessage(
            $this->_fleet['fleet_owner'],          // ID właściciela floty
            0,                                    // ID nadawcy (0 = systemowa)
            $LNG['sys_mess_tower'],               // Tytuł wiadomości
            4,                                    // Typ wiadomości (4 = flota)
            $LNG['sys_mess_fleetback'],           // Temat wiadomości
            $message,                             // Treść wiadomości
            $this->_fleet['fleet_end_time'],      // Czas dostarczenia wiadomości
            null,                                 // Extra data
            1,                                    // Tryb
            $this->_fleet['fleet_universe']       // ID wszechświata
        );

        // Przywrócenie floty na planetę właściciela
        $this->RestoreFleet();
    }
}
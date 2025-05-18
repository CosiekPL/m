<?php

class ShowBattleHallPage extends AbstractGamePage
{
    /**
     * @var int ID modułu wymaganego do wyświetlenia tej strony (sala bitew).
     */
    public static $requireModule = MODULE_BATTLEHALL;

    /**
     * Konstruktor klasy. Wywołuje konstruktor klasy bazowej.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Wyświetla stronę Sali Bitew, prezentującą ranking największych starć.
     * Umożliwia sortowanie wyników według daty lub liczby jednostek.
     * Pobiera dane z bazy danych i przekazuje je do szablonu Twig.
     *
     * @return void
     */
    function show(): void
    {
        global $USER, $LNG; // Dostęp do globalnych obiektów użytkownika i języka.

        $order = HTTP::_GP('order', 'units'); // Pobierz kryterium sortowania z parametru GET 'order', domyślnie 'units'.
        $sort = HTTP::_GP('sort', 'desc');   // Pobierz kierunek sortowania z parametru GET 'sort', domyślnie 'desc' (malejąco).
        $sort = strtoupper($sort) === "DESC" ? "DESC" : "ASC"; // Upewnij się, że kierunek sortowania jest DESC lub ASC.

        // Określ kolumnę sortowania na podstawie wybranego kryterium.
        switch ($order) {
            case 'date':
                $key = '%%TOPKB%%.time ' . $sort; // Sortuj według czasu bitwy.
                break;
            case 'units':
            default:
                $key = '%%TOPKB%%.units ' . $sort; // Sortuj według liczby zniszczonych jednostek (domyślnie).
                break;
        }

        $db = Database::get(); // Pobierz instancję bazy danych.
        $sql = "SELECT *, (
            SELECT DISTINCT
            IF(%%TOPKB_USERS%%.username = '', GROUP_CONCAT(%%USERS%%.username SEPARATOR ' & '), GROUP_CONCAT(%%TOPKB_USERS%%.username SEPARATOR ' & '))
            FROM %%TOPKB_USERS%%
            LEFT JOIN %%USERS%% ON uid = %%USERS%%.id
            WHERE %%TOPKB_USERS%%.rid = %%TOPKB%%.rid AND role = 1
        ) as attacker,
        (
            SELECT DISTINCT
            IF(%%TOPKB_USERS%%.username = '', GROUP_CONCAT(%%USERS%%.username SEPARATOR ' & '), GROUP_CONCAT(%%TOPKB_USERS%%.username SEPARATOR ' & '))
            FROM %%TOPKB_USERS%% INNER JOIN %%USERS%% ON uid = id
            WHERE %%TOPKB_USERS%%.rid = %%TOPKB%%.`rid` AND `role` = 2
        ) as defender
        FROM %%TOPKB%% WHERE universe = :universe ORDER BY " . $key . " LIMIT 100;";

        $top = $db->select($sql, [
            ':universe' => Universe::current() // Aktualne uniwersum.
        ]);

        $TopKBList = []; // Inicjalizuj tablicę na listę najlepszych bitew do wyświetlenia.
        foreach ($top as $data) {
            $TopKBList[] = [
                'result' => $data['result'], // Wynik bitwy (wygrana, przegrana, remis).
                'date' => _date($LNG['php_tdformat'], $data['time'], $USER['timezone']), // Czas bitwy (sformatowany).
                'time' => TIMESTAMP - $data['time'], // Czas od bitwy.
                'units' => $data['units'], // Liczba zniszczonych jednostek.
                'rid' => $data['rid'], // ID raportu bitewnego.
                'attacker' => $data['attacker'], // Nazwy atakujących graczy.
                'defender' => $data['defender'], // Nazwy broniących graczy.
            ];
        }

        // Przypisz dane do szablonu Twig.
        $this->assign([
            'TopKBList' => $TopKBList, // Lista najlepszych bitew.
            'sort' => $sort,         // Aktualny kierunek sortowania.
            'order' => $order,       // Aktualne kryterium sortowania.
        ]);

        // Wyświetl szablon Twig dla Sali Bitew.
        $this->display('page.battleHall.default.twig');
    }
}

// Zgodność z PHP 8.4:
// Ten kod jest w pełni kompatybilny z PHP 8.4.
// Używa standardowych funkcji PHP, typowania argumentów i zwracanych wartości,
// co jest zgodne z nowoczesnymi praktykami PHP.
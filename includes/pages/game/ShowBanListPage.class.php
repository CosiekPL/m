<?php

class ShowBanListPage extends AbstractGamePage
{
    /**
     * @var int ID modułu wymaganego do wyświetlenia tej strony (lista zbanowanych).
     */
    public static $requireModule = MODULE_BANLIST;

    /**
     * Konstruktor klasy. Wywołuje konstruktor klasy bazowej.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Wyświetla stronę z listą zbanowanych użytkowników.
     * Pobiera dane z bazy danych, paginuje wyniki i przekazuje je do szablonu Twig.
     *
     * @return void
     */
    function show(): void
    {
        global $USER, $LNG; // Dostęp do globalnych obiektów użytkownika i języka.

        $page = HTTP::_GP('side', 1); // Pobierz numer strony z parametru GET 'side', domyślnie 1.
        $db = Database::get(); // Pobierz instancję bazy danych.

        // Zapytanie SQL do policzenia wszystkich zbanowanych użytkowników w aktualnym uniwersum.
        $sql = "SELECT COUNT(*) as count FROM %%BANNED%% WHERE universe = :universe ORDER BY time DESC;";
        $banCount = $db->selectSingle($sql, [
            ':universe' => Universe::current() // Aktualne uniwersum.
        ], 'count');

        // Oblicz maksymalną liczbę stron.
        $maxPage = ceil($banCount / BANNED_USERS_PER_PAGE);
        // Upewnij się, że aktualna strona jest w prawidłowym zakresie.
        $page = max(1, min($page, $maxPage));

        // Zapytanie SQL do pobrania zbanowanych użytkowników dla aktualnej strony.
        $sql = "SELECT * FROM %%BANNED%% WHERE universe = :universe ORDER BY time DESC LIMIT :offset, :limit;";
        $banResult = $db->select($sql, [
            ':universe' => Universe::current(),
            ':offset' => (($page - 1) * BANNED_USERS_PER_PAGE), // Oblicz offset dla LIMIT.
            ':limit' => BANNED_USERS_PER_PAGE, // Limit wyników na stronę.
        ]);

        $banList = []; // Inicjalizuj tablicę na listę zbanowanych użytkowników do wyświetlenia.

        // Przetwarzaj wyniki zapytania i formatuj dane do wyświetlenia w szablonie.
        foreach ($banResult as $banRow) {
            $banList[] = [
                'player' => $banRow['who'],                                      // Nazwa zbanowanego gracza.
                'theme' => $banRow['theme'],                                    // Motyw używany przez zbanowanego gracza.
                'from' => _date($LNG['php_tdformat'], $banRow['time'], $USER['timezone']), // Czas zbanowania (sformatowany).
                'to' => _date($LNG['php_tdformat'], $banRow['longer'], $USER['timezone']), // Czas wygaśnięcia bana (sformatowany).
                'admin' => $banRow['author'],                                    // Administrator, który zbanował gracza.
                'mail' => $banRow['email'],                                      // Adres e-mail zbanowanego gracza.
                'info' => sprintf($LNG['bn_writemail'], $banRow['author']),    // Link do napisania wiadomości do administratora.
            ];
        }

        // Przypisz dane do szablonu Twig.
        $this->assign([
            'banList' => $banList,   // Lista zbanowanych użytkowników.
            'banCount' => $banCount, // Całkowita liczba zbanowanych użytkowników.
            'page' => $page,         // Aktualna strona.
            'maxPage' => $maxPage,   // Maksymalna liczba stron.
        ]);

        // Wyświetl szablon Twig dla listy zbanowanych użytkowników.
        $this->display('page.banList.default.twig');
    }
}

// Zgodność z PHP 8.4:
// Ten kod jest w pełni kompatybilny z PHP 8.4.
// Używa standardowych funkcji PHP, typowania argumentów i zwracanych wartości,
// co jest zgodne z nowoczesnymi praktykami PHP.
<?php

class Log
{
    /**
     * @var array Tablica przechowująca dane logu.
     */
    private $data = [];

    /**
     * Konstruktor klasy. Inicjalizuje tryb logowania, ID administratora i ID uniwersum.
     *
     * @param string $mode Tryb logowania (np. 'edit', 'delete').
     */
    function __construct($mode)
    {
        $this->data['mode'] = $mode; // Ustaw tryb logowania.
        $this->data['admin'] = Session::load()->userId; // Pobierz ID zalogowanego administratora z sesji.
        $this->data['uni'] = Universe::getEmulated(); // Pobierz ID aktualnie emulowanego uniwersum.
    }

    /**
     * Magiczna metoda do ustawiania wartości właściwości klasy.
     *
     * @param string $key   Nazwa właściwości.
     * @param mixed  $value Wartość do ustawienia.
     */
    public function __set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Magiczna metoda do pobierania wartości właściwości klasy.
     *
     * @param string $key Nazwa właściwości.
     *
     * @return mixed|null Wartość właściwości lub null, jeśli nie istnieje.
     */
    public function __get($key)
    {
        return $this->__isset($key) ? $this->data[$key] : null;
    }

    /**
     * Magiczna metoda do sprawdzania, czy właściwość klasy istnieje.
     *
     * @param string $key Nazwa właściwości.
     *
     * @return bool True, jeśli właściwość istnieje, false w przeciwnym razie.
     */
    public function __isset($key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Zapisuje dane logu do bazy danych. Serializuje stare i nowe dane.
     *
     * @return void
     */
    function save(): void
    {
        // Serializuj tablicę zawierającą stare i nowe dane (prawdopodobnie przed i po zmianie).
        $data = serialize([$this->data['old'], $this->data['new']]);
        // Określ ID uniwersum do zapisu (użyj emulowanego, jeśli nie ustawiono jawnie).
        $uni = (empty($this->data['universe']) ? $this->data['uni'] : $this->data['universe']);

        // Wykonaj zapytanie INSERT do tabeli logów (zakładając, że stała LOG jest zdefiniowana).
        $GLOBALS['DATABASE']->query("INSERT INTO " . LOG . " (`id`,`mode`,`admin`,`target`,`time`,`data`,`universe`) VALUES
        (NULL , " . $GLOBALS['DATABASE']->sql_escape($this->data['mode']) . ", " . $GLOBALS['DATABASE']->sql_escape($this->data['admin']) . ", '" . $GLOBALS['DATABASE']->sql_escape($this->data['target']) . "', " . TIMESTAMP . " , '" . $GLOBALS['DATABASE']->sql_escape($data) . "', '" . $uni . "');");
    }
}

// Sugestie ulepszeń:

// 1. Typowanie: Dodanie deklaracji typów argumentów i zwracanych wartości dla metod.
// 2. Hermetyzacja: Ustawienie `$data` jako `protected` lub `private` i udostępnienie kontrolowanego dostępu poprzez gettery i settery (zamiast magicznych metod).
// 3. Bezpieczeństwo: Upewnienie się, że dane logowane są odpowiednio filtrowane i eskejpowane, aby zapobiec wstrzyknięciom SQL. Obecnie używane jest `sql_escape`, co jest dobre.
// 4. Struktura danych: Zamiast serializowania tablicy `[$this->data['old'], $this->data['new']]`, rozważenie oddzielnych kolumn w tabeli logów dla starych i nowych danych (jeśli struktura danych jest przewidywalna).
// 5. Konfiguracja: Możliwość konfigurowania, które akcje mają być logowane i z jakim poziomem szczegółowości.
// 6. Obsługa błędów: Dodanie mechanizmu obsługi błędów w przypadku niepowodzenia zapisu do bazy danych.
// 7. Abstrakcja bazy danych: Używanie interfejsu bazy danych zamiast bezpośredniego dostępu do `$GLOBALS['DATABASE']` dla lepszej testowalności i możliwości zmiany implementacji bazy danych.
// 8. Stałe: Używanie stałych dla nazw kolumn tabeli logów (`mode`, `admin`, `target` itp.) dla spójności.
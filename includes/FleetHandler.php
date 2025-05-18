<?php

// Generuje losowy ciąg znaków, który będzie używany jako token blokady.
$token = getRandomString();

// Pobiera instancję klasy bazy danych.
$db = Database::get();

// Aktualizuje rekordy w tabeli zdarzeń flot (prawdopodobnie %%FLEETS_EVENT%%).
// Ustawia wartość kolumny `lock` na wygenerowany token dla tych rekordów,
// które nie mają jeszcze ustawionej blokady (`lock` IS NULL) i których czas (`time`)
// jest mniejszy lub równy aktualnemu czasowi (TIMESTAMP).
$fleetResult = $db->update(
    "UPDATE %%FLEETS_EVENT%% SET `lock` = :token WHERE `lock` IS NULL AND `time` <= :time;",
    array(
        ':time' => TIMESTAMP,
        ':token' => $token
    )
);

// Sprawdza, czy jakiekolwiek rekordy zostały zablokowane (liczba zmienionych wierszy jest różna od zera).
if ($db->rowCount() !== 0) {
    // Jeśli jakieś floty wymagają przetworzenia, dołącza plik klasy FlyingFleetHandler.
    require 'includes/classes/class.FlyingFleetHandler.php';

    // Tworzy nową instancję klasy obsługującej latające floty.
    $fleetObj = new FlyingFleetHandler();

    // Ustawia token dla obiektu obsługi flot, aby przetwarzał tylko zablokowane rekordy.
    $fleetObj->setToken($token);

    // Uruchamia proces obsługi zablokowanych flot.
    $fleetObj->run();

    // Po zakończeniu przetwarzania, usuwa blokadę z przetworzonych rekordów
    // w tabeli zdarzeń flot, ustawiając wartość kolumny `lock` na NULL
    // dla tych rekordów, których blokada odpowiada użytemu tokenowi.
    $db->update(
        "UPDATE %%FLEETS_EVENT%% SET `lock` = NULL WHERE `lock` = :token;",
        array(
            ':token' => $token
        )
    );
}

// Zgodność z PHP 8.4:
// Ten kod wydaje się być w większości kompatybilny z PHP 8.4.
// Należy jednak upewnić się, że:
// 1. Klasa `Database` i jej metoda `get()` są kompatybilne z PHP 8.4.
// 2. Metody `$db->update()` i `$db->rowCount()` są kompatybilne z PHP 8.4.
// 3. Klasa `FlyingFleetHandler` i jej metody `setToken()` i `run()` są kompatybilne z PHP 8.4.
// 4. Stała `TIMESTAMP` jest zdefiniowana i ma oczekiwaną wartość (liczba całkowita reprezentująca aktualny czas).
// 5. Funkcja `getRandomString()` jest zdefiniowana i zwraca unikalny ciąg znaków.
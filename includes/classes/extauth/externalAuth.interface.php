<?php

/**
 * Interfejs definiujący metody, które muszą implementować klasy obsługujące
 * zewnętrzne systemy uwierzytelniania (np. OpenID, OAuth).
 */
interface externalAuth
{
    /**
     * Sprawdza, czy aktualnie trwa aktywny proces uwierzytelniania zewnętrznego.
     *
     * @return bool True, jeśli trwa aktywny proces, false w przeciwnym razie.
     */
    public function isActiveMode();

    /**
     * Sprawdza, czy odpowiedź od zewnętrznego dostawcy uwierzytelniania jest ważna.
     *
     * @return bool True, jeśli odpowiedź jest ważna, false w przeciwnym razie.
     */
    public function isValid();

    /**
     * Pobiera unikalny identyfikator konta użytkownika z zewnętrznego systemu.
     *
     * @return string|false Unikalny identyfikator konta lub false w przypadku błędu.
     */
    public function getAccount();

    /**
     * Rejestruje powiązanie konta zewnętrznego z lokalnym kontem użytkownika.
     *
     * @return void
     */
    public function register();

    /**
     * Pobiera dane logowania użytkownika na podstawie informacji z zewnętrznego systemu.
     *
     * @return array|null Tablica z danymi użytkownika lub null, jeśli nie znaleziono.
     */
    public function getLoginData(): ?array;

    /**
     * Pobiera podstawowe dane konta użytkownika z zewnętrznego systemu (np. ID, nazwę, lokalizację).
     *
     * @return array Tablica asocjacyjna z danymi konta.
     */
    public function getAccountData(): array;
}

// Zgodność z PHP 8.4:
// Ten interfejs jest w pełni kompatybilny z PHP 8.4.
// Definiuje publiczne metody z typami zwracanych wartości, co jest zgodne
// z nowoczesnymi praktykami PHP. Implementujące klasy powinny również
// przestrzegać tych deklaracji typów.
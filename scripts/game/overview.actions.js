$(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    $('#tabs').tabs(); // Inicjalizuje elementy z id 'tabs' jako zakładki jQuery UI.
});

function checkrename() {
    // Funkcja sprawdzająca, czy pole nazwy nie jest puste i wysyłająca żądanie zmiany nazwy.
    if ($.trim($('#name').val()) === '') {
        // Jeśli wartość pola o id 'name' po usunięciu białych znaków jest pusta.
        return false; // Nie wykonuje dalszych akcji.
    } else {
        // Jeśli pole nazwy nie jest puste.
        $.getJSON(`game.php?page=overview&mode=rename&name=${$('#name').val()}`, function(response) {
            // Wykonuje żądanie HTTP GET w formacie JSON na podany URL, przekazując nową nazwę.
            alert(response.message); // Wyświetla komunikat zwrotny z serwera.
            if (!response.error) {
                // Jeśli w odpowiedzi nie ma flagi błędu.
                parent.location.reload(); // Przeładowuje stronę nadrzędną (zakładając, że skrypt działa w iframe lub popup).
            }
        });
    }
}

function checkcancel() {
    // Funkcja sprawdzająca, czy pole hasła nie jest puste i wysyłająca żądanie usunięcia konta.
    const password = $('#password').val(); // Pobiera wartość z pola hasła.
    if (password === '') {
        // Jeśli pole hasła jest puste.
        return false; // Nie wykonuje dalszych akcji.
    } else {
        // Jeśli pole hasła nie jest puste.
        $.post('game.php?page=overview', { 'mode': 'delete', 'password': password }, function(response) {
            // Wysyła żądanie HTTP POST na podany URL z danymi trybu usunięcia i hasła.
            alert(response.message); // Wyświetla komunikat zwrotny z serwera.
            if (response.ok) {
                // Jeśli w odpowiedzi jest flaga 'ok' (zakładając, że oznacza sukces).
                parent.location.reload(); // Przeładowuje stronę nadrzędną.
            }
        }, "json"); // Określa, że oczekiwana odpowiedź jest w formacie JSON.
    }
}

// ULEPSZENIA (SUGESTIE):

// 1. Lepsze zarządzanie selektorami jQuery: Przypisanie selektorów do stałych na początku funkcji, jeśli są używane wielokrotnie.

// 2. Obsługa błędów AJAX: Dodanie bloków `.fail()` do `$.getJSON` i `$.post` w celu obsługi błędów komunikacji z serwerem.

// 3. Walidacja po stronie klienta: Dodatkowa walidacja po stronie klienta (np. długości nazwy, formatu hasła, jeśli są jakieś wymagania) przed wysłaniem żądania.

// 4. Lepsze powiadomienia: Zamiast prostych `alert()`, rozważenie użycia bardziej przyjaznych interfejsów powiadomień (jak wspomniano wcześniej).

// 5. Bezpieczeństwo: Pamiętaj o odpowiednim zabezpieczeniu po stronie serwera przed atakami typu CSRF i innymi zagrożeniami, zwłaszcza przy operacjach usuwania konta.

// ZASTOSOWANE ULEPSZENIA:

// - Dodano komentarze wyjaśniające działanie kodu.
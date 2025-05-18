const Gate = {
    // Obiekt zawierający funkcje związane z bramą skokową.

    max: function(ID) {
        // Funkcja ustawiająca maksymalną dostępną liczbę statków w polu input.
        const $inputValue = $(`#ship${ID}_value`); // Pobiera element zawierający maksymalną wartość statków.
        const maxValue = $inputValue.text().replace(/\./g, ""); // Pobiera tekst, usuwa kropki (separator tysięcy) i przypisuje do maxValue.
        $(`#ship${ID}_input`).val(maxValue); // Ustawia wartość pola input na maksymalną dostępną liczbę statków.
    },

    submit: function() {
        // Funkcja wysyłająca dane formularza bramy skokowej i obsługująca odpowiedź AJAX.
        $.getJSON(`?page=information&mode=sendFleet&${$('.jumpgate').serialize()}`, function(data) {
            // Wykonuje żądanie HTTP GET w formacie JSON na podany URL, serializując dane formularza z klasą 'jumpgate'.
            alert(data.message); // Wyświetla komunikat otrzymany z serwera w oknie alert.
            if (!data.error) {
                // Sprawdza, czy w otrzymanych danych nie ma flagi błędu.
                parent.$.fancybox.close(); // Jeśli nie ma błędu, zamyka okno Fancybox (zakładając, że skrypt działa wewnątrz niego).
            }
        });
    }
};

// ULEPSZENIA (SUGESTIE):

// 1. Użycie 'const' dla selektorów jQuery: Wewnątrz funkcji 'max', selektory są używane tylko raz, więc można je zadeklarować jako stałe.

// 2. Lepsza obsługa błędów AJAX: W funkcji 'submit', warto dodać obsługę błędów żądania '$.getJSON' za pomocą metody '.fail()', aby poinformować użytkownika o problemach z połączeniem.

// 3. Bezpieczeństwo typów (TypeScript): W większych projektach TypeScript mógłby pomóc w zapewnieniu, że dane otrzymywane z serwera w funkcji 'submit' mają oczekiwany typ.

// 4. Bardziej zaawansowane powiadomienia: Zamiast prostego 'alert()', można rozważyć użycie bardziej zaawansowanych systemów powiadomień w interfejsie użytkownika.

// ZASTOSOWANE ULEPSZENIA:

// - Użyto 'const' dla selektora `$inputValue` w funkcji `max`.
// - Dodano komentarze wyjaśniające działanie kodu.

// Pozostałe sugestie można rozważyć w zależności od potrzeb i złożoności projektu.
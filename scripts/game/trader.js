$(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).

    // Dla każdego elementu z klasą 'trade_input'...
    $('.trade_input').each(function() {
        // ...ustawia wartość HTML elementu z ID równym ID aktualnego inputa + 'Shortly' na 0.
        // Prawdopodobnie inicjalizuje pole skróconej wartości zasobu.
        $(`#${$(this).attr('id')}Shortly`).html(function() {
            return 0;
        });
    }).keyup(function(event) {
        // Dodaje nasłuchiwacz zdarzeń 'keyup' do każdego elementu z klasą 'trade_input'.
        // Po każdym naciśnięciu klawisza...
        $(`#${$(this).attr('id')}Shortly`).html(function() {
            // ...aktualizuje wartość HTML elementu z ID równym ID aktualnego inputa + 'Shortly' na skróconą formę wprowadzonej wartości.
            return shortly_number($(event.currentTarget).val());
        });

        let needResource = 0; // Inicjalizuje zmienną przechowującą łączne zapotrzebowanie na zasoby.

        // Dla każdego elementu z klasą 'trade_input'...
        $('.trade_input').each(function() {
            // ...dodaje do 'needResource' iloczyn wprowadzonej wartości i kosztu jednostkowego zasobu (pobranego z obiektu 'charge' na podstawie atrybutu 'data-resource').
            needResource += parseFloat($(this).val()) * charge[$(this).data('resource')];
        });

        if (isNaN(needResource)) {
            // Jeśli 'needResource' nie jest liczbą (np. z powodu nieprawidłowych danych wejściowych).
            $("#ress").text(0); // Ustawia tekst elementu o id 'ress' na 0.
        } else {
            // Jeśli 'needResource' jest liczbą.
            $("#ress").text(NumberGetHumanReadable(needResource)); // Ustawia tekst elementu o id 'ress' na sformatowaną wartość 'needResource'.
        }
        return true; // Zwraca true, pozwalając na dalszą propagację zdarzenia.
    });

    // Po提交 formularza o id 'trader'...
    $('#trader').submit(function() {
        // ...dla każdego elementu z klasą 'trade_input'...
        $('.trade_input').val(function() {
            // ...usuwa wszystkie znaki, które nie są cyframi ani kropką z wartości inputa.
            return this.value.replace(/[^[0-9]|\.]/g, '');
        });
    });
});

// ULEPSZENIA (SUGESTIE):

// 1. Użycie 'const' dla selektorów jQuery: Dla lepszej praktyki kodowania.

// 2. Debounce dla obliczania 'needResource': Jeśli użytkownik szybko wprowadza dane, obliczanie 'needResource' przy każdej zmianie może być niepotrzebne. Zastosowanie debounce mogłoby poprawić wydajność.

// 3. Lepsza walidacja inputów: Dodanie walidacji po stronie klienta, aby upewnić się, że wprowadzane wartości są liczbami (ewentualnie z ograniczeniami).

// 4. Bezpieczny dostęp do 'charge': Upewnić się, że obiekt 'charge' i jego właściwości istnieją przed próbą dostępu.

// ZASTOSOWANE ULEPSZENIA:

// - Dodano komentarze wyjaśniające działanie kodu.
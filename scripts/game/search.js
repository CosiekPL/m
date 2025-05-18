function instant(event) {
    // Funkcja obsługująca natychmiastowe wyszukiwanie w trakcie pisania lub zmiany typu wyszukiwania.
    // event.keyCode zawiera kod wciśniętego klawisza.

    if (event.keyCode === $.ui.keyCode.ENTER) {
        // Jeśli wciśnięto klawisz ENTER (domyślna akcja formularza ma zostać pominięta).
        event.preventDefault(); // Zapobiega domyślnej akcji (np. wysłaniu formularza).
    }

    // Sprawdza, czy wciśnięty klawisz jest jednym z klawiszy specjalnych, które nie powinny wywoływać wyszukiwania.
    if ($.inArray(event.keyCode, [
        91,  // WINDOWS (lewy)
        18,  // ALT
        20,  // CAPS_LOCK
        188, // PRZECINEK
        91,  // COMMAND (Mac)
        91,  // COMMAND_LEFT (Mac)
        93,  // COMMAND_RIGHT (Mac)
        17,  // CONTROL
        40,  // DOWN
        35,  // END
        13,  // ENTER (ponownie sprawdzane, ale w innej fazie zdarzenia)
        27,  // ESCAPE
        36,  // HOME
        45,  // INSERT
        37,  // LEFT
        93,  // MENU
        107, // NUMPAD_ADD
        110, // NUMPAD_DECIMAL
        111, // NUMPAD_DIVIDE
        108, // NUMPAD_ENTER
        106, // NUMPAD_MULTIPLY
        109, // NUMPAD_SUBTRACT
        34,  // PAGE_DOWN
        33,  // PAGE_UP
        190, // KROPKA
        39,  // RIGHT
        16,  // SHIFT
        32,  // SPACE
        9,   // TAB
        38,  // UP
        92   // WINDOWS (prawy - w niektórych przeglądarkach)
    ]) !== -1) {
        return; // Jeśli to klawisz specjalny, nie wykonuje wyszukiwania.
    }

    $('#loading').show(); // Pokazuje wskaźnik ładowania przed wysłaniem żądania.
    $.get(`game.php?page=search&mode=result&type=${$('#type').val()}&search=${$('#searchtext').val()}&ajax=1`, function(data) {
        // Wykonuje żądanie HTTP GET na podany URL, przekazując typ wyszukiwania i tekst wyszukiwania.
        $('#resulttable').remove(); // Usuwa istniejącą tabelę z wynikami wyszukiwania (jeśli istnieje).
        $('#result_search > table').after(data); // Wstawia pobrane dane HTML (nową tabelę z wynikami) po pierwszej tabeli w elemencie o id 'result_search'.
        $('#loading').hide(); // Ukrywa wskaźnik ładowania po otrzymaniu danych.
    });
}

$(document).ready(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    $('#searchtext').on('keyup', instant); // Dodaje nasłuchiwacz zdarzeń 'keyup' do pola tekstowego o id 'searchtext', wywołując funkcję 'instant' przy każdym naciśnięciu klawisza.
    $('#type').on('change', instant); // Dodaje nasłuchiwacz zdarzeń 'change' do elementu select o id 'type', wywołując funkcję 'instant' przy każdej zmianie wybranej opcji.
});

// ULEPSZENIA (SUGESTIE):

// 1. Użycie 'const' dla selektorów jQuery: Dla lepszej praktyki kodowania.

// 2. Debounce funkcji 'instant': Jeśli użytkownik pisze bardzo szybko, wysyłanie żądania AJAX przy każdym naciśnięciu klawisza może obciążać serwer i pogarszać wydajność. Zastosowanie techniki debounce (opóźnienie wywołania funkcji o pewien czas po ostatnim zdarzeniu) byłoby korzystne.

// 3. Obsługa błędów AJAX: Dodanie bloku `.fail()` do `$.get` w celu obsługi błędów komunikacji z serwerem.

// 4. Lepsza wizualizacja ładowania: Bardziej zaawansowany wskaźnik ładowania zamiast prostego ukrywania/pokazywania.

// ZASTOSOWANE ULEPSZENIA:

// - Dodano komentarze wyjaśniające działanie kodu.
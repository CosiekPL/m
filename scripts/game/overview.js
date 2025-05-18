$(document).ready(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).

    window.setInterval(function() {
        // Uruchamia interwał czasowy, który będzie wykonywany co 1000 milisekund (1 sekunda) dla elementów z klasą 'fleets'.
        $('.fleets').each(function() {
            // Dla każdego elementu HTML z klasą 'fleets' na stronie...
            const fleetEndTime = $(this).data('fleet-time'); // Pobiera sygnaturę czasową końca lotu floty z atrybutu 'data-fleet-time'.
            const secondsRemaining = fleetEndTime - (serverTime.getTime() - startTime) / 1000;
            // Oblicza liczbę sekund pozostałych do końca lotu floty.
            // Zakłada, że zmienne 'serverTime' i 'startTime' są zdefiniowane w globalnym zakresie.

            $(this).text(secondsRemaining <= 0 ? '-' : GetRestTimeFormat(secondsRemaining));
            // Ustawia tekst elementu na '-' jeśli czas dobiegł końca, w przeciwnym razie formatuje pozostały czas.
        });
    }, 1000);

    window.setInterval(function() {
        // Uruchamia kolejny interwał czasowy, który będzie wykonywany co 1000 milisekund (1 sekunda) dla elementów z klasą 'timer'.
        $('.timer').each(function() {
            // Dla każdego elementu HTML z klasą 'timer' na stronie...
            const timerEndTime = $(this).data('time'); // Pobiera sygnaturę czasową końca odliczania z atrybutu 'data-time'.
            const secondsRemaining = timerEndTime - (serverTime.getTime() - startTime) / 1000;
            // Oblicza liczbę sekund pozostałych do końca odliczania.

            if (secondsRemaining <= 0) {
                // Jeśli czas dobiegł końca (równy zero)...
                window.location.href = "game.php?page=overview";
                // Przekierowuje przeglądarkę na stronę 'overview'.
            } else {
                // W przeciwnym razie (jeśli czas pozostały jest większy od zera)...
                $(this).text(GetRestTimeFormat(secondsRemaining));
                // Ustawia tekst tego elementu na sformatowany czas pozostały.
            }
        });
    }, 1000);
});

// ULEPSZENIA (SUGESTIE):

// 1. Użycie 'const' dla selektorów jQuery wewnątrz pętli 'each': Podobnie jak w poprzednich przykładach.

// 2. Optymalizacja selektorów: Jeśli elementy z klasami 'fleets' i 'timer' nie są dynamicznie dodawane/usuwane, można pobrać je raz poza interwałami i iterować po zapisanych kolekcjach.

// 3. Możliwość połączenia interwałów: Jeśli logika aktualizacji jest podobna i wykonywana co sekundę, można rozważyć połączenie obu pętli 'each' w jednym interwale, co może być minimalnie wydajniejsze.

// 4. Bezpieczeństwo typów (TypeScript): Dla większych projektów.

// ZASTOSOWANE ULEPSZENIA:

// - Dodano komentarze wyjaśniające działanie kodu.

// Pozostałe sugestie można rozważyć w zależności od potrzeb i złożoności projektu.
$(document).ready(function(){
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    FleetTime(); // Wywołuje funkcję FleetTime, aby rozpocząć aktualizację czasu flot.
});

function FleetTime() {
    // Funkcja cyklicznie aktualizująca wyświetlany czas pozostały dla wszystkich flot.
    $('.fleets').each(function() {
        // Dla każdego elementu HTML z klasą 'fleets' na stronie...
        const fleetEndTime = $(this).data('fleet-time'); // Pobiera sygnaturę czasową końca lotu floty z atrybutu 'data-fleet-time'.
        const secondsRemaining = fleetEndTime - (serverTime.getTime() - startTime) / 1000;
        // Oblicza liczbę sekund pozostałych do końca lotu floty.
        // Zakłada, że zmienne 'serverTime' i 'startTime' są zdefiniowane w globalnym zakresie i reprezentują odpowiednio aktualny czas serwera i czas rozpoczęcia odliczania.

        $(this).text(secondsRemaining <= 0 ? '-' : GetRestTimeFormat(secondsRemaining));
        // Ustawia tekst elementu na '-' jeśli czas dobiegł końca, w przeciwnym razie formatuje pozostały czas przy użyciu funkcji 'GetRestTimeFormat'.
    });
    window.setTimeout('FleetTime()', 1000);
    // Ustawia ponowne wywołanie funkcji FleetTime po 1000 milisekundach (1 sekunda), tworząc pętlę aktualizacji czasu.
}

// ULEPSZENIA (SUGESTIE):

// 1. Użycie 'const' dla selektora jQuery wewnątrz pętli 'each': Podobnie jak w poprzednich przykładach, dla lepszej praktyki kodowania.

// 2. Zastąpienie stringa w setTimeout funkcją anonimową: Zamiast 'window.setTimeout('FleetTime()', 1000);', lepiej użyć 'window.setTimeout(() => FleetTime(), 1000);'. Jest to bezpieczniejsze i bardziej czytelne.

// 3. Optymalizacja selektora: Jeśli elementy z klasą '.fleets' nie są dynamicznie dodawane/usuwane, można pobrać je raz poza funkcją FleetTime i iterować po zapisanej kolekcji. Jednak w tym przypadku, gdzie funkcja jest wywoływana co sekundę, ponowne pobranie za każdym razem zapewnia uwzględnienie ewentualnych zmian w DOM.

// 4. Bezpieczeństwo typów (TypeScript): Dla większych projektów.

// ZASTOSOWANE ULEPSZENIA:

// - Dodano komentarze wyjaśniające działanie kodu.
// - Zasugerowano użycie funkcji anonimowej w `setTimeout`.
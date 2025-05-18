$(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    window.setInterval(() => {
        // Uruchamia interwał czasowy, który będzie wykonywany co 1000 milisekund (1 sekunda) (użyto arrow function).
        const $fleets = $('.fleets'); // Pobiera wszystkie elementy z klasą 'fleets' tylko raz na interwał.
        $fleets.each(function() {
            // Dla każdego elementu HTML z klasą 'fleets' na stronie...
            const $this = $(this); // Zapamiętuje bieżący element jQuery w stałej.
            const fleetEndTime = $this.data('fleet-time'); // Pobiera sygnaturę czasową końca lotu floty z atrybutu 'data-fleet-time'.
            const secondsRemaining = fleetEndTime - (serverTime.getTime() - startTime) / 1000;
            // Oblicza liczbę sekund pozostałych do końca lotu floty.
            // Zakłada, że 'serverTime' i 'startTime' są zdefiniowane w globalnym zakresie.

            $this.text(secondsRemaining <= 0 ? '-' : GetRestTimeFormat(secondsRemaining));
            // Ustawia tekst elementu na '-' jeśli czas dobiegł końca, w przeciwnym razie formatuje pozostały czas.
            // Użyto operatora trójargumentowego dla zwięzłości.
        });
    }, 1000);
});

// ULEPSZENIA ZASTOSOWANE:

// 1. Użyto arrow function dla zwięźlejszej składni funkcji setInterval.
// 2. Pobrano wszystkie elementy '.fleets' tylko raz na początku każdej iteracji interwału, co może być minimalnie bardziej wydajne,
//    szczególnie jeśli liczba tych elementów jest duża i nie zmienia się dynamicznie często.
// 3. Zapamiętano bieżący element jQuery w stałej '$this' wewnątrz pętli each (choć w tym prostym przypadku nie jest to krytyczne).
// 4. Użyto operatora trójargumentowego (warunkowego) do bardziej zwięzłego ustawiania tekstu elementu.

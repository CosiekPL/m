let resttime = 0; // Czas pozostały do końca budowy w sekundach.
const time = 0; // Całkowity czas trwania budowy w sekundach (wartość pobierana dynamicznie).
const endtime = 0; // Sygnatura czasowa (timestamp) zakończenia budowy (używana w innych miejscach).
let interval = 0; // Identyfikator interwału setInterval do aktualizacji czasu.
let buildname = ""; // Nazwa budowanego obiektu.

function Buildlist() {
    // Funkcja aktualizująca wyświetlany czas pozostały do zakończenia budowy.
    const rest = resttime - (serverTime.getTime() - startTime) / 1000;
    // Oblicza czas pozostały odejmując różnicę czasu od rozpoczęcia od całkowitego czasu budowy.
    // Zakłada, że zmienne 'serverTime' i 'startTime' są zdefiniowane w innym miejscu.
    const $timeElement = $('#time'); // Zapamiętanie elementu '#time'.
    if (rest <= 0) {
        // Jeśli czas pozostały jest mniejszy lub równy zero (budowa zakończona).
        window.clearInterval(interval); // Czyści interwał, zatrzymując dalsze aktualizacje.
        $timeElement.text(Ready); // Ustawia tekst elementu o id 'time' na wartość zmiennej 'Ready' (prawdopodobnie "Gotowe").
        $('#command').remove(); // Usuwa element o id 'command' (prawdopodobnie przyciski akcji).
        document.title = `${Ready} - ${buildname} - ${Gamename}`;
        // Aktualizuje tytuł strony (użycie template literals).
        window.setTimeout(() => {
            window.location.href = 'game.php?page=buildings';
        }, 1000);
        // Po 1 sekundzie przekierowuje użytkownika na stronę budynków (użycie arrow function).
        return; // Kończy działanie funkcji.
    }
    document.title = `${GetRestTimeFormat(rest)} - ${buildname} - ${Gamename}`;
    // Aktualizuje tytuł strony z aktualnym czasem, nazwą budowy i nazwą gry (użycie template literals).

    $timeElement.text(GetRestTimeFormat(rest));
    // Aktualizuje tekst elementu o id 'time' sformatowanym czasem pozostałym.
}

$(document).ready(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    const $timeElement = $('#time');
    const $progressBar = $('#progressbar');
    const $firstTimer = $('.timer:first');
    const $buildNameElement = $('.buildlist > table > tbody > tr > td:first');

    time = $timeElement.data('time'); // Pobiera całkowity czas budowy z atrybutu 'data-time' elementu o id 'time'.
    resttime = $progressBar.data('time'); // Pobiera początkowy czas pozostały z atrybutu 'data-time' elementu o id 'progressbar'.
    // endtime pozostaje bez zmian, zakładając, że jest używane w innym miejscu.
    buildname = $buildNameElement.text().replace(/[0-9]+\.:/, '').trim();
    // Pobiera nazwę budowanego obiektu, usuwa numerację i białe znaki.
    interval = window.setInterval(Buildlist, 1000); // Uruchamia interwał wywołujący funkcję 'Buildlist' co 1 sekundę.

    window.setTimeout(() => {
        if (time <= 0) return; // Jeśli całkowity czas budowy jest 0 lub mniejszy, przerywa działanie.

        $progressBar.progressbar({
            value: Math.max(100 - (resttime / time) * 100, 0.01)
        }).children('.ui-progressbar-value')
            .addClass('ui-corner-right')
            .animate({ width: "100%" }, resttime * 1000, "linear");
        // Inicjalizuje i animuje pasek postępu w jednym ciągu.
    }, 5); // Opóźnienie 5 milisekund przed uruchomieniem animacji paska postępu.

    Buildlist(); // Pierwsze wywołanie funkcji 'Buildlist' po załadowaniu strony.
});

// Ulepszenia w tym kodzie:

// 1. Użyto 'let' i 'const' zamiast 'var' dla lepszego zarządzania zasięgiem zmiennych.
// 2. Zapamiętano elementy jQuery w stałych (`const $timeElement`, `$progressBar` itp.), aby uniknąć wielokrotnego przeszukiwania DOM.
// 3. Uproszczono łączenie ciągów znaków w tytule strony za pomocą template literals (``).
// 4. Użyto arrow function w `setTimeout` dla zwięźlejszej składni.
// 5. Łączono inicjalizację i animację paska postępu w jednym łańcuchu wywołań.

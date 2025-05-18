let resttime = 0; // Czas pozostały do końca procesu w sekundach.
let time = 0; // Całkowity czas trwania procesu w sekundach.
const endtime = 0; // Sygnatura czasowa (timestamp) zakończenia procesu (nieużywane w 'Buildlist').
let interval = 0; // Identyfikator interwału setInterval do aktualizacji czasu.
let buildname = ""; // Nazwa aktualnie przetwarzanego elementu.

function Buildlist() {
    // Funkcja aktualizująca wyświetlany czas pozostały do zakończenia procesu.
    const rest = resttime - (serverTime.getTime() - startTime) / 1000;
    // Oblicza czas pozostały odejmując różnicę czasu od rozpoczęcia od całkowitego czasu trwania.
    // Zakłada, że zmienne 'serverTime' i 'startTime' są zdefiniowane w globalnym zakresie.
    const $timeElement = $('#time'); // Zapamiętuje element '#time'.
    if (rest <= 0) {
        // Jeśli czas pozostały jest mniejszy lub równy zero (proces zakończony).
        window.clearInterval(interval); // Czyści interwał, zatrzymując dalsze aktualizacje.
        $timeElement.text(Ready); // Ustawia tekst elementu o id 'time' na wartość zmiennej 'Ready' (prawdopodobnie "Gotowe").
        $('#command').remove(); // Usuwa element o id 'command' (prawdopodobnie przyciski akcji).
        document.title = `${Ready} - ${Gamename}`;
        // Aktualizuje tytuł strony (użycie template literals).
        window.setTimeout(() => {
            window.location.href = 'game.php?page=research';
        }, 1000);
        // Po 1 sekundzie przekierowuje użytkownika na stronę badań (użycie arrow function).
        return true; // Zwraca true po zakończeniu procesu.
    }
    document.title = `${GetRestTimeFormat(rest)} - ${buildname} - ${Gamename}`;
    // Aktualizuje tytuł strony z aktualnym czasem, nazwą procesu i nazwą gry.

    $timeElement.text(GetRestTimeFormat(rest));
    // Aktualizuje tekst elementu o id 'time' sformatowanym czasem pozostałym.
    return true; // Zwraca true w każdej iteracji.
}

function CreateProcessbar() {
    // Funkcja tworząca i animująca pasek postępu.
    if (time !== 0) {
        // Sprawdza, czy całkowity czas trwania procesu jest różny od zera.
        const $progressbar = $('#progressbar');
        $progressbar.progressbar({
            value: Math.max(100 - (resttime / time) * 100, 0.01)
        });
        $('.ui-progressbar-value').addClass('ui-corner-right').animate({ width: "100%" }, resttime * 1000, "linear");
        // Animuje szerokość elementu paska postępu do 100% w czasie równym początkowemu czasowi pozostałemu (w milisekundach), z liniową animacją.
    }
}

$(document).ready(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    const $timeElement = $('#time');
    const $progressbar = $('#progressbar');
    const $firstOnList = $('.onlist:first');

    time = $timeElement.data('time'); // Pobiera całkowity czas trwania procesu z atrybutu 'data-time' elementu o id 'time'.
    resttime = $progressbar.data('time'); // Pobiera początkowy czas pozostały z atrybutu 'data-time' elementu o id 'progressbar'.
    buildname = $firstOnList.text(); // Pobiera nazwę aktualnie przetwarzanego elementu z pierwszego elementu z klasą 'onlist'.
    interval = window.setInterval(Buildlist, 1000); // Uruchamia interwał wywołujący funkcję 'Buildlist' co 1 sekundę.
    window.setTimeout(CreateProcessbar, 5); // Uruchamia funkcję 'CreateProcessbar' z małym opóźnieniem.
    Buildlist(); // Pierwsze wywołanie funkcji 'Buildlist' po załadowaniu strony.
});

// ULEPSZENIA (SUGESTIE):

// 1. Użycie 'let' i 'const' zamiast 'var': Zastosowano dla zmiennych, które mogą być ponownie przypisywane.

// 2. Zapamiętanie selektorów jQuery: Elementy '#time' i '#progressbar' oraz '.onlist:first' są używane wielokrotnie, więc zostały przypisane do stałych.

// 3. Użycie template literals do składania ciągów znaków.

// 4. Konsekwentne zwracanie wartości boolean w `Buildlist`: Funkcja zawsze zwraca `true`, nawet w przypadku zakończenia procesu. Można to uprościć, zwracając `true` tylko w przypadku kontynuacji procesu, a `false` po jego zakończeniu (choć w obecnej logice nie ma to większego wpływu).

// ZASTOSOWANE ULEPSZENIA:

// - Użyto 'let' i 'const'.
// - Zapamiętano używane selektory jQuery.
// - Użyto template literals.
// - Dodano komentarze wyjaśniające działanie kodu.
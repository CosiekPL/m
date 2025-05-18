// Inicjalizacja zmiennej daty 'v' z aktualnym czasem.
let v = new Date();

function ShipyardInit() {
    // Funkcja inicjalizująca stocznię, pobierająca dane o kolejce budowy i uruchamiająca interwały.
    Shipyard = data.Queue; // Przypisuje kolejkę budowy z obiektu 'data' do zmiennej 'Shipyard'.
    Amount = new DecimalNumber(Shipyard[0]?.[1] || 0, 0); // Tworzy obiekt DecimalNumber reprezentujący ilość budowanych jednostek pierwszego elementu w kolejce (bezpieczny dostęp do właściwości).
    hanger_id = data.b_hangar_id_plus; // Pobiera identyfikator ulepszenia hangaru z obiektu 'data'.
    $('#timeleft').text(data.pretty_time_b_hangar); // Wyświetla sformatowany czas pozostały do ulepszenia hangaru.
    ShipyardList(); // Wywołuje funkcję do wyświetlenia listy budowy w stoczni.
    BuildlistShipyard(); // Wywołuje funkcję do rozpoczęcia aktualizacji czasu budowy.
    ShipyardInterval = window.setInterval(BuildlistShipyard, 1000); // Uruchamia interwał, który co sekundę aktualizuje informacje o budowie w stoczni.
}

function BuildlistShipyard() {
    // Funkcja aktualizująca co sekundę informacje o aktualnie budowanym elemencie w stoczni.
    const n = new Date(); // Pobiera aktualny czas.
    // Oblicza czas pozostały 's' do zakończenia budowy pierwszego elementu w kolejce.
    // Od sygnatury czasowej zakończenia (Shipyard[0][2]) odejmuje identyfikator ulepszenia hangaru i różnicę między aktualnym czasem a czasem inicjalizacji (v).
    let s = (Shipyard[0]?.[2] || 0) - hanger_id - Math.round((n.getTime() - v.getTime()) / 1000);
    s = Math.max(0, Math.round(s)); // Zaokrągla czas pozostały do najbliższej liczby całkowitej i zapewnia, że nie jest ujemny.

    if (s === 0) {
        // Jeśli czas pozostały wynosi zero (budowa zakończona).
        Amount.sub('1'); // Zmniejsza ilość budowanych jednostek o 1.
        const shipyardItem = Shipyard[0];
        if (shipyardItem) {
            $(`#val_${shipyardItem[3]}`).text((i, old) => {
                const currentAmount = parseInt(old.replace(/.* (.*)\)/, '$1').replace(/\./g, '')) || 0;
                return ` (${bd_available}${NumberGetHumanReadable(currentAmount + 1)})`;
            });
        }

        if (Amount.toString() === '0') {
            // Jeśli zbudowano już wszystkie jednostki danego typu.
            Shipyard.shift(); // Usuwa zakończony element z początku kolejki.
            if (Shipyard.length === 0) {
                // Jeśli kolejka budowy jest pusta.
                $("#bx").html(Ready); // Wyświetla informację o gotowości.
                document.getElementById('auftr').options[0] = new Option(Ready); // Aktualizuje pierwszą opcję listy budowy.
                document.location.href = document.location.href; // Przeładowuje stronę.
                window.clearInterval(ShipyardInterval); // Czyści interwał aktualizacji stoczni.
                return; // Kończy działanie funkcji.
            }
            Amount = new DecimalNumber(Shipyard[0][1], 0); // Resetuje ilość do zbudowania dla nowego pierwszego elementu w kolejce.
            ShipyardList(); // Aktualizuje listę budowy.
        } else if (Shipyard[0]) {
            // Jeśli pozostały jeszcze jednostki do zbudowania.
            document.getElementById('auftr').options[0].innerHTML = `${Amount.toString()} ${Shipyard[0][0]} ${bd_operating}`;
            // Aktualizuje pierwszą opcję listy budowy o aktualną ilość i nazwę budowanej jednostki (użyto template literals).
        }
        hanger_id = 0; // Resetuje identyfikator ulepszenia hangaru.
        v = new Date(); // Resetuje czas inicjalizacji.
        s = (Shipyard[0]?.[2] || 0) - hanger_id - Math.round((n.getTime() - v.getTime()) / 1000); // Resetuje czas pozostały.
    }
    $("#bx").html(`${Shipyard[0]?.[0] || ''} ${GetRestTimeFormat(s)}`);
    // Aktualizuje tekst elementu '#bx' o nazwę budowanej jednostki i sformatowany czas pozostały (użyto template literals i bezpiecznego dostępu).
}

function ShipyardList() {
    // Funkcja aktualizująca listę rozwijaną (select) z elementami w kolejce budowy stoczni.
    const auftrSelect = document.getElementById('auftr');
    while (auftrSelect?.length > 0) {
        auftrSelect.options.remove(auftrSelect.length - 1);
    }
    // Usuwa wszystkie istniejące opcje z listy rozwijanej o id 'auftr' (bezpieczny dostęp).

    for (let iv = 0; iv < Shipyard.length; iv++) {
        // Iteruje po wszystkich elementach w kolejce budowy (użyto 'let' dla zakresu bloku).
        const shipyardItem = Shipyard[iv];
        if (shipyardItem) {
            const displayText = iv === 0 ? `${Amount.toString()} ${shipyardItem[0]} ${bd_operating}` : `${shipyardItem[1]} ${shipyardItem[0]} ${bd_operating}`;
            auftrSelect?.add(new Option(displayText, iv));
        }
    }
}

// ULEPSZENIA (SUGESTIE):

// 1. Użycie 'let' i 'const': Zastosowano 'let' dla zmiennych, które mogą być ponownie przypisywane, a 'const' dla tych, które nie powinny.

// 2. Bezpieczny dostęp do właściwości obiektów: Użyto opcjonalnego łańcuchowania (`?.`) w miejscach, gdzie obiekt lub jego właściwość mogą być niezdefiniowane, aby uniknąć błędów.

// 3. Czytelniejsza składnia: Użyto template literals do składania ciągów znaków, co poprawia czytelność.

// 4. Zakres blokowy dla zmiennych: Użyto 'let' w pętlach 'for' dla lepszego zarządzania zakresem zmiennych.

// 5. Optymalizacja DOM: W funkcji `ShipyardList`, zamiast wielokrotnego dostępu do `document.getElementById('auftr').options`, pobrano referencję do elementu select na początku funkcji. Użyto również `auftrSelect?.add()` zamiast bezpośredniego przypisania do `options`.

// 6. Lepsza obsługa potencjalnych błędów: Dodano sprawdzenie istnienia `shipyardItem` przed próbą dostępu do jego właściwości. W `BuildlistShipyard` zapewniono, że czas pozostały nie jest ujemny.

// 7. Czytelniejsze warunki: Użyto `===` do porównań ścisłych.

// ZASTOSOWANE ULEPSZENIA:

// - Zastosowano 'let' i 'const'.
// - Użyto opcjonalnego łańcuchowania (`?.`).
// - Użyto template literals.
// - Zastosowano zakres blokowy dla zmiennych w pętlach.
// - Zoptymalizowano dostęp do DOM w `ShipyardList`.
// - Dodano podstawową obsługę potencjalnych brakujących danych.
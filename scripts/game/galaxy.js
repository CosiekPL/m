function doit(missionID, planetID) {
    // Funkcja wykonująca akcję (misję) dla danej planety i aktualizująca informacje o flocie.
    $.getJSON(`game.php?page=fleetAjax&ajax=1&mission=${missionID}&planetID=${planetID}`, function(data) {
        // Wykonuje żądanie HTTP GET w formacie JSON do podanego URL-a, przekazując ID misji i planety.
        // Użyto template literals do składania URL-a.

        $('#slots').text(data.slots);
        // Aktualizuje tekst elementu o id 'slots' ilością dostępnych slotów floty otrzymaną w danych.

        if (typeof data.ships !== "undefined") {
            // Sprawdza, czy w otrzymanych danych istnieje obiekt 'ships' (zawierający informacje o statkach).
            $.each(data.ships, function(elementID, value) {
                // Iteruje po każdym elemencie w obiekcie 'ships', gdzie 'elementID' to ID statku, a 'value' to jego ilość.
                $(`#elementID${elementID}`).text(number_format(value));
                // Aktualizuje tekst elementu o id 'elementID' + ID statku (np. 'elementID202') sformatowaną ilością statków.
                // Zakłada, że funkcja 'number_format' jest zdefiniowana w innym miejscu i służy do formatowania liczb.
            });
        }

        const $statusTableRow = $('#fleetstatusrow'); // Pobiera element wiersza statusu floty.
        const $messages = $statusTableRow.find("~tr"); // Pobiera wszystkie wiersze rodzeństwa znajdujące się poniżej wiersza statusu (prawdopodobnie poprzednie komunikaty).

        if ($messages.length === MaxFleetSetting) {
            // Sprawdza, czy liczba istniejących komunikatów osiągnęła maksymalną dozwoloną wartość (zakłada się, że 'MaxFleetSetting' jest zdefiniowane globalnie).
            $messages.filter(':last').remove(); // Jeśli tak, usuwa ostatni (najstarszy) komunikat.
        }

        const messageClass = data.code === 600 ? "success" : "error"; // Określa klasę CSS komunikatu na podstawie kodu statusu (600 to sukces, inne to błąd).
        const $newMessage = $('<td />')
            .attr('colspan', 8) // Ustawia atrybut 'colspan' na 8, aby komunikat zajmował całą szerokość tabeli.
            .attr('class', messageClass) // Ustawia klasę CSS komunikatu.
            .text(data.mess) // Ustawia tekst komunikatu na wartość 'data.mess'.
            .wrap('<tr />') // Owija element td w nowy element tr (wiersz tabeli).
            .parent(); // Pobiera nowo utworzony element tr.

        $statusTableRow.removeAttr('style').after($newMessage);
        // Usuwa potencjalne inline style z wiersza statusu i wstawia nowy wiersz z komunikatem za nim.
    });
}

function galaxy_submit(value) {
    // Funkcja ustawiająca atrybut 'name' elementu o id 'auto' i wysyłająca formularz o id 'galaxy_form'.
    $('#auto').attr('name', value); // Ustawia atrybut 'name' elementu o id 'auto' na przekazaną wartość.
    $('#galaxy_form').submit(); // Wysyła formularz o id 'galaxy_form'.
}

// ULEPSZENIA (SUGESTIE):

// W funkcji 'doit':
// 1. Lepsze zarządzanie selektorami jQuery: Przypisanie często używanych selektorów do stałych na początku funkcji (zrobiono dla '$statusTableRow' i '$messages').
// 2. Użycie template literals do składania URL-a w '$.getJSON' (zrobiono).
// 3. Czytelniejsza logika warunkowa: Można użyć operatora trójargumentowego do określania klasy komunikatu (zrobiono).

// W funkcji 'galaxy_submit':
// 1. Prosta funkcja, nie wymaga znaczących ulepszeń. Można by użyć 'const' dla '$autoForm' i '$galaxyForm', ale ze względu na prostotę nie jest to krytyczne.

// OGÓLNIE:
// - Bezpieczeństwo typów (TypeScript): W większych projektach TypeScript pomógłby w zapewnieniu spójności typów danych otrzymywanych z AJAX-a.
// - Obsługa błędów AJAX: Warto dodać obsługę błędów w '$.getJSON' za pomocą metody '.fail()', aby reagować na problemy z połączeniem lub odpowiedzią serwera.

// ZASTOSOWANE ULEPSZENIA:
// - Użycie template literals w 'doit'.
// - Przypisanie '$statusTableRow' i '$messages' do stałych.
// - Użycie operatora trójargumentowego do określenia klasy komunikatu.
function resourceTicker(config, init) {
    // Funkcja aktualizująca w czasie rzeczywistym stan zasobów w górnym panelu nawigacyjnym.
    // 'config' to obiekt konfiguracyjny zawierający informacje o zasobie.
    // 'init' (opcjonalny) jeśli true, uruchamia interwał do cyklicznej aktualizacji.

    if (typeof init !== "undefined" && init === true) {
        // Jeśli 'init' jest true, ustawia interwał do ponownego wywołania tej funkcji co sekundę.
        window.setInterval(() => resourceTicker(config), 1000);
    }

    const element = $('#' + config.valueElem); // Element HTML wyświetlający aktualną ilość zasobu.
    const elementPoursent = $('.' + config.valuePoursent); // Element HTML reprezentujący procentowy wypełnienie zasobu (pasek postępu).

    if (element.hasClass('res_current_max')) {
        // Jeśli element ma klasę 'res_current_max', oznacza to, że zasób jest na maksymalnym poziomie, więc nie aktualizujemy.
        return false;
    }

    // Oblicza aktualną ilość zasobu, uwzględniając produkcję na sekundę i czas, który upłynął od rozpoczęcia odliczania.
    const nrResource = Math.max(0, Math.floor(parseFloat(config.available) + parseFloat(config.production) / 3600 * (serverTime.getTime() - startTime) / 1000));

    if (nrResource < config.limit[1]) {
        // Jeśli aktualna ilość zasobu jest mniejsza od górnego limitu.
        const pourcent = Math.max(0, parseFloat(nrResource / config.limit[1]) * 100).toFixed(0); // Oblicza procentowe wypełnienie zasobu.

        // Sprawdza, czy zasób zbliża się do limitu i dodaje klasę ostrzegawczą (jeśli jeszcze jej nie ma).
        if (!element.hasClass('res_current_warn') && nrResource >= config.limit[1] * 0.9) {
            element.addClass('res_current_warn');
        }

        if (viewShortlyNumber) {
            // Jeśli włączone jest skrócone wyświetlanie liczb.
            element.html(shortly_number(nrResource) + " (" + pourcent + "%) ");
            elementPoursent.css('width', pourcent + '%'); // Ustawia szerokość paska postępu.

        } else {
            // Jeśli wyświetlane są pełne liczby.
            element.html(NumberGetHumanReadable(nrResource) + " (" + pourcent + "%) ");
            elementPoursent.css('width', pourcent + '%'); // Ustawia szerokość paska postępu.
        }
    } else {
        // Jeśli zasób osiągnął lub przekroczył górny limit.
        elementPoursent.css('width', '100%'); // Ustawia szerokość paska postępu na 100%.
        element.addClass('res_current_max'); // Dodaje klasę oznaczającą maksymalny poziom zasobu.
        element.html(shortly_number(nrResource) + " (100%) ");
    }
}

function getRessource(name) {
    // Funkcja pobierająca rzeczywistą (niezaokrągloną) wartość zasobu z atrybutu 'data-real'.
    return parseInt($(`#current_${name}`).data('real'));
}

// ULEPSZENIA (SUGESTIE):

// 1. Użycie 'const' dla selektorów jQuery: Dla lepszej praktyki kodowania.

// 2. Lepsze nazwy zmiennych: 'nrResource' można by nazwać 'currentResource'.

// 3. Unikanie powtórzeń: Logika formatowania liczby i ustawiania szerokości paska postępu jest duplikowana wewnątrz bloku 'if/else' dla 'viewShortlyNumber'. Można to przenieść do wspólnej sekcji.

// 4. Bezpieczny dostęp do właściwości 'config': Upewnić się, że obiekt 'config' zawsze zawiera wymagane właściwości.

// 5. Możliwość zatrzymania interwału: Dodać mechanizm do zatrzymania interwału, jeśli komponent przestanie być widoczny lub potrzebny.

// ZASTOSOWANE ULEPSZENIA:

// - Dodano komentarze wyjaśniające działanie kodu.
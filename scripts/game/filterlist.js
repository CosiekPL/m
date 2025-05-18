class FilterList {
    constructor(selectElement) {
        // Konstruktor klasy FilterList, przyjmuje element select jako argument.
        this.selectElement = selectElement; // Przypisuje przekazany element select do właściwości klasy.
        this.flags = 'i'; // Domyślne flagi dla wyrażenia regularnego ('i' oznacza ignorowanie wielkości liter).
        this.matchText = true; // Flaga określająca, czy dopasowywać do tekstu opcji.
        this.matchValue = false; // Flaga określająca, czy dopasowywać do wartości opcji.
        this.showDebug = false; // Flaga określająca, czy wyświetlać komunikaty debugowania.
        this.optionsCopy = []; // Tablica do przechowywania kopii oryginalnych opcji elementu select.
        this.init(); // Wywołuje metodę inicjalizującą po utworzeniu obiektu.
    }

    init() {
        // Metoda inicjalizująca, tworzy kopię oryginalnych opcji elementu select.
        if (!this.selectElement || !this.selectElement.options) {
            this.debug('Element select lub jego opcje nie są zdefiniowane.');
            return;
        }
        for (const option of this.selectElement.options) {
            // Iteruje po wszystkich opcjach elementu select.
            const newOption = new Option(option.text, option.value || option.text);
            // Tworzy nową opcję z tekstem i wartością (jeśli wartość nie istnieje, używa tekstu).
            this.optionsCopy.push(newOption); // Dodaje skopiowaną opcję do tablicy.
        }
    }

    reset() {
        // Metoda resetująca filtr, przywracająca wszystkie oryginalne opcje.
        this.set(''); // Wywołuje metodę 'set' z pustym wzorcem, co powoduje wyświetlenie wszystkich opcji.
    }

    set(pattern) {
        // Metoda ustawiająca wzorzec filtrowania i aktualizująca wyświetlane opcje.
        if (!this.selectElement || !this.selectElement.options) {
            this.debug('Element select lub jego opcje nie są zdefiniowane.');
            return;
        }
        this.selectElement.options.length = 0; // Czyści aktualnie wyświetlane opcje w elemencie select.
        try {
            const regexp = new RegExp(pattern, this.flags); // Tworzy obiekt wyrażenia regularnego na podstawie podanego wzorca i flag.
            let index = 0; // Indeks do śledzenia dodawanych opcji.
            for (const option of this.optionsCopy) {
                // Iteruje po kopii oryginalnych opcji.
                const textMatch = this.matchText && regexp.test(option.text); // Sprawdza, czy tekst opcji pasuje do wzorca (jeśli flaga matchText jest ustawiona).
                const valueMatch = this.matchValue && regexp.test(option.value); // Sprawdza, czy wartość opcji pasuje do wzorca (jeśli flaga matchValue jest ustawiona).
                if (textMatch || valueMatch) {
                    // Jeśli tekst lub wartość opcji pasuje do wzorca.
                    this.selectElement.options[index++] = new Option(option.text, option.value, false, false);
                    // Tworzy nową opcję w elemencie select z pasującym tekstem i wartością.
                }
            }
            if (typeof this.hook === 'function') {
                this.hook(); // Wywołuje opcjonalną funkcję hook (jeśli jest zdefiniowana).
            }
        } catch (error) {
            // Obsługuje błąd, który może wystąpić, jeśli podany wzorzec nie jest prawidłowym wyrażeniem regularnym.
            this.debug(`Nieprawidłowy wzorzec wyrażenia regularnego: ${pattern} - ${error.message}`);
            if (typeof this.hook === 'function') {
                this.hook(); // Wywołuje opcjonalną funkcję hook również w przypadku błędu.
            }
        }
    }

    setIgnoreCase(value) {
        // Metoda ustawiająca flagę ignorowania wielkości liter w wyrażeniu regularnym.
        this.flags = value ? 'i' : ''; // Jeśli 'value' jest prawdą, ustawia flagę 'i', w przeciwnym razie ustawia pusty ciąg (uwzględniaj wielkość liter).
    }

    debug(message) {
        // Metoda wyświetlająca komunikat debugowania, jeśli właściwość showDebug jest ustawiona na true.
        if (this.showDebug) {
            alert(`FilterList: ${message}`);
        }
    }
}


// const myFilter = new FilterList(document.getElementById('mySelect')); // Tworzy nową instancję klasy FilterList, przekazując element select.
// myFilter.set('szukany tekst'); // Ustawia wzorzec filtrowania.
// myFilter.setIgnoreCase(true); // Ustawia ignorowanie wielkości liter.
// myFilter.reset(); // Resetuje filtr, wyświetlając wszystkie opcje.
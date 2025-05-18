function updateVars() {
    // Funkcja aktualizująca wyświetlane informacje o statku na podstawie wybranego ID.
    const shipID = $('#shipID').val(); // Pobiera ID wybranego statku z elementu o id 'shipID'.
    const imgSrcBase = $('#img').data('src'); // Pobiera bazowy URL obrazka z atrybutu 'data-src' elementu o id 'img'.
    const chargeFactor = 1 - Charge / 100; // Oblicza współczynnik rabatu na podstawie zmiennej 'Charge' (zakłada się, że jest zdefiniowana globalnie).

    $('#img').attr('src', imgSrcBase + shipID + '.gif'); // Ustawia atrybut 'src' obrazka, dodając do bazowego URL ID statku.
    $('#metal').text(NumberGetHumanReadable(CostInfo[shipID][2][901] * chargeFactor));
    // Pobiera koszt metalu dla danego statku z obiektu 'CostInfo', stosuje rabat i formatuje liczbę do czytelnej formy.
    $('#crystal').text(NumberGetHumanReadable(CostInfo[shipID][2][902] * chargeFactor));
    // Pobiera koszt kryształu, stosuje rabat i formatuje.
    $('#deuterium').text(NumberGetHumanReadable(CostInfo[shipID][2][903] * chargeFactor));
    // Pobiera koszt deuteru, stosuje rabat i formatuje.
    $('#darkmatter').text(NumberGetHumanReadable(CostInfo[shipID][2][921] * chargeFactor));
    // Pobiera koszt ciemnej materii, stosuje rabat i formatuje.
    $('#traderHead').text(CostInfo[shipID][1]);
    // Ustawia tekst elementu 'traderHead' na nazwę statku (zakładając, że to nazwa).
    Reset(); // Resetuje pola z liczbą statków i ich łącznym kosztem.
}

function MaxShips() {
    // Funkcja ustawiająca maksymalną możliwą liczbę statków do zbudowania.
    const shipID = $('#shipID').val(); // Pobiera ID wybranego statku.
    $('#count').val(CostInfo[shipID][0]); // Ustawia wartość pola 'count' na maksymalną liczbę statków (zakładając, że jest przechowywana w CostInfo).
    Total(); // Aktualizuje łączny koszt dla maksymalnej liczby statków.
}

function Total() {
    // Funkcja obliczająca i wyświetlająca łączny koszt budowy podanej liczby statków.
    let Count = $('#count').val(); // Pobiera liczbę statków z pola 'count'.

    if (isNaN(Count) || Count < 0) {
        // Sprawdza, czy pobrana wartość nie jest liczbą lub jest mniejsza od zera.
        $('#count').val(0); // Jeśli tak, resetuje wartość pola 'count' do 0.
        Count = 0; // Ustawia zmienną Count na 0.
    }

    const shipID = $('#shipID').val(); // Ponownie pobiera ID wybranego statku.
    const chargeFactor = 1 - Charge / 100; // Ponownie oblicza współczynnik rabatu.
    const metalCost = CostInfo[shipID][2][901];
    const crystalCost = CostInfo[shipID][2][902];
    const deuteriumCost = CostInfo[shipID][2][903];
    const darkMatterCost = CostInfo[shipID][2][921];

    $('#total_metal').text(NumberGetHumanReadable(metalCost * Count * chargeFactor));
    // Oblicza łączny koszt metalu, stosuje rabat i formatuje.
    $('#total_crystal').text(NumberGetHumanReadable(crystalCost * Count * chargeFactor));
    // Oblicza łączny koszt kryształu, stosuje rabat i formatuje.
    $('#total_deuterium').text(NumberGetHumanReadable(deuteriumCost * Count * chargeFactor));
    // Oblicza łączny koszt deuteru, stosuje rabat i formatuje.
    $('#total_darkmatter').text(NumberGetHumanReadable(darkMatterCost * Count * chargeFactor));
    // Oblicza łączny koszt ciemnej materii, stosuje rabat i formatuje.
}

function Reset() {
    // Funkcja resetująca pola z liczbą statków i ich łącznym kosztem do wartości zerowych.
    $('#count').val(0);
    $('#total_metal').text(0);
    $('#total_crystal').text(0);
    $('#total_deuterium').text(0);
    $('#total_darkmatter').text(0);
}

$(document).ready(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    updateVars(); // Wywołuje funkcję updateVars, aby zainicjalizować informacje o statku przy załadowaniu strony.
});

// ULEPSZENIA (SUGESTIE):

// 1. Lepsze zarządzanie zmiennymi: W funkcjach 'Total' i 'updateVars', zmienna 'shipID' jest pobierana za każdym razem z DOM. Można ją pobrać raz na początku funkcji i przypisać do stałej. Podobnie 'chargeFactor'. Zrobiono to w powyższym kodzie.

// 2. Unikanie powtórzeń: W funkcji 'Total', koszty surowców dla danego 'shipID' są pobierane bezpośrednio wewnątrz wywołań $('#total_...').text(). Można je przypisać do zmiennych dla lepszej czytelności i potencjalnej optymalizacji (zrobiono to).

// 3. Łańcuchowanie metod jQuery: Tam gdzie to możliwe, można łączyć wywołania metod jQuery dla bardziej zwięzłego kodu (np. w funkcji 'Reset').

// 4. Bezpieczeństwo typów (TypeScript): W większych projektach TypeScript mógłby pomóc w zapewnieniu spójności typów danych, szczególnie przy dostępie do 'CostInfo' i zmiennej 'Charge'.

// 5. Obsługa błędów: W przypadku, gdy 'shipID' nie istnieje w 'CostInfo', kod może generować błędy. Warto dodać sprawdzenie, czy 'CostInfo[shipID]' jest zdefiniowane przed próbą dostępu do jego właściwości.

// 6. Wydajność (przy częstych zmianach): Jeśli użytkownik często zmienia wartość w polu '#count', funkcja 'Total' jest wywoływana za każdym razem. Można rozważyć dodanie opóźnienia (debounce) do wywoływania 'Total', aby nie aktualizować kosztów przy każdej zmianie, ale dopiero po krótkiej przerwie w pisaniu.

// ZASTOSOWANE ULEPSZENIA:

// - Lepsze zarządzanie zmiennymi 'shipID' i 'chargeFactor' wewnątrz funkcji.
// - Przypisanie kosztów surowców do zmiennych w funkcji 'Total' dla lepszej czytelności.

// Pozostałe sugestie można rozważyć w zależności od specyficznych potrzeb i złożoności projektu.
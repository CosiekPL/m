function add() {
    // Funkcja zmieniająca atrybuty formularza i wysyłająca go.
    const $form = $("#form");
    $form.attr('action', 'game.php?page=battleSimulator&action=moreslots'); // Ustawia atrybut 'action' formularza.
    $form.attr('method', 'POST'); // Ustawia metodę wysyłki formularza na POST.
    $form.submit(); // Wysyła formularz.
    return true; // Zwraca true po wysłaniu formularza.
}

async function check() {
    // Funkcja otwierająca nowe okno i wysyłająca dane formularza AJAX-em (używa async/await).
    const kb = window.open('about:blank', 'kb', 'scrollbars=yes,statusbar=no,toolbar=no,location=no,directories=no,resizable=no,menubar=no,width=' + screen.width + ',height=' + screen.height + ', screenX=0, screenY=0, top=0, left=0');
    // Otwiera nowe, puste okno przeglądarki o określonych parametrach.

    const $submit = $("#submit:visible");
    const $wait = $("#wait:hidden");
    const $form = $("#form");

    $submit.hide();
    $wait.show();

    try {
        const response = await fetch('game.php?page=battleSimulator&mode=send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: $form.serialize()
        });

        if (!response.ok) {
            throw new Error(`Błąd HTTP: ${response.status}`);
        }

        const data = await response.json();
        kb.focus();
        kb.location.href = 'CombatReport.php?raport=' + data;

    } catch (error) {
        console.error('Wystąpił błąd:', error);
        kb.window.close();
        if (typeof Dialog !== 'undefined' && Dialog.alert) {
            Dialog.alert('Wystąpił błąd: ' + error.message);
        } else {
            alert('Wystąpił błąd: ' + error.message);
        }
    } finally {
        // Użycie finally do zapewnienia, że UI zostanie zaktualizowane niezależnie od wyniku.
        setTimeout(() => $submit.show(), 10000);
        setTimeout(() => $wait.hide(), 10000);
    }

    return true;
}

$(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    const $tabsElement = $("#tabs");
    $tabsElement.tabs({
        tabTemplate: '<li><a href="#{href}">#{label}</a></li>',
        // Definiuje szablon HTML dla nowej zakładki.
    });

    $(document).on('click', '.reset', function(e) {
        e.preventDefault(); // Zapobiega domyślnej akcji elementu (np. przejście do linku).

        const index = $(this).parent().index();
        // Pobiera indeks (liczony od zera) rodzica (prawdopodobnie <li>) klikniętego elementu 'reset'.

        $(this).parent().parent().nextAll().each(function() {
            // Dla wszystkich kolejnych elementów rodzeństwa rodzica klikniętego 'reset'...
            $(this).children('td:eq(' + index + ')').children().val(0);
            // ...znajduje komórkę tabeli (<td>) o tym samym indeksie co rodzic 'reset' i ustawia wartość znajdującego się w niej elementu (prawdopodobnie input) na 0.
        });
        return false; // Zatrzymuje dalsze propagowanie zdarzenia.
    });
});

// Ulepszenia w tym kodzie:

// 1. Zastąpiono '$.post' i callbacki API Fetch z 'async/await' dla lepszej czytelności i obsługi błędów asynchronicznych.
// 2. Dodano obsługę błędów Fetch (sprawdzenie 'response.ok').
// 3. Użyto bloku 'try...catch...finally' do zarządzania asynchroniczną operacją i zapewnienia aktualizacji UI.
// 4. Uproszczono ukrywanie i pokazywanie elementów UI za pomocą '.hide()' i '.show()'.
// 5. Zachowano delegację zdarzeń '.on()' dla elementów '.reset'.
// 6. Przypisano często używane selektory jQuery do stałych wewnątrz funkcji dla lepszej wydajności.

// Dalsze potencjalne ulepszenia (zależne od kontekstu i wymagań):

// - Lepszy system powiadomień użytkownika zamiast prostego 'alert' lub 'Dialog.alert'.
// - Zarządzanie stanem aplikacji, szczególnie jeśli 'check' jest częścią bardziej złożonego przepływu.
// - Walidacja formularza przed wysłaniem.
// - Możliwość przerwania żądania AJAX.
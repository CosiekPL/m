function submitftp() {
    // Funkcja wysyłająca dane formularza FTP i obsługująca odpowiedź AJAX GET.
    const langCode = location.search.split('&')[1]?.substr(-2); // Pobiera kod języka z drugiego parametru URL (jeśli istnieje). Użyto opcjonalnego łańcuchowania.
    const ftpData = $('#ftp').serialize(); // Serializuje dane formularza o id 'ftp'.

    $.get(`?mode=ajax&action=ftp&lang=${langCode}&${ftpData}`, function(data) {
        // Wykonuje żądanie HTTP GET na podany URL, przekazując kod języka i dane formularza FTP.
        if (data === "") {
            // Jeśli odpowiedź z serwera jest pusta.
            document.location.reload(); // Przeładowuje bieżącą stronę.
        } else {
            // Jeśli odpowiedź zawiera dane (prawdopodobnie komunikat o błędzie).
            Dialog.alert(data); // Wyświetla komunikat w oknie dialogowym (zakłada się, że obiekt 'Dialog' jest zdefiniowany).
        }
    });
}

function submitinstall() {
    // Funkcja wysyłająca dane formularza instalacji i obsługująca odpowiedź AJAX GET w formacie JSON.
    const langCode = location.search.split('&')[2]?.substr(-2); // Pobiera kod języka z trzeciego parametru URL (jeśli istnieje). Użyto opcjonalnego łańcuchowania.
    const installData = $('#install').serialize(); // Serializuje dane formularza o id 'install'.

    $.getJSON(`?mode=ajax&action=install&lang=${langCode}&${installData}`, function(data) {
        // Wykonuje żądanie HTTP GET w formacie JSON na podany URL, przekazując kod języka i dane formularza instalacji.
        alert(data.msg); // Wyświetla komunikat otrzymany z serwera w oknie alert.
        if (!data.error) {
            // Jeśli w odpowiedzi nie ma flagi błędu.
            document.location.href = `?mode=ins&page=2&lang=${langCode}`;
            // Przekierowuje przeglądarkę na kolejny krok instalacji, przekazując kod języka.
        }
    });
    return false; // Zapobiega domyślnej akcji formularza (przeładowaniu strony).
}

// ULEPSZENIA (SUGESTIE):

// 1. Bezpieczniejsze pobieranie kodu języka z URL: Użycie opcjonalnego łańcuchowania (`?.`) zapobiega błędom, gdy dany parametr URL nie istnieje. Można również dodać bardziej robustną logikę parsowania parametrów URL.

// 2. Lepsza obsługa błędów AJAX: W obu funkcjach warto dodać obsługę błędów żądania AJAX za pomocą metody `.fail()`, aby poinformować użytkownika o problemach z połączeniem.

// 3. Użycie 'const' dla zmiennych przechowujących dane formularza i kod języka.

// 4. W funkcji `submitinstall`, zamiast `alert(data.msg)`, można rozważyć bardziej przyjazny interfejs powiadomień.

// 5. W funkcji `submitftp`, sprawdzenie `data == ""` jest dość proste. Można sprawdzić kod statusu odpowiedzi HTTP dla bardziej wiarygodnej informacji o sukcesie.

// ZASTOSOWANE ULEPSZENIA:

// - Użyto opcjonalnego łańcuchowania (`?.`) przy pobieraniu kodu języka z URL w obu funkcjach.
// - Dodano komentarze wyjaśniające działanie kodu.

// Pozostałe sugestie można rozważyć w zależności od potrzeb i złożoności projektu.
$(function() {
	// Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
	$(".processbar").css("width", "1px"); // Ustawia początkową szerokość paska postępu na 1 piksel.
	$(".info").text("0%"); // Ustawia początkowy tekst informujący o postępie na "0%".
	$.getJSON("?page=vertify&action=getFileList&"+document.location.search.split("&").pop(), startCompare);
	// Wykonuje żądanie HTTP GET w formacie JSON do podanego URL-a.
	// URL zawiera parametry 'page=vertify', 'action=getFileList' oraz ostatni parametr z ciągu zapytania URL bieżącej strony.
	// Po pomyślnym pobraniu danych JSON, wywołuje funkcję 'startCompare' przekazując jej otrzymaną listę plików.
});

function startCompare(Filelist) {
	// Funkcja rozpoczynająca proces porównywania plików.
	$('#result > td > div').empty(); // Czyści zawartość elementu div wewnątrz tabeli o id 'result'.
	compareFiles(Filelist, 0); // Wywołuje funkcję 'compareFiles' rozpoczynając od pierwszego elementu listy (indeks 0).
}

function compareFiles(Filelist, Pointer) {
	// Funkcja rekurencyjnie porównująca pliki z listy.
	if(typeof Filelist[++Pointer] === "undefined")
		return; // Jeśli następny element na liście plików nie istnieje (doszliśmy do końca listy), kończy działanie funkcji.
		// Zauważ, że '++Pointer' najpierw zwiększa wartość 'Pointer', a potem jej używa.

	var File	= Filelist[Pointer]; // Pobiera nazwę pliku z listy na podstawie aktualnego wskaźnika.
	var ELE		= $("<div />").text("Plik: "+File).appendTo('#result > td > div');
	// Tworzy nowy element div z nazwą pliku i dodaje go na końcu elementu div wewnątrz tabeli o id 'result'.
	$("#result > td > div").scrollTop($("#result > td > div").scrollTop() + 14);
	// Przewija kontener z wynikami o 14 pikseli w dół, aby nowy element był widoczny.
	$.ajax({
		url: "?page=vertify&action=check&file="+File, // URL do żądania AJAX, sprawdzający konkretny plik.
		success: function(TEXT) {
			// Funkcja wywoływana po pomyślnym otrzymaniu odpowiedzi z serwera.
			$(".processbar").css("width", (((Pointer + 1) / Filelist.length) * 100)+"%");
			// Aktualizuje szerokość paska postępu na podstawie liczby przetworzonych plików.
			$(".info").text(Math.ceil(((Pointer + 1) / Filelist.length) * 100)+"%");
			// Aktualizuje tekst informujący o postępie (zaokrąglony do najbliższej liczby całkowitej).
			if(TEXT == 1) {
				// Jeśli odpowiedź serwera to '1' (plik OK).
				ELE.css("background-color", "green"); // Ustawia tło elementu div na zielony.
				$("#fileok").text(function(i, old) {
					return parseInt(old) + 1; // Zwiększa licznik poprawnych plików.
				});
			} else if(TEXT == 2) {
				// Jeśli odpowiedź serwera to '2' (plik uszkodzony/zmieniony).
				ELE.css("background-color", "red"); // Ustawia tło elementu div na czerwony.
				$("#filefail").text(function(i, old) {
					return parseInt(old) + 1; // Zwiększa licznik uszkodzonych plików.
				});
			} else if(TEXT == 3) {
				// Jeśli odpowiedź serwera to '3' (błąd podczas sprawdzania pliku).
				ELE.css("background-color", "orange"); // Ustawia tło elementu div na pomarańczowy.
				$("#fileerror").text(function(i, old) {
					return parseInt(old) + 1; // Zwiększa licznik błędów.
				});
			} else if(TEXT == 4) {
				// Jeśli odpowiedź serwera to '4' (nowy plik, nieznany).
				ELE.css("background-color", "grey"); // Ustawia tło elementu div na szary.
				ELE.css("color", "black"); // Ustawia kolor tekstu na czarny.
				$("#filenew").text(function(i, old) {
					return parseInt(old) + 1; // Zwiększa licznik nowych plików.
				});
			}
			compareFiles(Filelist, Pointer); // Rekurencyjnie wywołuje funkcję 'compareFiles' dla następnego pliku.
		}
	});
}
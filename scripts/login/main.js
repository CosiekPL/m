$(function() {
	// Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
	$('.flags').on('click', function(e) {
		// Obsługa kliknięcia na elementy z klasą 'flags' (prawdopodobnie ikony flag).
		e.preventDefault(); // Zapobiega domyślnej akcji elementu (np. przejście do linku).
		var langKey = $(this).attr('class').replace(/flags(.*)/, "$1").trim();
		// Pobiera atrybut 'class' klikniętego elementu, usuwa z niego 'flags' i usuwa białe znaki.
		Login.setLanguage(langKey); // Wywołuje funkcję ustawiającą język.
		return false; // Zatrzymuje dalsze propagowanie zdarzenia.
	});

	$('.fancybox').fancybox({
		// Inicjalizuje plugin Fancybox dla elementów z klasą 'fancybox' (okienka modalne).
		'type' : 'iframe', // Typ zawartości wyświetlanej w Fancyboxie to ramka iframe.
		'padding' : 1, // Ustawia wewnętrzny odstęp w oknie Fancyboxa na 1 piksel.
	});

	if(LoginConfig.isMultiUniverse)
	{
		// Sprawdza, czy w konfiguracji logowania włączona jest obsługa wielu uniwersów.
		$('.changeAction')
		.each(function() {
			// Dla każdego elementu z klasą 'changeAction' (prawdopodobnie select z wyborem uniwersum)...
			updateUrls($(this)); // ...wywołuje funkcję aktualizującą adresy URL.
		})
		.on('change', function() {
			// Dodaje nasłuchiwacz zdarzeń 'change' do tych elementów.
			updateUrls($(this)); // Po zmianie wartości, ponownie aktualizuje adresy URL.
		});

		// $('.changeUni').on('change', function() {
		// 	document.location.href = LoginConfig.basePath+'uni'+$(this).val()+'/index.php'+document.location.search;
		// });
		// Zakomentowany kod, który prawdopodobnie bezpośrednio zmieniał adres URL po zmianie uniwersum.
	}
	else
	{
		// Jeśli obsługa wielu uniwersów jest wyłączona...
		$('.fb_login').attr('href', function(i, old) {
			// ...dla każdego elementu z klasą 'fb_login' (link do logowania przez Facebooka)...
			return LoginConfig.basePath+$(this).data('href'); // ...ustawia atrybut 'href' łącząc bazowy URL z danymi z atrybutu 'data-href'.
		});
	}
});

var updateUrls = function(that, universe) {
	// Funkcja aktualizująca adresy URL formularzy i linków logowania w zależności od wybranego uniwersum.
	var universe = that.val(); // Pobiera wartość wybraną w elemencie (uniwersum).
	if (LoginConfig.unisWildcast) {
		// Sprawdza, czy konfiguracja używa subdomen do rozróżniania uniwersów.
		var basePathWithSubdomain = LoginConfig.basePath.replace('://', '://uni' + universe + '.');
		// Tworzy bazowy URL z odpowiednią subdomeną.
		that.parents('form').attr('action', function(i, old) {
			// Dla formularza nadrzędnego, ustawia atrybut 'action' (gdzie dane z formularza są wysyłane)...
			return basePathWithSubdomain+$(this).data('action'); // ...łącząc bazowy URL z subdomeną i danymi z atrybutu 'data-action'.
		});
		$('.fb_login').attr('href', function(i, old) {
			// Dla linków logowania Facebooka, ustawia atrybut 'href'...
			return basePathWithSubdomain+$(this).data('href'); // ...łącząc bazowy URL z subdomeną i danymi z atrybutu 'data-href'.
		});
	} else {
		// Jeśli konfiguracja używa segmentów ścieżki URL do rozróżniania uniwersów.
		that.parents('form').attr('action', function(i, old) {
			// Dla formularza nadrzędnego, ustawia atrybut 'action'...
			return LoginConfig.basePath+'uni'+universe+'/'+$(this).data('action'); // ...łącząc bazowy URL, '/uni/', wybrane uniwersum i dane z atrybutu 'data-action'.
		});
		$('.fb_login').attr('href', function(i, old) {
			// Dla linków logowania Facebooka, ustawia atrybut 'href'...
			return LoginConfig.basePath+'uni'+universe+'/'+$(this).data('href'); // ...łącząc bazowy URL, '/uni/', wybrane uniwersum i dane z atrybutu 'data-href'.
		});
	}
}

var Login = {
	// Obiekt zawierający funkcje związane z logowaniem.
	setLanguage : function (LNG, Query) {
		// Funkcja ustawiająca język.
		$.cookie('lang', LNG); // Ustawia ciasteczko 'lang' z wybranym językiem (wymaga pluginu jQuery Cookie).
		if(typeof Query === "undefined")
			document.location.href = document.location.href // Przeładowuje bieżącą stronę.
		else
			document.location.href = document.location.href+Query; // Przeładowuje stronę, dodając opcjonalny ciąg zapytania URL.
	}
};
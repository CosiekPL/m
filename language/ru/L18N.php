<?php



// Translation into Russian - Copyright © 2010-2013 InquisitorEA <support@moon-hunt.ru>

setlocale(LC_ALL, 'ru_RU.UTF8', 'russian'); // http://msdn.microsoft.com/en-us/library/39cwe7zf%28vs.71%29.aspx
setlocale(LC_NUMERIC, 'C');

// Дата и время
$LNG['dir']            = 'ltr';
$LNG['week_day']       = array('Вск', 'Пнд', 'Втр', 'Срд', 'Чтв', 'Птн', 'Сбт');
$LNG['months']         = array('Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
$LNG['js_tdformat']    = '[M] [D] [d] [H]:[i]:[s]';
$LNG['php_timeformat'] = 'H:i:s';
$LNG['php_dateformat'] = 'd. M Y';
$LNG['php_tdformat']   = 'd. M Y, H:i:s';
$LNG['short_day']      = 'd';
$LNG['short_hour']     = 'h';
$LNG['short_minute']   = 'm';
$LNG['short_second']   = 's';

$LNG['timezones'] = array(
	'-12'	=> '[UTC − 12] Меридиан смены дат (запад)',
	'-11'	=> '[UTC − 11] о. Мидуэй, Самоа',
	'-10'	=> '[UTC − 10] Гавайи',
	'-9.5'	=> '[UTC − 9:30] Маркизские острова',
	'-9'	=> '[UTC − 9] Аляска',
	'-8'	=> '[UTC − 8] Тихоокеанское время (США и Канада) и Тихуана',
	'-7'	=> '[UTC − 7] Аризона',
	'-6'	=> '[UTC − 6] Мехико, Центральная Америка, Центральное время (США и Канада)',
	'-5'	=> '[UTC − 5] Индиана (восток), Восточное время (США и Канада)',
	'-4.5'	=> '[UTC − 4:30] Венесуэла',
	'-4'	=> '[UTC − 4] Сантьяго, Атлантическое время (Канада)',
	'-3.5'	=> '[UTC − 3:30] Ньюфаундленд',
	'-3'	=> '[UTC − 3] Бразилия, Гренландия',
	'-2'	=> '[UTC − 2] Среднеатлантическое время',
	'-1'	=> '[UTC − 1] Азорские острова, острова Зелёного мыса',
	'0'		=> '[UTC] Время по Гринвичу: Дублин, Лондон, Лиссабон, Эдинбург',
	'1'		=> '[UTC + 1] Берлин, Мадрид, Париж, Рим, Западная Центральная Африка',
	'2'		=> '[UTC + 2] Афины, Вильнюс, Киев, Минск, Рига, Таллин, Центральная Африка',
	'3'		=> '[UTC + 3] Волгоград, Москва, Самара, Санкт-Петербург',
	'3.5'	=> '[UTC + 3:30] Тегеран',
	'4'		=> '[UTC + 4] Баку, Ереван, Тбилиси',
	'4.5'	=> '[UTC + 4:30] Кабул',
	'5'		=> '[UTC + 5] Екатеринбург, Исламабад, Карачи, Оренбург, Ташкент',
	'5.5'	=> '[UTC + 5:30] Бомбей, Калькутта, Мадрас, Нью-Дели',
	'5.75'	=> '[UTC + 5:45] Катманду',
	'6'		=> '[UTC + 6] Алматы, Астана, Новосибирск, Омск',
	'6.5'	=> '[UTC + 6:30] Рангун',
	'7'		=> '[UTC + 7] Бангкок, Красноярск',
	'8'		=> '[UTC + 8] Гонконг, Иркутск, Пекин, Сингапур',
	'8.75'	=> '[UTC + 8:45] Юго-восточная Западная Австралия',
	'9'		=> '[UTC + 9] Токио, Сеул, Чита, Якутск',
	'9.5'	=> '[UTC + 9:30] Дарвин',
	'10'	=> '[UTC + 10] Владивосток, Канберра, Мельбурн, Сидней',
	'10.5'	=> '[UTC + 10:30] Лорд-Хау',
	'11'	=> '[UTC + 11] Камчатка, Магадан, Сахалин, Соломоновы о-ва',
	'11.5'	=> '[UTC + 11:30] Остров Норфолк',
	'12'	=> '[UTC + 12] Новая Зеландия, Фиджи',
	'12.75'	=> '[UTC + 12:45] Острова Чатем',
	'13'	=> '[UTC + 13] Острова Феникс, Тонга',
	'14'	=> '[UTC + 14] Остров Лайн',
);
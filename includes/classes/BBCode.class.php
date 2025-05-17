<?php



class BBCode
{
	static public function parse($sText)
	{
		if (!isset($sText)) {
			$sText = '';
		}
		$sText = str_replace("\r\n", "\n", $sText);
		$sText = str_replace("\r", "\n", $sText);
		$sText = str_replace("\n", '<br />', $sText);

	    	$config = parse_ini_file('BBCodeParser2.ini', true);

				$options = $config['HTML_BBCodeParser2'];


				$parser = new HTML_BBCodeParser2($options);

	    	$parser->setText($sText);

				$parser->parse();

	    	return $parser->getParsed();
	}
}
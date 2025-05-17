<?php

/**
 *  2Moons 
 *   by Jan-Otto Kröpke 2009-2016
 *
 * For the full copyright and license information, please view the LICENSE
 *
 * @package 2Moons
 * @author Jan-Otto Kröpke <slaver7@gmail.com>
 * @copyright 2009 Lucky
 * @copyright 2016 Jan-Otto Kröpke <slaver7@gmail.com>
 * @licence MIT
 * @version 1.8.0
 * @link https://github.com/jkroepke/2Moons
 */

declare(strict_types=1);

class ShowOverviewPage extends AbstractGamePage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
	}
	
	private function GetTeamspeakData(): array
	{
		global $LNG;
		
		$config = Config::get();
		
		if ($config->ts_modon == 0)
		{
			return [];
		}
		
		Cache::get()->add('teamspeak', 'TeamspeakBuildCache');
		$tsInfo	= Cache::get()->getData('teamspeak', false);
		
		if(empty($tsInfo))
		{
			return [
				'error'	=> $LNG['ov_teamspeak_not_online']
			];
		}
		
		$url = sprintf($LNG['ov_teamspeak_connect'], $config->ts_server, $config->ts_tcpport, $config->ts_udpport, $tsInfo['password']);
		
		return [
			'url'      => $url,
			'current'  => $tsInfo['current'],
			'max'      => $tsInfo['maxuser'],
			'error'    => false,
		];
	}

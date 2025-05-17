<?php


 
class Flash
{
	private $key;
	private $sessionState = false;

 	public static function setFlash($key, $value)
	{
		setcookie($key, $value);
		return true;
	} 

	public static function getFlash($key)
	{
		$msg = $_COOKIE[$key];
		unset($_COOKIE[$key]);
		return $msg;
	} 

	public static function createToken()
	{
		$token = md5(uniqid(rand(),true));
      	setcookie('token', $token);
        return $token;
	}

	public static function getToken()
	{
		if(isset($_COOKIE) && isset($_COOKIE['token']))
		{
			$msg = $_COOKIE['token'];
			unset($_COOKIE['token']);
			return $msg;
		}
		return false;
	}
}
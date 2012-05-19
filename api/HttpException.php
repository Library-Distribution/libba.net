<?php
	class HttpException extends Exception
	{
		public $code;
		public $headers;

		public function __construct($code, $headers = NULL)
		{
			$this->code = $code;
			$this->headers = $headers;
			parent::__construct(HttpException::getStatusMessage($code), $code);
		}

		public static function getStatusMessage($code)
		{
			switch ($code)
			{
				case 200: return "OK";
				case 400: return "Bad request";
				case 405: return "Method not allowed";
				case 406: return "Not Acceptable";
				case 500: return "Internal Server Error";
				default: return "";
			}
		}
	}
?>
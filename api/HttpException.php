<?php
	class HttpException extends Exception
	{
		private $headers;

		public function __construct($code, $headers = NULL, $message = NULL)
		{
			$this->code = $code;
			$this->headers = $headers;
			parent::__construct(($message ? $message . " - " : "") . HttpException::getStatusMessage($code), $code);
		}

		public function getHeaders()
		{
			return $this->headers;
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
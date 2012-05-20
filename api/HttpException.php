<?php
	class HttpException extends Exception
	{
		private $code;
		private $headers;

		public function __construct($code, $headers = NULL)
		{
			$this->code = $code;
			$this->headers = $headers;
			parent::__construct(HttpException::getStatusMessage($code), $code);
		}

		public function getCode()
		{
			return $this->code;
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
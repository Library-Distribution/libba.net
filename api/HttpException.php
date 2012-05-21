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
				case 401: return "Unauthorized";
				case 403: return "Forbidden";
				case 404: return "Not Found";
				case 405: return "Method not allowed";
				case 406: return "Not Acceptable";
				case 500: return "Internal Server Error";
				case 501: return "Not Implemented";
				case 503: return "Service Unavailable";
				default: return "";
			}
		}
	}
?>
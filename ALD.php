<?php
	require_once("api/HttpException.php");

	class ALD
	{
		private $server;

		public function __construct($server)
		{
			$this->server = $server;
		}

		public function getUserList( $start = 0, $count = "all" )
		{
			return json_decode( $this->Request( CURLOPT_HTTPGET, "/users/list.php?start=$start&count=count", array("Accept: application/json") ) );
		}

		public function getUser( $name, $request_user = NULL, $request_password = NULL )
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET, "/users/describe.php?name=$name", array("Accept: application/json"), NULL, $request_user, $request_password) );
		}

		public function getItemById( $id )
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET, "/items/describe.php?id=$id", array("Accept: application/json") ) );
		}

		public function getItem($name, $version)
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET, "/items/describe.php?name=$name &version=$version", array("Accept: application/json") ) );
		}

		public function getItemList($start = 0, $count = "all", $type = NULL, $user = NULL, $name = NULL, $tags = NULL, $latest = false)
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET
										, "/items/list.php?start=$start&count=$count&latest=$latest"
											. ( $type != NULL ? "&type=$type" : "" )
											. ( $user != NULL ? "&user=$user" : "" )
											. ( $name != NULL ? "&name=$name" : "" )
											. ( $tags != NULL ? "&tags=" . implode("|", $tags) : "" )
										, array("Accept: application/json") ) );
		}

		public function uploadItem( $file, $user, $password )
		{
			return json_decode( $this->_Request( CURLOPT_POST, "/items/add.php", array("Accept: application/json"), array("package" => "@$file"), $user, $password) )->id;
		}

		private function _Request($method, $url, $header, $data = NULL, $user = NULL, $password = NULL)
		{
			$conn = curl_init();

			curl_setopt($conn, CURLOPT_URL, $this->server . $url); # URL
			curl_setopt($conn, $method, true); # POST/GET to the URL
			curl_setopt($conn, CURLOPT_RETURNTRANSFER, true); # return data, do not directly print it
			curl_setopt($conn, CURLOPT_HTTPHEADER, $header); # custom headers

			if (is_array($data))
			{
				curl_setopt($conn, CURLOPT_POSTFIELDS, $data); # data to upload (@ for files)
			}

			if ($user != NULL && $password != NULL)
			{
				curl_setopt($conn, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); # use HTTP BASIC Authentication
				curl_setopt($conn, CURLOPT_USERPWD, "$user:$password"); # set auth data
			}

			$response = curl_exec($conn);
			$code = curl_getinfo($conn, CURLINFO_HTTP_CODE);
			curl_close($conn);

			if ($code == 200)
			{
				return $response;
			}
			throw new HttpException($code, NULL, $response);
		}
	}
?>
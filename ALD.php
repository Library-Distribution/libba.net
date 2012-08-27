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
			return json_decode( $this->_Request( CURLOPT_HTTPGET, "/users/list?start=$start&count=$count", array("Accept: application/json") ), true );
		}

		public function getUser( $name, $request_user = NULL, $request_password = NULL )
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET, "/users/describe/$name", array("Accept: application/json"), NULL, $request_user, $request_password), true );
		}

		public function getUserById( $id, $request_user = NULL, $request_password = NULL )
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET, "/users/describe/$id", array("Accept: application/json"), NULL, $request_user, $request_password), true );
		}

		public function modifyUser( $name, $password, $new_name = NULL, $new_mail = NULL, $new_password = NULL)
		{
			$data = array();

			$new_name != NULL && $data["name"] = $new_name;
			$new_mail != NULL && $data["mail"] = $new_mail;
			$new_password != NULL && $data["password"] = $new_password;

			$this->_Request( CURLOPT_HTTPGET, "/users/modify/$name", array(), $data, $name, $password );
		}

		public function getItemById( $id )
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET, "/items/describe/$id", array("Accept: application/json") ), true );
		}

		public function getItem($name, $version)
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET, "/items/describe/$name/$version", array("Accept: application/json") ), true );
		}

		public function getItemList($start = 0, $count = "all", $type = NULL, $user = NULL, $name = NULL, $tags = NULL, $version = NULL, $stdlib = "both", $reviewed = "yes")
		{
			return json_decode( $this->_Request( CURLOPT_HTTPGET
										, "/items/list?start=$start&count=$count&stdlib=$stdlib&reviewed=$reviewed"
											. ( $version != NULL ? "&version=$version" : "" )
											. ( $type != NULL ? "&type=$type" : "" )
											. ( $user != NULL ? "&user=$user" : "" )
											. ( $name != NULL ? "&name=$name" : "" )
											. ( $tags != NULL ? "&tags=" . implode("|", $tags) : "" )
										, array("Accept: application/json") ), true );
		}

		public function uploadItem( $file, $user, $password )
		{
			return json_decode( $this->_Request( CURLOPT_POST, "/items/add", array("Accept: application/json"), array("package" => "@$file"), $user, $password) )->id;
		}

		public function modifyItemById( $id, $request_user, $request_password, $reviewed = NULL, $default = NULL, $user = NULL )
		{
			$data = array();

			$reviewed != NULL && $data["reviewed"] = $reviewed;
			$default != NULL && $data["default"] = $default;
			$user != NULL && $data["user"] = $user;

			$this->_Request( CURLOPT_HTTPGET, "/items/modify/$id", array("Accept: application/json"), $data, $request_user, $request_password );
		}

		public function initRegistration( $name, $mail, $password, $template )
		{
			$this->_Request( CURLOPT_POST, "/users/register", NULL, array("name" => $name, "mail" => $mail, "password" => $password, "template" => $template) );
		}

		public function completeRegistration( $id, $token )
		{
			$this->_Request( CURLOPT_POST, "/users/register/verify/$id", NULL, array("token" => $token) );
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

			if ($code >= 200 && $code < 300)
			{
				return $response;
			}
			throw new HttpException($code, NULL, $response);
		}
	}
?>
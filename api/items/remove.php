<?php
	require_once("../HttpException.php");
	require_once("../db.php");
	require_once("../util.php");

	try
	{
		$request_method = strtoupper($_SERVER["REQUEST_METHOD"]);

		if ($request_method == "DELETE")
		{
			# authentication
			user_basic_auth("Restricted API");
			$user = $_SERVER["PHP_AUTH_USER"];

			if (isset($_GET["id"]))
			{
				throw new HttpException(501);
			}
			else
			{
				throw new HttpException(400);
			}
		}
		else
		{
			throw new HttpException(405, array("Allow" => "DELETE"));
		}
	}
	catch (HttpException $e)
	{
		handleHttpException($e);
	}
?>
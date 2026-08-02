<?php

namespace App\Traits;
use Carbon\Carbon;

trait ApiResponser{

    public static function jsonResponse($statuscode, $status, $message, $data, $code=200)
	{
		date_default_timezone_set('Asia/Kolkata');
		return response()->json([
// 			'statuscode'=> $statuscode,
			'status'=> $status,
			'message' => $message,
			'data' => $data,
			'timestamp' => date('Y-m-d h:i:s'),
			'environment' => 'Live',
		], $code);
	}

	public static function prepaidResponse($statuscode, $status, $message, $data, $info, $code)
	{
		date_default_timezone_set('Asia/Kolkata');
		return response()->json([
			'statuscode'=> $statuscode,
			'status'=> $status,
			'message' => $message,
			'data' => $data,
			'info' => $info,
			'timestamp' => date('Y-m-d h:i:s'),
			'environment' => 'Live',
		], $code);
	}

}
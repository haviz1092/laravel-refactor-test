<?php

namespace App\Helpers;

class ApiFormatter
{
  protected static $response = [
    'success' => false,
    'message' => null,
    'data'    => null,
  ];

  public static function success($data = null, $message = null, $code = 200)
  {
    self::$response['success'] = true;
    self::$response['message'] = $message;
    self::$response['data']    = $data;

    return response()->json(self::$response, $code);
  }

  public static function error($message = null, $code = 400, $data = null)
  {
    self::$response['success'] = false;
    self::$response['message'] = $message;
    self::$response['data']    = $data;

    return response()->json(self::$response, $code);
  }
}

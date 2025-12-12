<?php

namespace App\Helpers;

class ApiFormatter
{
  protected static $response = [
    'success' => false,
    'message' => null,
    'data'    => null,
  ];

  protected static $errResponse = [
    'success' => false,
    'message' => null,
    'errors'   => null,
  ];

  public static function success($data = null, $message = null, $code = 200)
  {
    self::$response['success'] = true;
    self::$response['message'] = $message;
    self::$response['data']    = $data;

    return response()->json(self::$response, $code);
  }

  public static function error($message = null, $code = 500, $errors = null)
  {
    self::$errResponse['success'] = false;
    self::$errResponse['message'] = $message;
    self::$errResponse['errors']  = $errors;

    return response()->json(self::$errResponse, $code);
  }
}

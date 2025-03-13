<?php
namespace App\Helpers;

class ResponseFormatter {
    public static function success($data, $message = 'Success') {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public static function error($data, $message = 'Error', $code = 500) {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}
?>

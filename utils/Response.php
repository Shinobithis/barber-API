<?php
/**
 * Response Helper Class
 */

class Response {
    public static function json($data, $status_code = 200) {
        http_response_code($status_code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success($data, $status_code = 200, $message = 'Success') {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status_code);
    }

    public static function error($message = "An error occurred", $status_code = 400, $errors = null) {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status_code);
    }

    public static function unauthorized($message = "You are not authorized to access") {
        self::error($message, 401);
    }

    public static function notFound($message = "Resource not found") {
        self::error($message, 404);
    }

    public static function banned($message = "You are banned from accessing") {
        self::error($message, 403);
    }
}
<?php
/**
 * Authentication Middleware
 */

require_once __DIR__ . "/../config/Jwt.php";
require_once __DIR__ . "/../utils/Response.php";

class AuthMiddleware {
    public static function authenticate() {
        $token = JWTHelper::getTokenFromHeader();

        if (!$token) {
            return Response::unauthorized("Access token is required.");
        }

        $user_data = JWTHelper::validateToken($token);

        if (!$user_data) {
            return Response::unauthorized("Invalide or Expired token.");
        }

        return $user_data;
    }

    public static function requireUser() {
        $user_data = self::authenticate();

        if (!$user_data) {
            return Response::unauthorized();
        }
        return $user_data;
    }

    public static function requireBarber() {
        $user_data = self::authenticate();

        if ($user_data['role'] !== 'barber') {
            return Response::banned("Forbidden: You do not have the required permissions to access this resource.");
        }
        return $user_data;
    }

    public static function requireAdmin() {
        $user_data = self::authenticate();

        if ($user_data['role'] !== 'admin') {
            return Response::banned("Forbidden: You do not have the required permissions to access this resource.");
        }
        return $user_data;
    } 
}
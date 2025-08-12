<?php
/**
 * Validator Helper class
 */

class Validator {
    private $errors = [];

    public function required($field, $value, $message = null) {
        if (empty($field) && $value !== '0') {
            $this->errors[$field] = $message ?: ucfirst($field) . " is required";
        }
        return $this;
    }

    public function email($field, $value, $message = null) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?: " Invalid email format";
        }
        return $this;
    }

    public function minLength($field, $min, $value, $message = null) {
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$value] = $message ?: ucfirst($field) . " must be at least {$min} characters";
        }
        return $this;
    }

    public function maxLength($field, $max, $value, $message = null) {
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[$field] = $message ?: ucfirst($field) . "must be at least {$max} characters";
        }
        return $this;
    }

    public function exactLength($field, $length, $value, $message = null) {
        if (!empty($value) && strlen($value) != $length) {
            $this->errors[$field] = $message ?: ucfirst($field) . "must be {$length} characters";
        }
        return $this;
    }

    public function numeric($field, $value, $message = null) {
        if (!empty($value) && !is_numeric($value)) {
            return $this->errors[$field] = $message ?: ucfirst($field) . " must be numerical"; 
        }
        return $this;
    }

    public function string($field, $value, $message = null) {
        if (!empty($value) && !is_string($value)) {
            $this->errors[$field] = $message ?: ucfirst($field) . " must be a string";
        }
        return $this;
    }

    public function in($field, $value, $allowed, $message = null) {
        if (!empty($value) && !in_array($value, $allowed)) {
            $this->errors[$field] = $message ?: ucfirst($field) . " Must be one of: " . implode(',', $allowed);
        }
        return $this;
    }

    public function unique($table, $pdo, $value, $field, $column, $exclude_id = null, $message = null) {
        if (!empty($value)) {

            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            
            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->fetchColumn() > 0) {
                $this->errors[$field] = $message ?: ucfirst($field) . " Already Exists";
            }
        }
        return $this;
    }

    public function dateTime($field, $value, $format, $message = null) {
        if (!empty($value)) {
            $d = DateTime::createFromFormat($format, $value);

            if (!($d && $d->format($format) === $value)) {
                $this->errors[$field] = $message ?: "Invalid format for {$field}. Please use {$format}.";
            }
        }
        return $this;
    }

    public function hasErrors() {
        return !empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    public function addError($field, $message) {
        $this->errors[$field] = $message;
        return $this;
    }
}
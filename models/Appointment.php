<?php

use Dom\Element;

class Appointment {

    private $table = 'appointments';
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createAppointment($data) {
        $query = "INSERT INTO appointments (user_id, barber_id, service_id, date, start_time, status)
                VALUES (:user_id, :barber_id, :service_id, :date, :start_time, :status)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':barber_id', $data['barber_id']);
        $stmt->bindParam(':service_id', $data['service_id']);
        $stmt->bindParam(':date', $data['date']);
        $stmt->bindParam(':start_time', $data['start_time']);
        $stmt->bindParam(':status', $data['status']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function cancelAppointment($id) {
        $query = "UPDATE " . $this->table . " SET status = 'cancel' WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    public function readAppointment($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    public function editAppointment($id, $data) {
        $fiedls = [];
        $values = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['service_id', 'date', 'start_time'])) {
                $fiedls[] = "{$key} = :{$key}";
                $values[":{$key}"] = $value;
            }
        }

        if (empty($fiedls)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(", ", $fiedls). " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        return $stmt->execute($values);
    }
    public function checkAvailability($barber_id, $date, $start_time)
    {
        $query = "SELECT COUNT(*) FROM " . $this->table . "
                WHERE barber_id = :barber_id
                AND date = :date
                AND start_time = :start_time
                AND status != 'cancelled'";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':barber_id', $barber_id, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':start_time', $start_time);

        $stmt->execute();

        $count = $stmt->fetchColumn();

        return $count == 0;
    }
    public function findForReview($appointment_id, $user_id) {
        $query = "SELECT * FROM " . $this->table . " 
                WHERE id = :appointment_id 
                AND user_id = :user_id 
                AND status = 'completed' 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':appointment_id', $appointment_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
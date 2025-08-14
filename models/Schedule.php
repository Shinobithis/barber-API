<?php
class Schedule {
    private $conn;
    private $table = 'schedules';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Finds the full weekly schedule for a single professional.
     */
    public function findByProfessionalId($professional_id) {
        $query = "SELECT day_of_week, start_time, end_time 
                  FROM " . $this->table . " 
                  WHERE professional_id = :professional_id 
                  ORDER BY day_of_week, start_time ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':professional_id', $professional_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Deletes a professional's entire old schedule and inserts the new one.
     * This is wrapped in a transaction to ensure data integrity.
     */
    public function rebuildForProfessional($professional_id, $scheduleData) {
        $insertQuery = "INSERT INTO " . $this->table . " (professional_id, day_of_week, start_time, end_time) 
                        VALUES (:professional_id, :day_of_week, :start_time, :end_time)";

        try {
            $this->conn->beginTransaction();

            $deleteStmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE professional_id = :professional_id");
            $deleteStmt->bindParam(':professional_id', $professional_id, PDO::PARAM_INT);
            $deleteStmt->execute();

            $insertStmt = $this->conn->prepare($insertQuery);

            foreach ($scheduleData as $day => $timeBlocks) {
                if (is_array($timeBlocks)) {
                    foreach ($timeBlocks as $block) {
                        $insertStmt->execute([
                            ':professional_id' => $professional_id,
                            ':day_of_week' => $day,
                            ':start_time' => $block['start'],
                            ':end_time' => $block['end']
                        ]);
                    }
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Schedule rebuild failed: " . $e->getMessage());
            return false;
        }
    }
}
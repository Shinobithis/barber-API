<?php

class ScheduleController {
    private $scheduleModel;

    public function __construct(Schedule $scheduleModel) {
        $this->scheduleModel = $scheduleModel;
    }

    public function getMySchedule() {
        $professionalData = AuthMiddleware::requireProfessional();
        $schedule = $this->scheduleModel->findByProfessionalId($professionalData['id']);

        $structuredSchedule = [];
        
        $dayMap = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 0 => 'sunday'];
        foreach ($schedule as $block) {
            $dayName = $dayMap[$block['day_of_week']];
            $structuredSchedule[$dayName][] = [
                'start' => substr($block['start_time'], 0, 5),
                'end' => substr($block['end_time'], 0, 5)
            ];
        }

        Response::success(['schedule' => $structuredSchedule]);
    }

    public function updateMySchedule() {
        $professionalData = AuthMiddleware::requireProfessional();
        $data = json_decode(file_get_contents("php://input"), true);

        $validationErrors = $this->validateScheduleData($data);

        if (!empty($validationErrors)) {
            Response::error("Validation failed", 422, $validationErrors);
        }

        if ($this->scheduleModel->rebuildForProfessional($professionalData['id'], $data)) {
            Response::success(null, 200, "Schedule updated successfully.");
        } else {
            Response::error("Failed to update schedule.", 500);
        }
    }

    /**
     * A private helper method to validate the complex schedule data structure.
     * This keeps the main controller method clean.
     * @param array $scheduleData The raw schedule data from the request.
     * @return array An array of validation errors, or an empty array if valid.
     */
    private function validateScheduleData($scheduleData) {
        $errors = [];
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dayMap = ['monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0];

        if (!is_array($scheduleData)) {
            return ['schedule' => 'Invalid data format. Expected a JSON object.'];
        }

        foreach ($validDays as $day) {
            if (!isset($scheduleData[$day])) {
                $errors[$day] = "Schedule data for {$day} is missing.";
                continue;
            }

            $timeBlocks = $scheduleData[$day];
            if (!is_array($timeBlocks)) {
                $errors[$day] = "Schedule for {$day} must be an array of time blocks.";
                continue;
            }

            usort($timeBlocks, function($a, $b) {
                return strcmp($a['start'], $b['start']);
            });

            $lastEndTime = '00:00';

            foreach ($timeBlocks as $index => $block) {
                if (!isset($block['start']) || !isset($block['end'])) {
                    $errors["{$day}[{$index}]"] = "Time block is missing 'start' or 'end' time.";
                    continue;
                }

                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $block['start']) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $block['end'])) {
                    $errors["{$day}[{$index}]"] = "Invalid time format. Please use HH:MM.";
                    continue;
                }

                if ($block['start'] >= $block['end']) {
                    $errors["{$day}[{$index}]"] = "Start time must be before end time.";
                }

                if ($block['start'] < $lastEndTime) {
                    $errors["{$day}[{$index}]"] = "Time blocks for {$day} are overlapping.";
                }
                $lastEndTime = $block['end'];
            }
        }

        return $errors;
    }
}
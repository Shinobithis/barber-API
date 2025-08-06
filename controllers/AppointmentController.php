<?php

use JsonSchema\Uri\Retrievers\FileGetContents;
class AppointmentController {
    private $db;
    private $appointment;

    public function __construct(Appointment $appointment, $db) {
        $this->db = $db;
        $this->appointment = $appointment;
    }

    public function getAppointment($id) {
        $userData = AuthMiddleware::requireUser();

        $appointment = $this->appointment->readAppointment($id);

        if (!$appointment || $appointment['user_id'] != $userData['id']) {
            Response::notFound("Appointment not found.");
        }

        Response::success($appointment);
    }
    public function createAppointment() {
        $userData = AuthMiddleware::requireUser();

        $input = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator();
        $validator
            ->required('barber_id', $input['barber_id'] ?? null)
            ->required('service_id', $input['service_id'] ?? null)
            ->required('date', $input['date'] ?? null)
            ->required('start_time', $input['start_time'] ?? null)
            ->numeric('barber_id', $input['barber_id'] ?? null)
            ->numeric('service_id', $input['service_id'] ?? null)
            ->dateTime('date', $input['date'] ?? null, 'Y-m-d')
            ->dateTime('start_time', $input['start_time'] ?? null, 'H:i:s');

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $barbershopModel = new Barbershop($this->db);

        if (!$barbershopModel->findBarberById($input['barber_id'])) {
            Response::error("Validation failed", 422, ['barber_id' => 'Selected barber does not exist.']);
        }

        if (!$barbershopModel->isServiceOfferedByBarber($input['service_id'], $input['barber_id'])) {
            Response::error("Validation failed", 422, ['service_id' => 'This service is not offered by the selected barber.']);
        }

        if (!$this->appointment->checkAvailability($input['barber_id'], $input['date'], $input['start_time'])) {
            Response::error("This time slot is no longer available.", 409);
        }

        try {
            $dataToCreate = [
                'user_id' => $userData['id'],
                'barber_id' => $input['barber_id'],
                'service_id' => $input['service_id'],
                'date' => $input['date'],
                'start_time' => $input['start_time'],
                'status' => 'booked'
            ];

            $newAppointmentId = $this->appointment->createAppointment($dataToCreate);

            if ($newAppointmentId) {
                $newAppointment = $this->appointment->readAppointment($newAppointmentId);
                Response::success($newAppointment, 201, "Appointment created successfully.");
            } else {
                Response::error("Failed to create appointment.", 500);
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }

    public function cancelAppointment($id) {
        $userData = AuthMiddleware::requireUser();
        
        $appointment = $this->appointment->readAppointment($id);

        if (!$appointment || $appointment['user_id'] != $userData['id']) {
            Response::notFound("Appointment not found.");
        }

        if ($this->appointment->cancelAppointment($id)) {
            Response::success(null, 200, "Appointment cancelled successfully.");
        } else {
            Response::error("Failed to cancel appointment.", 500);
        }
    }

    public function editAppointment($id) {
        $userData = AuthMiddleware::requireUser();

        $appointment = $this->appointment->readAppointment($id);

        if (!$appointment || $appointment['user_id'] != $userData['id']) {
            Response::notFound("Appointment not found.");
        }

        $update = json_decode(file_get_contents('php://input', true));

        try {
            $data = $this->appointment->editAppointment($id, $update);
            Response::success($data);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            Response::error("A database error occurred.", 500);
        }
    }
}
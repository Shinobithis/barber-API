<?php
class ProfessionalProfileController {
    private $professionalServicesModel;
    private $serviceModel;
    private $offerModel;

    public function __construct(ProfessionalServices $professionalServicesModel, Service $serviceModel, Offer $offerModel) {
        $this->professionalServicesModel = $professionalServicesModel;
        $this->serviceModel = $serviceModel;
        $this->offerModel = $offerModel;
    }

    public function addService() {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_data = AuthMiddleware::requireProfessional();

        $validator = new Validator;
        $validator
            ->required('service_id', $data['service_id'] ?? null)
            ->numeric('service_id', $data['service_id'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $service = $this->serviceModel->findById($data['service_id']);
        if (!$service) {
            Response::error("Service not found", 404);
        }

        $check = $this->professionalServicesModel->checkIfServiceIsAdded($user_data['id'], $data['service_id']);
        if ($check) {
            Response::error("Service already added", 409);
        }

        $result = $this->professionalServicesModel->addServiceToProfessional($user_data['id'], $data['service_id']);

        if ($result) {
            Response::success([], 200, "Service added successfully.");
        } else {
            Response::error("Failed to add service", 500);
        }
    }

    public function removeService() {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_data = AuthMiddleware::requireProfessional();

        $validator = new Validator;
        $validator
            ->required('service_id', $data['service_id'] ?? null)
            ->numeric('service_id', $data['service_id'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $result = $this->professionalServicesModel->removeServiceFromProfessional($user_data['id'], $data['service_id']);

        if ($result) {
            Response::success([], 200, "Service removed successfully.");
        } else {
            Response::error("Failed to remove service", 500);
        }
    }

    public function getServices() {
        $user_data = AuthMiddleware::requireProfessional();
        $services = $this->professionalServicesModel->getServicesForProfessional($user_data['id']);
        Response::success(['services' => $services]);
    }

    public function createOffer() {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_data = AuthMiddleware::requireProfessional();

        $validator = new Validator;
        $validator
            ->required('service_id', $data['service_id'] ?? null)
            ->numeric('service_id', $data['service_id'] ?? null)
            ->required('name', $data['name'] ?? null)
            ->string('name', $data['name'] ?? null)
            ->required('price', $data['price'] ?? null)
            ->numeric('price', $data['price'] ?? null)
            ->required('duration_min', $data['duration_min'] ?? null)
            ->numeric('duration_min', $data['duration_min'] ?? null)
            ->optional('description', $data['description'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $data['professional_id'] = $user_data['id'];

        $offerId = $this->offerModel->create($data);

        if ($offerId) {
            Response::success(['offer_id' => $offerId], 201, "Offer created successfully.");
        } else {
            Response::error("Failed to create offer", 500);
        }
    }

    public function updateOffer() {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_data = AuthMiddleware::requireProfessional();

        $validator = new Validator;
        $validator
            ->required('service_id', $data['service_id'] ?? null)
            ->numeric('service_id', $data['service_id'] ?? null)
            ->required('name', $data['name'] ?? null)
            ->string('name', $data['name'] ?? null)
            ->required('price', $data['price'] ?? null)
            ->numeric('price', $data['price'] ?? null)
            ->required('duration_min', $data['duration_min'] ?? null)
            ->numeric('duration_min', $data['duration_min'] ?? null)
            ->optional('description', $data['description'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $offer = $this->offerModel->findById($data['offer_id']);
        if (!$offer) {
            Response::error("Offer not found", 404);
        }

        if ($offer['professional_id'] != $user_data['id']) {
            Response::error("Unauthorized to update this offer", 403);
        }

        unset($data['offer_id']);

        if ($this->offerModel->update($data['id'], $data)) {
            Response::success([], 200, "Offer updated successfully.");
        } else {
            Response::error("Failed to update offer", 500);
        }
    }

    public function deleteOffer() {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_data = AuthMiddleware::requireProfessional();

        $validator = new Validator;
        $validator
            ->required('offer_id', $data['offer_id'] ?? null)
            ->numeric('offer_id', $data['offer_id'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $offer = $this->offerModel->findById($data['offer_id']);
        if (!$offer) {
            Response::error("Offer not found", 404);
        }

        if ($offer['professional_id'] != $user_data['id']) {
            Response::error("Unauthorized to delete this offer", 403);
        }

        if ($this->offerModel->delete($data['offer_id'])) {
            Response::success([], 200, "Offer deleted successfully.");
        } else {
            Response::error("Failed to delete offer", 500);
        }
    }

    public function getOffers() {
        $user_data = AuthMiddleware::requireProfessional();
        $offers = $this->offerModel->getOffersForProfessional($user_data['id']);
        Response::success(['offers' => $offers]);
    }

    public function getOfferById($offer_id) {
        $user_data = AuthMiddleware::requireProfessional();
        $offer = $this->offerModel->findById($offer_id);

        if (!$offer || $offer['professional_id'] != $user_data['id']) {
            Response::error("Offer not found or unauthorized", 404);
        }

        Response::success(['offer' => $offer]);
    }
}
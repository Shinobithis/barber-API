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

    public function getMyServices() {
        $userData = AuthMiddleware::requireProfessional();
        $services = $this->professionalServicesModel->getServicesForProfessional($userData['id']);
        Response::success(['services' => $services]);
    }

    public function addMyService() {
        $userData = AuthMiddleware::requireProfessional();
        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator->required('service_id', $data['service_id'] ?? null)->numeric('service_id', $data['service_id'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $service_id = $data['service_id'];
        $professional_id = $userData['id'];

        if (!$this->serviceModel->findById($service_id)) {
            Response::error("Validation failed", 422, ['service_id' => 'This service does not exist.']);
        }

        if ($this->professionalServicesModel->checkIfServiceIsAdded($professional_id, $service_id)) {
            Response::error("This service has already been added to your profile.", 409);
        }

        if ($this->professionalServicesModel->addServiceToProfessional($professional_id, $service_id)) {
            Response::success(null, 201, "Service added successfully.");
        } else {
            Response::error("Failed to add service.", 500);
        }
    }

    public function removeMyService($service_id) {
        $userData = AuthMiddleware::requireProfessional();
        $professional_id = $userData['id'];
        
        if ($this->professionalServicesModel->removeServiceFromProfessional($professional_id, $service_id)) {
            Response::success(null, 204, "Service removed successfully.");
        } else {
            Response::error("Failed to remove service.", 500);
        }
    }

    public function getMyOffers() {
        $userData = AuthMiddleware::requireProfessional();
        $offers = $this->offerModel->getOffersForProfessional($userData['id']);
        Response::success(['offers' => $offers]);
    }

    public function createOffer() {
        $userData = AuthMiddleware::requireProfessional();
        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator
            ->required('service_id', $data['service_id'] ?? null)->numeric('service_id', $data['service_id'] ?? null)
            ->required('name', $data['name'] ?? null)->string('name', $data['name'] ?? null, 255)
            ->required('price', $data['price'] ?? null)->numeric('price', $data['price'] ?? null)
            ->required('duration_min', $data['duration_min'] ?? null)->integer('duration_min', $data['duration_min'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        if (!$this->professionalServicesModel->checkIfServiceIsAdded($userData['id'], $data['service_id'])) {
            Response::error("Validation failed", 422, ['service_id' => 'You must add this service to your skills before creating an offer for it.']);
        }

        $data['professional_id'] = $userData['id'];
        $offerId = $this->offerModel->create($data);

        if ($offerId) {
            $newOffer = $this->offerModel->findById($offerId);
            Response::success(['offer' => $newOffer], 201, "Offer created successfully.");
        } else {
            Response::error("Failed to create offer.", 500);
        }
    }

    public function updateOffer($id) {
        $userData = AuthMiddleware::requireProfessional();
        $data = json_decode(file_get_contents("php://input"), true);

        $offer = $this->offerModel->findById($id);
        if (!$offer || $offer['professional_id'] != $userData['id']) {
            Response::notFound("Offer not found or you do not have permission to edit it.");
        }

        $validator = new Validator;
        if (isset($data['name'])) $validator->string('name', $data['name'], 255);
        if (isset($data['price'])) $validator->numeric('price', $data['price']);
        if (isset($data['duration_min'])) $validator->numeric('duration_min', $data['duration_min']);
        
        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        if ($this->offerModel->update($id, $data)) {
            $updatedOffer = $this->offerModel->findById($id);
            Response::success(['offer' => $updatedOffer], 200, "Offer updated successfully.");
        } else {
            Response::error("Failed to update offer or no changes were made.", 500);
        }
    }

    public function deleteOffer($id) {
        $userData = AuthMiddleware::requireProfessional();

        $offer = $this->offerModel->findById($id);
        if (!$offer || $offer['professional_id'] != $userData['id']) {
            Response::notFound("Offer not found or you do not have permission to delete it.");
        }

        if ($this->offerModel->delete($id)) {
            Response::success(null, 204, "Offer deleted successfully.");
        } else {
            Response::error("Failed to delete offer.", 500);
        }
    }
}
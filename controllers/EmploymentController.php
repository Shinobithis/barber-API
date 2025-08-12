<?php
class EmploymentController {
    private $shopProfessionalsModel;
    private $professionalModel;
    private $shopModel;

    public function __construct(ShopProfessionals $shopProfessionalsModel, Professional $professionalModel, Shop $shopModel) {
        $this->shopProfessionalsModel = $shopProfessionalsModel;
        $this->professionalModel = $professionalModel;
        $this->shopModel = $shopModel;
    }

    public function sendHireRequest() {
        $ownerData = AuthMiddleware::requireOwner();
        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator
            ->required('shop_id', $data['shop_id'] ?? null)->numeric('shop_id', $data['shop_id'] ?? null)
            ->required('professional_email', $data['professional_email'] ?? null)->email('professional_email', $data['professional_email'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $shop = $this->shopModel->findById($data['shop_id']);
        if (!$shop || $shop['owner_id'] != $ownerData['id']) {
            Response::error("Shop not found or you do not have permission.", 404);
        }

        $professional = $this->professionalModel->findByEmail($data['professional_email']);
        if (!$professional) {
            Response::error("No professional found with this email address.", 404);
        }
        $professional_id = $professional['id'];

        if ($this->shopProfessionalsModel->createRequest($data['shop_id'], $professional_id)) {
            Response::success(null, 201, "Hire request sent successfully.");
        } else {
            Response::error("Failed to send hire request.", 500);
        }
    }

    public function getMyPendingRequests() {
        $professionalData = AuthMiddleware::requireProfessional();
        $requests = $this->shopProfessionalsModel->getPendingRequestsForProfessional($professionalData['id']);
        Response::success(['requests' => $requests]);
    }

    public function respondToRequest($request_id) {
        $professionalData = AuthMiddleware::requireProfessional();
        $data = json_decode(file_get_contents('php://input'), true);

        $validator = new Validator;
        $validator->required('decision', $data['decision'] ?? null)->in('decision', $data['decision'] ?? null, ['accept', 'decline']);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $request = $this->shopProfessionalsModel->findRequestById($request_id);
        if (!$request || $request['professional_id'] != $professionalData['id']) {
            Response::notFound("Hire request not found or you do not have permission.");
        }

        if ($request['status'] !== 'pending') {
            Response::error("This request has already been responded to.", 409);
        }

        $newStatus = ($data['decision'] === 'accept') ? 'active' : 'inactive';

        if ($this->shopProfessionalsModel->updateRequestStatus($request_id, $newStatus)) {
            Response::success(null, 200, "Request responded to successfully.");
        } else {
            Response::error("Failed to respond to request.", 500);
        }
    }
}
<?php
class ReviewController {
    private $reviewModel;
    private $appointmentModel;

    public function __construct(Review $review, Appointment $appointment) {
        $this->reviewModel = $review;
        $this->appointmentModel = $appointment;
    }

    public function createReview() {
        $userData = AuthMiddleware::requireUser();
        $data = json_decode(file_get_contents("php://input"), true);

        $validator = new Validator;
        $validator
            ->required('appointment_id', $data['appointment_id'] ?? null)->numeric('appointment_id', $data['appointment_id'] ?? null)
            ->required('rating', $data['rating'] ?? null)->numeric('rating', $data['rating'] ?? null)
            ->in('rating', $data['rating'] ?? null, [1, 2, 3, 4, 5])
            ->required('comment', $data['comment'] ?? null)->maxLength('comment', 500, $data['comment'] ?? null);

        if ($validator->hasErrors()) {
            Response::error("Validation failed", 422, $validator->getErrors());
        }

        $appointment = $this->appointmentModel->findForReview($data['appointment_id'], $userData['id']);
        if (!$appointment) {
            Response::error("Appointment not found, not completed, or you do not have permission to review it.", 403);
        }

        $reviewData = [
            'appointment_id' => $appointment['id'],
            'user_id' => $userData['id'],
            'professional_id' => $appointment['professional_id'],
            'shop_id' => $appointment['shop_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment']
        ];

        try {
            $newReviewId = $this->reviewModel->create($reviewData);
            if ($newReviewId) {
                Response::success(['review_id' => $newReviewId], 201, "Review submitted successfully.");
            } else {
                Response::error("Failed to submit review.", 500);
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                Response::error("You have already submitted a review for this appointment.", 409);
            } else {
                error_log($e->getMessage());
                Response::error("A database error occurred.", 500);
            }
        }
    }

    public function getReviewsForProfessional($professional_id) {
        $reviews = $this->reviewModel->findByProfessionalId($professional_id);
        Response::success(['reviews' => $reviews]);
    }

    public function getReviewsForShop($shop_id) {
        $reviews = $this->reviewModel->findByShopId($shop_id);
        Response::success(['reviews' => $reviews]);
    }
}
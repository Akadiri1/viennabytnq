<?php
// submit-review.php

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to submit a review.']);
    exit;
}

// 2. Validate Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// 3. Get Input Data
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$reviewText = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

// 4. Validate Input
if ($productId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a rating between 1 and 5 stars.']);
    exit;
}
if (empty($reviewText)) {
    echo json_encode(['status' => 'error', 'message' => 'Please write a review.']);
    exit;
}

// 5. Get User Details
$userId = $_SESSION['user_id'];
// Fetch user name/email if not in session, or just use what we have.
// Assuming users table has 'full_name' and 'email'.
// We'll trust the session for ID, but let's fetch nice names for the review record.
try {
    // Check if user has already reviewed this product? (Optional, but good practice)
    // $stmtCheck = $conn->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?"); ...
    // For now, allow multiple reviews or assume checking isn't strictly required by user prompt.

    // Get Reviewer Name/Email from DB to be accurate
    $stmtUser = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $reviewerName = $user ? $user['full_name'] : 'Customer';
    $reviewerEmail = $user ? $user['email'] : '';

    // 6. Insert Review
    // is_approved = 1 (Auto-approve as requested for "immediate show")
    $stmtInsert = $conn->prepare("
        INSERT INTO product_reviews (product_id, reviewer_name, reviewer_email, rating, review_text, is_approved, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    
    if ($stmtInsert->execute([$productId, $reviewerName, $reviewerEmail, $rating, $reviewText])) {
        
        // Calculate new stats
        $stmtStats = $conn->prepare("SELECT COUNT(*) as total, AVG(rating) as average FROM product_reviews WHERE product_id = ? AND is_approved = 1");
        $stmtStats->execute([$productId]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
        
        $newTotal = $stats['total'];
        $newAverage = round($stats['average'], 1);

        // Return success with the review data so JS can prepend it accurately
        echo json_encode([
            'status' => 'success',
            'message' => 'Review submitted successfully!',
            'new_total' => $newTotal,
            'new_average' => $newAverage,
            'review' => [
                'reviewer_name' => $reviewerName,
                'rating' => $rating,
                'review_text' => htmlspecialchars($reviewText), // XSS protection for display
                'created_at' => date('F j, Y'), // Formatted date
                'avatar_letter' => strtoupper(substr($reviewerName, 0, 1))
            ]
        ]);
    } else {

        echo json_encode(['status' => 'error', 'message' => 'Database error: Could not save review.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}

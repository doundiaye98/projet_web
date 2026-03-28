<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../../includes/auth/session.php';
requireLogin();

$userRole = getUserRole();
if (!in_array($userRole, ['admin', 'moderateur'], true)) {
    http_response_code(403);
    exit('Accès refusé.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['review_id'])) {
    $action = $_POST['action'];
    $reviewId = (int)$_POST['review_id'];
    if ($action === 'accept_review') {
        if (acceptReview($mysqli, $reviewId)) {
            $_SESSION['flash_success'] = "L'avis a été validé avec succès.";
            http_response_code(200);
            echo 'ok';
        } else {
            $_SESSION['flash_error'] = "Erreur lors de la validation de l'avis.";
            http_response_code(500);
            echo 'Erreur lors de la validation.';
        }
        exit();
    } elseif ($action === 'delete_review') {
        if (deleteReview($mysqli, $reviewId)) {
            $_SESSION['flash_success'] = "L'avis a été supprimé avec succès.";
            http_response_code(200);
            echo 'ok';
        } else {
            $_SESSION['flash_error'] = "Erreur lors de la suppression de l'avis.";
            http_response_code(500);
            echo 'Erreur lors de la suppression.';
        }
        exit();
    }
}
http_response_code(400);
echo 'Requête invalide.';

<?php
defined("SECURE_ACCESS") or die("Acces direct interdit");
require_once __DIR__ . '/../../includes/auth/session.php';
require_once __DIR__ . '/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

$action = $_POST['action'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if (!$userId) {
    $_SESSION['flash_error'] = "Action impossible.";
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

if ($action === 'create_session') {
    if (!in_array(getUserRole(), ['admin', 'moderateur'], true)) {
        $_SESSION['flash_error'] = "Acces refuse.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    $bookId = (int) ($_POST['book_id'] ?? 0);
    $titre = trim($_POST['titre'] ?? '');
    $dateRaw = trim($_POST['date_heure'] ?? '');
    $lieu = trim($_POST['lieu'] ?? '');
    $lien = trim($_POST['lien'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($bookId < 1 || $titre === '' || $dateRaw === '') {
        $_SESSION['flash_error'] = "Veuillez remplir le livre, le titre et la date.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    // Normalisation date
    $dateHeure = null;
    $normalized = str_replace('T', ' ', $dateRaw);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
        $dateHeure = $normalized . ':00';
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $normalized)) {
        $dateHeure = $normalized;
    } else {
        $ts = strtotime($dateRaw);
        if ($ts !== false) $dateHeure = date('Y-m-d H:i:s', $ts);
    }
    
    if ($dateHeure === null) {
        $_SESSION['flash_error'] = "Date ou heure invalide.";
        header('Location: ' . BASE_URL . '/sessions');
        exit();
    }

    $data = [
        'book_id' => $bookId,
        'titre' => mb_substr($titre, 0, 255),
        'date_heure' => $dateHeure,
        'lieu' => mb_substr($lieu, 0, 255),
        'lien' => mb_substr($lien, 0, 500),
        'description' => $description
    ];

    if (createSessionWithCreator($mysqli, $userId, $data)) {
        $_SESSION['flash_success'] = "Session creee avec succes.";
    } else {
        $_SESSION['flash_error'] = "Impossible d'enregistrer la session.";
    }
    
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

$sessionId = (int) ($_POST['session_id'] ?? 0);
if (!$sessionId) {
    $_SESSION['flash_error'] = "Session invalide.";
    header('Location: ' . BASE_URL . '/sessions');
    exit();
}

if ($action === 'join_session') {
    $stmt = $mysqli->prepare("SELECT date_heure FROM sessions WHERE id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $sessionData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($sessionData && strtotime($sessionData['date_heure']) < time()) {
        $_SESSION['flash_error'] = "Vous ne pouvez plus rejoindre cette session car elle est deja lancee.";
    } elseif (joinSession($mysqli, $sessionId, $userId)) {
        $_SESSION['flash_success'] = "Participation enregistree.";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de l'inscription.";
    }
} elseif ($action === 'leave_session') {
    if (leaveSession($mysqli, $sessionId, $userId)) {
        $_SESSION['flash_success'] = "Participation annulee.";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de l'annulation.";
    }
} elseif ($action === 'delete_session') {
    if (getUserRole() !== 'admin') {
        $_SESSION['flash_error'] = "Action reservee aux administrateurs.";
    } elseif (deleteSession($mysqli, $sessionId)) {
        $_SESSION['flash_success'] = "Session supprimee.";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de la suppression.";
    }
} elseif ($action === 'update_session_progression') {
    $page = (int) ($_POST['page_actuelle'] ?? 0);
    $userRole = getUserRole();
    
    if (!canManageSession($mysqli, $sessionId, $userId, $userRole)) {
        $_SESSION['flash_error'] = "Vous n'avez pas l'autorisation de modifier cette session.";
    } elseif ($page < 0) {
        $_SESSION['flash_error'] = "Page invalide.";
    } else {
        if (syncSessionProgress($mysqli, $sessionId, $page)) {
            $_SESSION['flash_success'] = "Progression de la session mise a jour pour tous les membres !";
        } else {
            $_SESSION['flash_error'] = "Erreur lors de la synchronisation de la progression.";
        }
    }
} else {
    $_SESSION['flash_error'] = "Action inconnue.";
}

header('Location: ' . BASE_URL . '/sessions');
exit();

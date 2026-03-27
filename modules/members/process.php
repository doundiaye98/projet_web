<?php
require_once __DIR__ . '/functions.php';

if (getUserRole() !== 'admin') {
    http_response_code(403);
    $errorCode = 403;
    include __DIR__ . '/../../includes/layout/error_page.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);

    if ($action === 'update_role') {
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'ID utilisateur invalide';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        if ($userId === $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Action impossible sur votre propre compte';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        if (isUserSystem($mysqli, $userId)) {
            $_SESSION['flash_error'] = 'Ce compte système est protégé et ne peut être modifié';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        $newRole = $_POST['role'] ?? '';
        if (updateUserRole($mysqli, $userId, $newRole)) {
            $_SESSION['flash_success'] = 'Rôle mis à jour avec succès';
        } else {
            $_SESSION['flash_error'] = 'Erreur lors de la mise à jour du rôle';
        }

    } elseif ($action === 'toggle_statut') {
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'ID utilisateur invalide';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        if ($userId === $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Action impossible sur votre propre compte';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        if (isUserSystem($mysqli, $userId)) {
            $_SESSION['flash_error'] = 'Ce compte système est protégé et ne peut être modifié';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        $newStatut = $_POST['statut'] ?? '';
        if (toggleUserStatut($mysqli, $userId, $newStatut)) {
            $_SESSION['flash_success'] = ($newStatut === 'banni') ? 'Utilisateur banni' : 'Utilisateur débanni';
        } else {
            $_SESSION['flash_error'] = 'Erreur lors du changement de statut';
        }

    } elseif ($action === 'delete_user') {
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'ID utilisateur invalide';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        if ($userId === $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Vous ne pouvez pas vous supprimer vous-même';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        if (isUserSystem($mysqli, $userId)) {
            $_SESSION['flash_error'] = 'Impossible de supprimer un compte système protégé';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        if (deleteUser($mysqli, $userId)) {
            $_SESSION['flash_success'] = 'Utilisateur supprimé avec succès';
        } else {
            $_SESSION['flash_error'] = 'Erreur lors de la suppression de l\'utilisateur';
        }

    } elseif ($action === 'add_user') {
        $nom      = $_POST['nom'] ?? '';
        $email    = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'membre';

        if (empty($nom) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Veuillez remplir tous les champs obligatoires';
            header('Location: ' . BASE_URL . '/members'); exit();
        }
        $result = createUser($mysqli, $nom, $email, $password, $role);
        if ($result === true) {
            $_SESSION['flash_success'] = 'Utilisateur ajouté avec succès';
        } else {
            $_SESSION['flash_error'] = $result;
        }
    }

    header('Location: ' . BASE_URL . '/members');
    exit();
} else {
    header('Location: ' . BASE_URL . '/members');
    exit();
}

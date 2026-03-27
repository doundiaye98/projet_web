<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/settings');
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'update_profile') {
    $currentUser = getUserById($mysqli, $_SESSION['user_id']);
    $nom         = trim($_POST['nom'] ?? '');
    $email       = $currentUser['is_system'] ? $currentUser['email'] : trim($_POST['email'] ?? '');

    if (empty($nom) || empty($email)) {
        $_SESSION['flash_error'] = 'Le nom et l\'email sont obligatoires.';
        header('Location: ' . BASE_URL . '/settings');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_error'] = 'Adresse email invalide.';
        header('Location: ' . BASE_URL . '/settings');
        exit();
    }

    if (isEmailTaken($mysqli, $email, $_SESSION['user_id'])) {
        $_SESSION['flash_error'] = 'Cet email est déjà utilisé par un autre compte.';
        header('Location: ' . BASE_URL . '/settings');
        exit();
    }

    $result = updateProfile($mysqli, $_SESSION['user_id'], $nom, $email);
    if ($result === true) {
        $_SESSION['user_name']  = $nom;
        $_SESSION['user_email'] = $email;
        $_SESSION['flash_success'] = 'Profil mis à jour avec succès.';
    } else {
        $_SESSION['flash_error'] = $result;
    }
}

if ($action === 'update_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $_SESSION['flash_error'] = 'Veuillez remplir tous les champs.';
        header('Location: ' . BASE_URL . '/settings');
        exit();
    }

    if ($new !== $confirm) {
        $_SESSION['flash_error'] = 'Les mots de passe ne correspondent pas.';
        header('Location: ' . BASE_URL . '/settings');
        exit();
    }

    $result = updatePassword($mysqli, $_SESSION['user_id'], $current, $new);
    if ($result === true) {
        $_SESSION['flash_success'] = 'Mot de passe mis à jour avec succès.';
    } else {
        $_SESSION['flash_error'] = $result;
    }
}

header('Location: ' . BASE_URL . '/settings');
exit();

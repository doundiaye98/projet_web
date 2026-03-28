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
    $avatarPath  = $currentUser['avatar_path'] ?? null;

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

    $updateAvatar = false;

    // Gestion suppression avatar
    if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
        $avatarPath = null;
        $updateAvatar = true;
    }

    // Gestion upload avatar
    if (!empty($_FILES['avatar']['name'] ?? '')) {
            // Log du contenu de $_FILES['avatar'] pour debug
            error_log('[Avatar Upload] $_FILES["avatar"] = ' . var_export($_FILES['avatar'], true));
        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log('[Avatar Upload] Erreur upload PHP code ' . $file['error']);
            header('Location: ' . BASE_URL . '/settings');
            exit();
        }
        if (!in_array($file['type'], $allowed, true)) {
            error_log('[Avatar Upload] Format non autorisé : ' . $file['type']);
            header('Location: ' . BASE_URL . '/settings');
            exit();
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = 'avatar_user_' . (int)$_SESSION['user_id'] . '.' . strtolower($ext);
        $uploadDir = __DIR__ . '/../../uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $target = $uploadDir . '/' . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            error_log('[Avatar Upload] move_uploaded_file a échoué pour ' . $file['tmp_name'] . ' -> ' . $target);
            header('Location: ' . BASE_URL . '/settings');
            exit();
        }
        $avatarPath = 'uploads/avatars/' . $safeName;
        $updateAvatar = true;
    }

    $result = updateProfile($mysqli, $_SESSION['user_id'], $nom, $email, $avatarPath, $updateAvatar);
    if ($result === true) {
        $_SESSION['user_name']  = $nom;
        $_SESSION['user_email'] = $email;
        if ($updateAvatar) {
            $_SESSION['user_avatar'] = $avatarPath;
        }
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

<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $nom              = trim($_POST['nom'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (empty($nom) || empty($email) || empty($password) || empty($password_confirm)) {
            $_SESSION['flash_error'] = "Veuillez remplir tous les champs.";
        } elseif ($password !== $password_confirm) {
            $_SESSION['flash_error'] = "Les mots de passe ne correspondent pas.";
        } else {
            $result = register($mysqli, $nom, $email, $password);
            if ($result === true) {
                $_SESSION['flash_success'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                header('Location: ' . BASE_URL . '/login');
                exit;
            } else {
                $_SESSION['flash_error'] = $result;
            }
        }
        header('Location: ' . BASE_URL . '/register');
        exit;

    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = "Veuillez remplir tous les champs.";
        } else {
            $loginError = null;
            if (login($mysqli, $email, $password, $loginError)) {
                header('Location: ' . BASE_URL . '/');
                exit;
            } else {
                $_SESSION['flash_error'] = $loginError ?: "Email ou mot de passe incorrect.";
            }
        }
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
} else {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

<?php
define('SECURE_ACCESS', true);

//login gestion formulaire

require_once '../../config/db.php';
require_once 'function.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    if ($action === 'register') {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        if (empty($nom) || empty($email) || empty($password) || empty($password_confirm)) {
            $error = "Veuillez remplir tous les champs.";
        } elseif ($password !== $password_confirm) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            $register_result = register($mysqli, $nom, $email, $password);
            if ($register_result === true) {
                header('Location: login.php?success=1');
                exit;
            } else {
                $error = $register_result;
            }
        }
        header('Location: register.php?error=' . urlencode($error));
        exit;
    } else {
        $email = trim($_POST["email"] ?? '');
        $password = $_POST["password"] ?? '';
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } else {
            if (login($mysqli, $email, $password)) {
                header("Location: ../../index.php");
                exit;
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        }
        header('Location: login.php?error=' . urlencode($error));
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}



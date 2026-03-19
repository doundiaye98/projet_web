<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth/session.php';
require_once __DIR__ . '/functions.php';

// Sécurité : Seul l'admin peut accéder à ce script
if (getUserRole() !== 'admin') {
    header('Location: /index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);

    if ($action === 'update_role') {
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'ID utilisateur invalide';
            header('Location: index.php');
            exit();
        }
        // Empêcher l'admin de s'auto-dégrader
        if ($userId === $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Action impossible sur votre propre compte';
            header('Location: index.php');
            exit();
        }
        // Protection des comptes système
        if (isUserSystem($mysqli, $userId)) {
            $_SESSION['flash_error'] = 'Ce compte système est protégé et ne peut être modifié';
            header('Location: index.php');
            exit();
        }

        $newRole = $_POST['role'] ?? '';
        if (updateUserRole($mysqli, $userId, $newRole)) {
            $_SESSION['flash_success'] = 'Rôle mis à jour avec succès';
            header('Location: index.php');
        } else {
            $_SESSION['flash_error'] = 'Erreur lors de la mise à jour du rôle';
            header('Location: index.php');
        }
    } elseif ($action === 'toggle_statut') {
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'ID utilisateur invalide';
            header('Location: index.php');
            exit();
        }
        if ($userId === $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Action impossible sur votre propre compte';
            header('Location: index.php');
            exit();
        }
        // Protection des comptes système
        if (isUserSystem($mysqli, $userId)) {
            $_SESSION['flash_error'] = 'Ce compte système est protégé et ne peut être modifié';
            header('Location: index.php');
            exit();
        }

        $newStatut = $_POST['statut'] ?? '';
        if (toggleUserStatut($mysqli, $userId, $newStatut)) {
            $msg = ($newStatut === 'banni') ? 'Utilisateur banni' : 'Utilisateur débanni';
            $_SESSION['flash_success'] = $msg;
            header("Location: index.php");
        } else {
            $_SESSION['flash_error'] = 'Erreur lors du changement de statut';
            header('Location: index.php');
        }
    } elseif ($action === 'delete_user') {
        if ($userId <= 0) {
            $_SESSION['flash_error'] = 'ID utilisateur invalide';
            header('Location: index.php');
            exit();
        }
        if ($userId === $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Vous ne pouvez pas vous supprimer vous-même';
            header('Location: index.php');
            exit();
        }
        // Protection des comptes système
        if (isUserSystem($mysqli, $userId)) {
            $_SESSION['flash_error'] = 'Impossible de supprimer un compte système protégé';
            header('Location: index.php');
            exit();
        }

        if (deleteUser($mysqli, $userId)) {
            $_SESSION['flash_success'] = 'Utilisateur supprimé avec succès';
            header('Location: index.php');
        } else {
            $_SESSION['flash_error'] = 'Erreur lors de la suppression de l\'utilisateur';
            header('Location: index.php');
        }
    } elseif ($action === 'add_user') {
        $nom = $_POST['nom'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'membre';

        if (empty($nom) || empty($email) || empty($password)) {
            $_SESSION['flash_error'] = 'Veuillez remplir tous les champs obligatoires';
            header('Location: index.php');
            exit();
        }

        $result = createUser($mysqli, $nom, $email, $password, $role);
        if (is_numeric($result)) {
            $_SESSION['flash_success'] = 'Utilisateur ajouté avec succès';
            header('Location: index.php');
        } else {
            $_SESSION['flash_error'] = $result;
            header('Location: index.php');
        }
    } else {
        header('Location: index.php');
    }
    exit();
} else {
    header('Location: index.php');
    exit();
}

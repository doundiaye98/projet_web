<?php 
require_once __DIR__ . '/../../includes/auth/session.php';

// Fonction de connection 

function login($mysqli, $email, $password) {
    $sql = "SELECT id, nom, email, password_hash, role FROM users WHERE email = ? AND statut = 'actif'";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            // Authentification réussie
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
    }
    return false;
}

function register($mysqli, $nom, $email, $password) {
    // Vérifier si l'email existe déjà
    $sql_check = "SELECT id FROM users WHERE email = ?";
    $stmt_check = $mysqli->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        return "Cet email est déjà utilisé.";
    }

    // Hash du mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insérer l'utilisateur (role par défaut : membre)
    $sql_insert = "INSERT INTO users (nom, email, password_hash, role, statut) VALUES (?, ?, ?, 'membre', 'actif')";
    $stmt_insert = $mysqli->prepare($sql_insert);
    $stmt_insert->bind_param("sss", $nom, $email, $password_hash);

    if ($stmt_insert->execute()) {
        return true;
    } else {
        return "Erreur lors de l'inscription : " . $stmt_insert->error;
    }
}

?>
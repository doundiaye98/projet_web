<?php

function login($mysqli, $email, $password, &$loginError = null) {
    $email = trim(mb_strtolower($email));
    $sql = "SELECT id, nom, email, password_hash, role, statut FROM users WHERE LOWER(email) = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        $loginError = "Connexion impossible temporairement.";
        return false;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $loginError = "Email ou mot de passe incorrect.";
        return false;
    }

    $user = $result->fetch_assoc();

    $storedHash = (string)($user['password_hash'] ?? '');
    $passwordOk = password_verify($password, $storedHash);
    $needsMigration = false;

    // Compatibilite legacy: anciens comptes en clair ou md5
    if (!$passwordOk && $storedHash !== '' && hash_equals($storedHash, $password)) {
        $passwordOk = true;
        $needsMigration = true;
    }
    if (
        !$passwordOk
        && $storedHash !== ''
        && strlen($storedHash) === 32
        && ctype_xdigit($storedHash)
        && hash_equals($storedHash, md5($password))
    ) {
        $passwordOk = true;
        $needsMigration = true;
    }

    if (!$passwordOk) {
        $loginError = "Email ou mot de passe incorrect.";
        return false;
    }

    $statut = mb_strtolower((string)($user['statut'] ?? ''));
    if (!in_array($statut, ['actif', 'active'], true)) {
        $loginError = "Votre compte est inactif ou banni.";
        return false;
    }

    if ($needsMigration || password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        if ($update) {
            $update->bind_param("si", $newHash, $user['id']);
            $update->execute();
            $update->close();
        }
    }

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['nom'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    return true;
}

function register($mysqli, $nom, $email, $password) {
    $sql_check = "SELECT id FROM users WHERE email = ?";
    $stmt_check = $mysqli->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        return "Cet email est déjà utilisé.";
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $sql_insert = "INSERT INTO users (nom, email, password_hash, role, statut) VALUES (?, ?, ?, 'membre', 'actif')";
    $stmt_insert = $mysqli->prepare($sql_insert);
    $stmt_insert->bind_param("sss", $nom, $email, $password_hash);

    if ($stmt_insert->execute()) {
        return true;
    } else {
        return "Erreur lors de l'inscription : " . $stmt_insert->error;
    }
}

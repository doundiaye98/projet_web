<?php
defined("SECURE_ACCESS") or die("Accès direct interdit");
/**
 * Helpers globaux pour l'application
 */

/**
 * Formate une date en français (ex: 26 Mars 2026)
 */
function formatDateFR($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    
    $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $months = [
        1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
    ];
    
    $d = date('j', $timestamp);
    $m = $months[(int)date('n', $timestamp)];
    $y = date('Y', $timestamp);
    
    return "$d $m $y";
}

/**
 * Génère les initiales d'un utilisateur à partir de son nom
 */
function getUserInitials($nom) {
    if (empty($nom)) return '?';
    $words = explode(' ', trim($nom));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($words[0], 0, 2));
}

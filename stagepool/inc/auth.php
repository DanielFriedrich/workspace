<?php
if (!defined('SP_APP')) { exit; }

/** Anmeldung für das Backend. */

function current_user()
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['sp_user_id'])) {
        $row = db_one('SELECT id, name, email, phone, role, active FROM #__users WHERE id = ?', array((int) $_SESSION['sp_user_id']));
        if ($row && (int) $row['active'] === 1) {
            $user = $row;
        } else {
            unset($_SESSION['sp_user_id']);
        }
    }
    return $user;
}

function is_admin()
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_login()
{
    if (!current_user()) {
        $_SESSION['sp_after_login'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        redirect('admin/login.php');
    }
    header('Cache-Control: no-store');
}

function require_admin()
{
    require_login();
    if (!is_admin()) {
        flash('error', 'Dieser Bereich ist nur für Administratoren.');
        redirect('admin/index.php');
    }
}

function ip_hash()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0';
    return hash('sha256', $ip . '|' . cfg('secret', 'stagepool'));
}

function login_blocked()
{
    $since = date('Y-m-d H:i:s', time() - 15 * 60);
    db_exec('DELETE FROM #__login_attempts WHERE created_at < ?', array(date('Y-m-d H:i:s', time() - 86400)));
    return (int) db_value('SELECT COUNT(*) FROM #__login_attempts WHERE ip_hash = ? AND created_at >= ?', array(ip_hash(), $since)) >= 6;
}

function attempt_login($email, $password)
{
    $user = db_one('SELECT * FROM #__users WHERE email = ? AND active = 1', array(strtolower(trim($email))));
    if (!$user || !password_verify($password, $user['password_hash'])) {
        db_insert('login_attempts', array('ip_hash' => ip_hash(), 'created_at' => now()));
        usleep(400000);
        return false;
    }
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db_update('users', array('password_hash' => password_hash($password, PASSWORD_DEFAULT)), 'id = ?', array($user['id']));
    }
    session_regenerate_id(true);
    $_SESSION['sp_user_id'] = (int) $user['id'];
    db_update('users', array('last_login' => now()), 'id = ?', array($user['id']));
    db_exec('DELETE FROM #__login_attempts WHERE ip_hash = ?', array(ip_hash()));
    return true;
}

function logout()
{
    unset($_SESSION['sp_user_id']);
    session_regenerate_id(true);
}

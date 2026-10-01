<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
csrf_check();
logout();
flash('success', 'Du bist abgemeldet.');
redirect('admin/login.php');

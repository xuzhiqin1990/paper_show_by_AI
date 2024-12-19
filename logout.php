<?php
// logout.php

session_start();

// 清除所有session变量
$_SESSION = array();

// 删除session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// 删除记住登录的cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time()-3600, '/');
}

// 销毁session
session_destroy();

// 重定向到首页
header("Location: index.php");
exit();
?>
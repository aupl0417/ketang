<?php

/*
 * Version : 1.0.0
 * Author  : flybug
 * Comment : 2015-11-14
 * 
 * memo:安装模块
 * 
 */

if (!isset($_GET['1234567890'])){
    die("Please don't try install,you are't allow!");
}

if (!file_exists(__FILE__.'../../index.php')){
    copy('./main.php', '../../index.php');
}
if (!is_dir('../../app')){
    mkdir('../../app');
}

echo 'Install successful!';
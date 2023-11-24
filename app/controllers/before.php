<?php

import('app/config.php');

if (is_file(MAIN_PATH . MAIN_APPLICATION_PATH . 'app/config.local.php')) {
    import('app/config.local.php');
}

import('app/services/user.php');
import('libs/plugins/loader.php');

// オートログイン
if (empty($_SESSION['auth']['session']) && !empty($_COOKIE['auth']['session'])) {
    list($session, $user_id) = service_user_autologin($_COOKIE['auth']['session']);
    if ($session === true) {
        $_SESSION['auth']['session'] = $session;
        $_SESSION['auth']['user']    = [
            'id'   => $user_id,
            'time' => localdate(),
        ];
    }
}

// ユーザ存在確認
if (!empty($_SESSION['auth']['user']['id'])) {
    $users = model('select_users', [
        'where' => [
            'id = :id',
            [
                'id' => $_SESSION['auth']['user']['id'],
            ],
        ],
    ]);
    if (empty($users)) {
        unset($_SESSION['auth']['user']);

        // リダイレクト
        redirect('/user');
    } else {
        $_view['_user'] = $users[0];
    }
}

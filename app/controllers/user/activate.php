<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ワンタイムトークン
    if (!token('check')) {
        error('不正なアクセスです。');
    }

    // トークンを作成
    $token = rand_string();

    // トランザクションを開始
    db_transaction();

    // ユーザを編集
    $resource = update_users(array(
        'set'   => array(
            'token'        => $token,
            'token_code'   => null,
            'token_expire' => null,
        ),
        'where' => array(
            'id = :id',
            array(
                'id' => $_SESSION['auth']['user']['id'],
            ),
        ),
    ));
    if (!$resource) {
        error('指定されたユーザが見つかりません。');
    }

    // ユーザを取得
    $users = select_users(array(
        'select' => 'email',
        'where'  => array(
            'id = :id',
            array(
                'id' => $_SESSION['auth']['user']['id'],
            ),
        ),
    ));

    // メール送信内容を作成
    $_view['url'] = $GLOBALS['config']['http_url'] . MAIN_FILE . '/user/activate?email=' . rawurlencode($users[0]['email']) . '&token=' . $token;

    $to      = $users[0]['email'];
    $subject = $GLOBALS['config']['mail_subjects']['user/activate'];
    $message = view('mail/user/activate.php', true);
    $headers = $GLOBALS['config']['mail_headers'];

    // メールを送信
    if (service_mail_send($to, $subject, $message, $headers) === false) {
        error('メールを送信できません。');
    }

    // トランザクションを終了
    db_commit();

    // リダイレクト
    redirect('/user/home?ok=send');
} else {
    // ユーザを編集
    $resource = update_users(array(
        'set'   => array(
            'email_activated' => 1,
            'token'           => null,
            'token_code'      => null,
            'token_expire'    => null,
        ),
        'where' => array(
            'email = :email AND token = :token',
            array(
                'email' => $_GET['email'],
                'token' => $_GET['token'],
            ),
        ),
    ));
    if (!$resource) {
        error('データを編集できません。');
    }

    if (db_affected_count($resource) == 0) {
        error('メールアドレスを確認できません。');
    }

    // リダイレクト
    redirect('/user/home?ok=activate');
}

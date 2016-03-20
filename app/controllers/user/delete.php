<?php

//ワンタイムトークン
if (!token('check')) {
    error('不正なアクセスです。');
}

//トランザクションを開始
db_transaction();

//ユーザを削除
$resource = delete_users(array(
    'where' => array(
        'id = :id AND regular = 1',
        array(
            'id' => $_SESSION['user']['id'],
        ),
    ),
), array(
    'associate' => true,
));
if (!$resource) {
    error('データを削除できません。');
}

//トランザクションを終了
db_commit();

//投稿セッションを初期化
unset($_SESSION['user']);

//リダイレクト
redirect('/user/delete_complete');

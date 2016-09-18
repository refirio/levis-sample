<?php

import('libs/plugins/file.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ワンタイムトークン
    if ((empty($_POST['view']) || $_POST['view'] !== 'preview') && !token('check')) {
        error('不正なアクセスです。');
    }

    // 入力データを整理
    $post = array(
        'class' => normalize_classes(array(
            'id'   => isset($_POST['id'])   ? $_POST['id']   : '',
            'code' => isset($_POST['code']) ? $_POST['code'] : '',
            'name' => isset($_POST['name']) ? $_POST['name'] : '',
            'memo' => isset($_POST['memo']) ? $_POST['memo'] : '',
        ))
    );

    if (isset($_POST['view']) && $_POST['view'] === 'preview') {
        // プレビュー
        $view['class'] = $post['class'];
    } else {
        // 入力データを検証＆登録
        $warnings = validate_classes($post['class']);
        if (isset($_POST['type']) && $_POST['type'] === 'json') {
            if (empty($warnings)) {
                ok();
            } else {
                warning($warnings);
            }
        } else {
            if (empty($warnings)) {
                $_SESSION['post']['class'] = $post['class'];

                // フォワード
                forward('/admin/class_post');
            } else {
                $view['class'] = $post['class'];

                $view['warnings'] = $warnings;
            }
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $view['class'] = default_classes();
    } else {
        $classes = select_classes(array(
            'where' => array(
                'id = :id',
                array(
                    'id' => $_GET['id'],
                ),
            ),
        ));
        if (empty($classes)) {
            warning('編集データが見つかりません。');
        } else {
            $view['class'] = $classes[0];
        }
    }

    if (isset($_GET['type']) && $_GET['type'] === 'json') {
        // 教室情報を取得
        header('Content-Type: application/json; charset=' . MAIN_CHARSET);

        echo json_encode(array(
            'status' => 'OK',
            'data'   => $view,
            'files'  => array(
                'image_01' => $view['class']['image_01'] ? file_mimetype($view['class']['image_01']) : null,
                'image_02' => $view['class']['image_02'] ? file_mimetype($view['class']['image_02']) : null,
                'document' => $view['class']['document'] ? file_mimetype($view['class']['document']) : null,
            ),
        ));

        exit;
    } else {
        // 投稿セッションを初期化
        unset($_SESSION['post']);
        unset($_SESSION['file']);
    }

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['class'] = localdate('Y-m-d H:i:s');
    }
}

// タイトル
if (empty($_GET['id'])) {
    $view['title'] = '教室登録';
} else {
    $view['title'] = '教室編集';
}

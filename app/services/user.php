<?php

/*******************************************************************************

 Functions for User

*******************************************************************************/

import('libs/plugins/cookie.php');

function service_user_autologin($session_id)
{
    //セッション情報を取得
    $users = select_sessions(array(
        'select' => 'user_id, keep',
        'where'  => array(
            'id = :id AND expire > :expire',
            array(
                'id'     => $session_id,
                'expire' => localdate('Y-m-d H:i:s'),
            ),
        ),
    ));

    $session = false;
    $user_id = null;

    if (!empty($users)) {
        //セッション情報を更新
        $new_session_id = rand_string();

        $resource = update_sessions(array(
            'set'   => array(
                'id'     => $new_session_id,
                'agent'  => $_SERVER['HTTP_USER_AGENT'],
                'expire' => localdate('Y-m-d H:i:s', time() + $GLOBALS['cookie_expire']),
            ),
            'where' => array(
                'id = :id',
                array(
                    'id' => $session_id,
                ),
            ),
        ));
        if ($resource) {
            cookie_set('session', $new_session_id, time() + $GLOBALS['cookie_expire']);
        } else {
            error('データを編集できません。');
        }

        if ($users[0]['keep']) {
            //ユーザ情報を更新
            $resource = update_users(array(
                'set'   => array(
                    'loggedin' => localdate('Y-m-d H:i:s'),
                ),
                'where' => array(
                    'id = :id',
                    array(
                        'id' => $users[0]['user_id'],
                    ),
                ),
            ));
            if (!$resource) {
                error('データを編集できません。');
            }

            $session = true;
            $user_id = $users[0]['user_id'];
        }
    }

    return array($session, $user_id);
}

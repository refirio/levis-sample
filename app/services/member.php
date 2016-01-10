<?php

/*********************************************************************

 Functions for Member

*********************************************************************/

function member_export()
{
	//名簿を取得
	$members = select_members(array(
		'where'    => 'members.public = 1',
		'order_by' => 'members.id'
	), array(
		'associate' => true
	));

	//CSV形式に整形
	$data  = mb_convert_encoding('"ID","登録日時","更新日時","削除","クラスID","名前","名前（フリガナ）","成績","生年月日","メールアドレス","電話番号","メモ","画像1","画像2","公開","クラス名"', 'SJIS-WIN', 'UTF-8');
	$data .= "\n";

	foreach ($members as $member) {
		$flag = false;
		foreach ($member as $key => $value) {
			if ($flag) {
				$data .= ',';
			}

			if ($key == 'grade') {
				$value = $GLOBALS['options']['member']['grades'][$value];
			} elseif ($key == 'public') {
				$value = $GLOBALS['options']['member']['publics'][$value];
			}

			$data .= '"' . ($value != '' ? str_replace('"', '""', mb_convert_encoding($value, 'SJIS-WIN', 'UTF-8')) : '') . '"';

			$flag = true;
		}
		$data .= "\n";
	}

	return $data;
}

function member_import($filename)
{
	if ($fp = fopen($filename, 'r')) {
		$options = array(
			'grades'  => array_flip($GLOBALS['options']['member']['grades']),
			'publics' => array_flip($GLOBALS['options']['member']['publics']),
		);

		if ($_POST['operation'] == 'replace') {
			//元データ削除
			$resource = db_delete(array(
				'delete_from' => DATABASE_PREFIX . 'members'
			));
			if (!$resource) {
				error('データを削除できません。');
			}
		}

		//CSVファイルの一行目を無視
		$dummy = file_getcsv($fp);

		//CSVファイル読み込み
		$all_warnings = array();
		$i            = 1;
		while ($line = file_getcsv($fp)) {
			list($id, $created, $modified, $deleted, $class_id, $name, $name_kana, $grade, $birthday, $email, $tel, $memo, $image_01, $image_02, $public, $dummy) = $line;

			//入力データを整理
			$post = array(
				'class' => normalize_classes(array(
					'id'        => mb_convert_encoding($id, 'UTF-8', 'SJIS-WIN'),
					'created'   => mb_convert_encoding($created, 'UTF-8', 'SJIS-WIN'),
					'modified'  => mb_convert_encoding($modified, 'UTF-8', 'SJIS-WIN'),
					'deleted'   => mb_convert_encoding($deleted, 'UTF-8', 'SJIS-WIN'),
					'class_id'  => mb_convert_encoding($class_id, 'UTF-8', 'SJIS-WIN'),
					'name'      => mb_convert_encoding($name, 'UTF-8', 'SJIS-WIN'),
					'name_kana' => mb_convert_encoding($name_kana, 'UTF-8', 'SJIS-WIN'),
					'grade'     => $options['grades'][mb_convert_encoding($grade, 'UTF-8', 'SJIS-WIN')],
					'birthday'  => mb_convert_encoding($birthday, 'UTF-8', 'SJIS-WIN'),
					'email'     => mb_convert_encoding($email, 'UTF-8', 'SJIS-WIN'),
					'tel'       => mb_convert_encoding($tel, 'UTF-8', 'SJIS-WIN'),
					'memo'      => mb_convert_encoding($memo, 'UTF-8', 'SJIS-WIN'),
					'image_01'  => mb_convert_encoding($image_01, 'UTF-8', 'SJIS-WIN'),
					'image_02'  => mb_convert_encoding($image_02, 'UTF-8', 'SJIS-WIN'),
					'public'    => $options['publics'][mb_convert_encoding($public, 'UTF-8', 'SJIS-WIN')]
				))
			);

			//入力データを検証＆登録
			$warnings = validate_members($post['class']);
			if (empty($warnings)) {
				if ($_POST['operation'] == 'update') {
					//データ編集
					$resource = db_update(array(
						'update' => DATABASE_PREFIX . 'members',
						'set'    => array(
							'created'   => $post['class']['created'],
							'modified'  => $post['class']['modified'],
							'deleted'   => $post['class']['deleted'],
							'class_id'  => $post['class']['class_id'],
							'name'      => $post['class']['name'],
							'name_kana' => $post['class']['name_kana'],
							'grade'     => $post['class']['grade'],
							'birthday'  => $post['class']['birthday'],
							'email'     => $post['class']['email'],
							'tel'       => $post['class']['tel'],
							'memo'      => $post['class']['memo'],
							'image_01'  => $post['class']['image_01'],
							'image_02'  => $post['class']['image_02'],
							'public'    => $post['class']['public']
						),
						'where'  => array(
							'id = :id',
							array(
								'id' => $post['class']['id']
							)
						)
					));
					if (!$resource) {
						db_rollback();

						error('データを編集できません。');
					}
				} else {
					//データ登録
					$resource = db_insert(array(
						'insert_into' => DATABASE_PREFIX . 'members',
						'values'      => array(
							'id'        => $post['class']['id'],
							'created'   => $post['class']['created'],
							'modified'  => $post['class']['modified'],
							'deleted'   => $post['class']['deleted'],
							'class_id'  => $post['class']['class_id'],
							'name'      => $post['class']['name'],
							'name_kana' => $post['class']['name_kana'],
							'grade'     => $post['class']['grade'],
							'birthday'  => $post['class']['birthday'],
							'email'     => $post['class']['email'],
							'tel'       => $post['class']['tel'],
							'memo'      => $post['class']['memo'],
							'image_01'  => $post['class']['image_01'],
							'image_02'  => $post['class']['image_02'],
							'public'    => $post['class']['public']
						)
					));
					if (!$resource) {
						db_rollback();

						error('データを登録できません。');
					}
				}
			} else {
				foreach ($warnings as $warning) {
					$all_warnings[] = '[' . $i . '行目] ' . $warning;
				}
			}

			$i++;
		}

		fclose($fp);

		if (empty($all_warnings)) {
			return array();
		} else {
			return $all_warnings;
		}
	} else {
		return array('ファイルを読み込めません。');
	}
}

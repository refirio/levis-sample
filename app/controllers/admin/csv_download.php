<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	//名簿を出力
	$data = member_export();

	//CSVダウンロード
	header('Content-Type: text/plain');
	header('Content-Disposition: attachment; filename="' . DATABASE_PREFIX . 'members.csv"');

	echo $data;

	exit;
}

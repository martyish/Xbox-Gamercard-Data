<?php
require('xbox.php');

$xbox = new XboxGamercard();
$data = $xbox->build_request('Major Nelson', 'ja-JP');

print_r($data);
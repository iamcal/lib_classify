<?php
	$raw = file_get_contents('samples.json');
	$db = json_decode($raw, true);

	print_r(array_keys($db['extnames']));

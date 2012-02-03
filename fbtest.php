<?php

function fromTxtToArray($filename){
	$fh = fopen($filename, "rb");
	$data = fread($fh, filesize($filename));
	fclose($fh);

	$array = explode("\n",$data);
	$rows = $array[0];
	unset($array[0]);

	return array('rows' => $rows, 'data' => $array);
}

$data = fromTxtToArray('input.txt');

foreach ($data['data'] as $k => $v){
	echo $v."\n";
}
?>
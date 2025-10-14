<?php

$xml_str = file_get_contents('php://input');

echo json_encode($xml_str);

?>
<?php
$file = 'specs_log.xlsx';
if (file_exists($file)) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"specs_log.xlsx\"");
    readfile($file);
    exit;
} else {
    echo "Excel file not found!";
}

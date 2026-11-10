<?php
declare(strict_types=1);

require __DIR__.'/app/Release.php';

$file = __DIR__.'/dist/'.Release::FILE;
if (!is_file($file)) {
    http_response_code(404);
    exit('The release archive is available with the source checkout.');
}
header('Content-Type: application/zip');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="'.Release::FILE.'"');
header('Content-Length: '.filesize($file));
readfile($file);


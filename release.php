<?php
declare(strict_types=1);

require __DIR__.'/app/lang.php';
require __DIR__.'/app/Release.php';

$file = __DIR__.'/dist/'.Release::FILE;
if (!is_file($file)) {
    http_response_code(404);
    exit(ltText('the_release_archive_is_available_with'));
}
header('Content-Type: application/zip');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="'.Release::FILE.'"');
header('Content-Length: '.filesize($file));
readfile($file);


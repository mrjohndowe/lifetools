<?php
declare(strict_types=1);

final class Release
{
    public const FILE = 'lifetools-1.0.0.zip';

    public static function build(): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new Problem('Enable extension=zip to build a release.');
        }
        $output = LT_ROOT.'/dist/'.self::FILE;
        $temp = $output.'.tmp';
        $files = [
            '.env.example','.gitignore','.htaccess','index.php','router.php','release.php','lifetools.config.example.json',
            'docker-compose.yml','README.md','ARCHITECTURE.md','SECURITY.md','DEMO.md','SUBMISSION.md','CHECKLIST.md','bin/lifetools',
        ];
        foreach (['app','bin','assets','tests','data','config','uploads','backups','storage','dist'] as $dir) {
            foreach (glob(LT_ROOT.'/'.$dir.'/*') ?: [] as $path) {
                if (is_file($path) && ($dir === 'assets' || in_array($dir,['app','tests'],true) && str_ends_with($path,'.php'))) {
                    $files[] = $dir.'/'.basename($path);
                }
            }
            if ($dir !== 'assets') {
                $files[] = $dir.'/.htaccess';
                $files[] = $dir.'/index.php';
            }
        }
        $zip = new ZipArchive();
        if ($zip->open($temp,ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Problem('Could not create the release archive.');
        }
        try {
            foreach (array_unique($files) as $file) {
                if (!is_file(LT_ROOT.'/'.$file) || !$zip->addFile(LT_ROOT.'/'.$file,'lifetools/'.$file)) {
                    throw new Problem("Missing release source: $file");
                }
            }
        } finally {
            $zip->close();
        }
        if (!rename($temp,$output)) {
            throw new Problem('Could not publish the release file.');
        }
        return ['file'=>$output,'entries'=>count(array_unique($files)),'sha256'=>hash_file('sha256',$output)];
    }
}


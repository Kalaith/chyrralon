<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    $root . '/public',
    $root . '/src',
    $root . '/tests',
];

$failures = [];
foreach ($paths as $path) {
    if (!is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        $command = sprintf('php -l %s 2>&1', escapeshellarg($file->getPathname()));
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            $failures[] = $file->getPathname() . PHP_EOL . implode(PHP_EOL, $output);
        }
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL . PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo 'PHP syntax OK' . PHP_EOL;

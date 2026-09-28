<?php

declare(strict_types=1);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function filesUnder(string $root): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $entry) {
        if ($entry->isLink()) {
            throw new RuntimeException('Unexpected symbolic link in scratch payload.');
        }
        if ($entry->isFile()) {
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($entry->getPathname(), strlen($root) + 1));
            $files[$relative] = file_get_contents($entry->getPathname());
        }
    }
    ksort($files, SORT_STRING);
    return $files;
}

function removeOwnedTree(string $path, string $testsRoot): void
{
    if (dirname($path) !== $testsRoot || !str_starts_with(basename($path), '.package-manager-check-run-') || is_link($path)) {
        throw new RuntimeException('Refusing to remove a path outside this harness scratch directory.');
    }
    if (!is_dir($path)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        $entry->isDir() && !$entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($path);
}

function writeTamperedArchive(string $sourcePath, string $targetPath, string $changedName): void
{
    $source = new ZipArchive();
    $target = new ZipArchive();
    check($source->open($sourcePath, ZipArchive::RDONLY) === true, 'Could not open fixture package.');
    check($target->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 'Could not create tampered package.');
    $changed = false;
    try {
        for ($index = 0; $index < $source->numFiles; $index++) {
            $name = $source->getNameIndex($index);
            $contents = $source->getFromIndex($index);
            check(is_string($name) && is_string($contents), 'Could not read fixture package entry.');
            if ($name === $changedName) {
                $contents .= "tampered\n";
                $changed = true;
            }
            check($target->addFromString($name, $contents), "Could not copy package entry '{$name}'.");
        }
    } finally {
        $source->close();
        $target->close();
    }
    check($changed, "Fixture package does not contain '{$changedName}'.");
}

try {
    $projectRoot = realpath(dirname(__DIR__));
    $testsRoot = realpath(__DIR__);
    check(is_string($projectRoot) && is_string($testsRoot) && dirname($testsRoot) === $projectRoot, 'Harness must run from this project.');
    $archivePath = isset($argv[1]) ? realpath($argv[1]) : false;
    check(is_string($archivePath) && is_file($archivePath), 'Pass a generated fixture archive path.');
    check(str_starts_with($archivePath, $projectRoot . DIRECTORY_SEPARATOR), 'Fixture archive must be inside the project.');

    require_once '/var/www/html/HerikaServer/lib/plugin_package_manager.php';
    check(class_exists(ZipArchive::class), 'PHP ZipArchive is unavailable.');
    $package = new ZipArchive();
    check($package->open($archivePath, ZipArchive::RDONLY) === true, 'Fixture package is not a readable ZIP.');
    $outer = json_decode((string)$package->getFromName('manifest.json'), true, 64, JSON_THROW_ON_ERROR);
    check(($outer['schema_version'] ?? null) === 4, 'Fixture does not use schema 4.');
    check(($outer['name'] ?? null) === 'mind_poisoning' && ($outer['version'] ?? null) === '0.1.0', 'Unexpected fixture identity.');
    $expectedFiles = [];
    for ($index = 0; $index < $package->numFiles; $index++) {
        $name = $package->getNameIndex($index);
        if (is_string($name) && str_starts_with($name, 'server/') && !str_ends_with($name, '/')) {
            $contents = $package->getFromIndex($index);
            check(is_string($contents), "Could not read server payload '{$name}'.");
            $expectedFiles[substr($name, strlen('server/'))] = $contents;
        }
    }
    $package->close();
    ksort($expectedFiles, SORT_STRING);
    check($expectedFiles !== [], 'Fixture package has no server payload.');

    $scratchRoot = $testsRoot . DIRECTORY_SEPARATOR . '.package-manager-check-run-' . bin2hex(random_bytes(6));
    check(mkdir($scratchRoot, 0770), 'Could not create harness scratch root.');
    $migrationCalled = false;
    try {
        $migrationRunner = static function (string $targetDir, string $pluginName, array $migrations) use (&$migrationCalled): void {
            $migrationCalled = true;
            throw new RuntimeException('Unexpected migration runner call.');
        };
        $serverRoot = $scratchRoot . DIRECTORY_SEPARATOR . 'server';
        $stateRoot = $scratchRoot . DIRECTORY_SEPARATOR . 'state';
        $manager = new DwemerPluginPackageManager($serverRoot, $stateRoot, $migrationRunner);

        $contents = file_get_contents($archivePath);
        check(is_string($contents) && $contents !== '', 'Could not read fixture archive bytes.');
        $chunks = str_split($contents, DwemerPluginPackageManager::MAX_UPLOAD_CHUNK_BYTES);
        $upload = $manager->startChunkedUpload('mind_poisoning', '0.1.0', 'mind_poisoning-0.1.0.dwpkg', strlen($contents), count($chunks));
        $result = null;
        foreach ($chunks as $index => $chunk) {
            $result = $manager->appendUploadChunk($upload['upload_id'], $index, $chunk);
        }
        check(($result['complete'] ?? false) === true, 'Chunked upload did not complete.');
        check(($result['job']['status'] ?? null) === 'completed', 'Package manager did not complete installation.');

        $targetRoot = $serverRoot . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . 'mind_poisoning';
        check(filesUnder($targetRoot) === $expectedFiles, 'Installed payload bytes differ from archive source.');
        check(($manager->probe('mind_poisoning', '0.1.0')['upload_required'] ?? true) === false, 'Installed package state is missing.');

        file_put_contents($targetRoot . DIRECTORY_SEPARATOR . 'preserved.txt', 'prior scratch payload');
        $before = filesUnder($targetRoot);
        $tamperedPath = $scratchRoot . DIRECTORY_SEPARATOR . 'tampered.dwpkg';
        writeTamperedArchive($archivePath, $tamperedPath, 'server/influence.php');
        $rejected = false;
        try {
            $manager->installArchive($tamperedPath);
        } catch (DwemerPluginPackageException $error) {
            check(str_contains($error->getMessage(), 'Checksum mismatch'), 'Tampered archive failed for an unexpected reason.');
            $rejected = true;
        }
        check($rejected, 'Checksum-tampered archive was accepted.');
        check(filesUnder($targetRoot) === $before, 'Rejected package changed the previous scratch payload.');
        check(($manager->probe('mind_poisoning', '0.1.0')['upload_required'] ?? true) === false, 'Rejected package changed installed state.');
        check(!$migrationCalled, 'Migration runner was unexpectedly invoked.');

        echo "PASS: schema-4 fixture uploaded and installed through the pinned manager in scratch roots.\n";
        echo 'PASS: installed bytes match archive payload (' . count($expectedFiles) . " files).\n";
        echo "PASS: checksum-tampered archive rejected; prior payload and installed state preserved.\n";
        echo "PASS: injected migration runner was not called.\n";
    } finally {
        removeOwnedTree($scratchRoot, $testsRoot);
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

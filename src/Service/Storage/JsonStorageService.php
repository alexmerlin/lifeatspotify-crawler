<?php

namespace App\Service\Storage;

use Symfony\Component\Filesystem\Filesystem;

use function file_get_contents;
use function json_decode;
use function json_encode;

/**
 * Class JsonStorageService
 * @package App\Service\Storage
 */
class JsonStorageService
{
    /**
     * @param string $path
     * @param array $data
     */
    public function dump(string $path, array $data): void
    {
        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($path, json_encode($data));
    }

    /**
     * @param string $path
     * @return array|null
     */
    public function load(string $path): ?array
    {
        $string = file_get_contents($path);
        return json_decode($string, true);
    }
}

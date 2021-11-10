<?php

namespace HL\Storage;

use GuzzleHttp\Client;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

class Adapter implements FilesystemAdapter
{

    private $client;
    function __construct(string $key, string $server)
    {
        $this->client = new Client([
            'base_uri' => $server,
            "verify" => false,
            "http_errors" => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $key
            ]
        ]);
    }

    function fileExists(string $path): bool
    {
        $response = $this->client->head($path);
        return $response->getStatusCode() == 200;
    }

    function write(string $path, string $contents, Config $config): void
    {
        $this->client->put($path, [
            'body' => $contents
        ]);
    }

    function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, (string)stream_get_contents($contents), $config);
    }

    function read(string $path): string
    {
        $response = $this->client->get($path);
        return $response->getBody()->getContents();
    }

    function readStream(string $path)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $this->read($path));
        rewind($stream);
        return $stream;
    }

    function delete(string $path): void
    {
        $this->client->delete($path);
    }

    function deleteDirectory(string $path): void
    {
        if (!str_ends_with($path, '/')) {
            $path .= "/";
        }
        $this->client->delete($path);
    }

    function createDirectory(string $path, Config $config): void
    {
        if (!str_ends_with($path, '/')) {
            $path .= "/";
        }
        $this->client->put($path);
    }

    function setVisibility(string $path, string $visibility): void
    {
    }

    function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, null, null, null);
    }

    function mimeType(string $path): FileAttributes
    {
        $response = $this->client->head($path);
        $mimeType = $response->getHeaderLine("Content-Type");
        return  new FileAttributes($path, null, null, null, $mimeType);
    }

    function lastModified(string $path): FileAttributes
    {
        $response = $this->client->head($path);
        $lastModified = $response->getHeaderLine("Last-Modified");
        return new FileAttributes($path, null, null, $lastModified, null);
    }

    function fileSize(string $path): FileAttributes
    {
        $response = $this->client->head($path);
        $fileSize = $response->getHeaderLine("Content-Length");
        return new FileAttributes($path, $fileSize, null, null, null,);
    }

    function listContents(string $path, bool $deep): iterable
    {
        if (!str_ends_with($path, '/')) {
            $path .= "/";
        }

        $response = $this->client->get($path);
        $data = json_decode($response->getBody()->getContents(), true);
        $ret = [];
        foreach ($data as $d) {
            if ($d['type'] == 'file') {
                $ret[] = new FileAttributes($d['path'], $d['size'], null, $d['modified'], $d['mime']);
            } else {
                $ret[] = new DirectoryAttributes($d['path'], null, $d['modified']);
            }
        }

        return $ret;
    }

    function move(string $source, string $destination, Config $config): void
    {
        $this->client->request('MOVE', $source, [
            'headers' => [
                'Destination' => $destination
            ]
        ]);
    }

    function copy(string $source, string $destination, Config $config): void
    {
        $this->client->request('COPY', $source, [
            'headers' => [
                'Destination' => $destination
            ]
        ]);
    }
}

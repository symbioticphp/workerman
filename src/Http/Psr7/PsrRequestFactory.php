<?php

namespace Symbiotic\Workerman\Http\Psr7;


use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symbiotic\Http\ServerRequest;
use Workerman\Protocols\Http\Request;

class PsrRequestFactory
{
    public function __construct(
        private ServerRequestFactoryInterface $serverRequestFactory,
        private StreamFactoryInterface $streamFactory,
        private UploadedFileFactoryInterface $uploadedFileFactory
    ) {
    }

    public function createByWorkermanRequest(Request $workermanRequest, bool $secure = false): ServerRequestInterface
    {
        $request = new ServerRequest(
            $workermanRequest->method(),
            'http' . ($secure ? 's' : '') . '://' . $workermanRequest->host() . $workermanRequest->uri(),
            $workermanRequest->header(), $workermanRequest->rawBody(),
            $workermanRequest->protocolVersion()
        );

        $request = $request->withCookieParams($workermanRequest->cookie());
        $request = $request->withQueryParams($workermanRequest->get());
        $request = $request->withParsedBody($workermanRequest->post());
        $request = $request->withUploadedFiles($this->uploadedFiles($workermanRequest->file()));

        return $request;
    }

    /**
     * @param array<string, array<string, int|string>> $files
     *
     * @return array<string, UploadedFileInterface>
     */
    private function uploadedFiles(array $files): array
    {
        $uploadedFiles = [];
        foreach ($files as $key => $file) {
            $uploadedFiles[$key] = isset($file['tmp_name']) ? $this->createUploadedFile($file) : $this->uploadedFiles(
                $file
            );
        }

        return $uploadedFiles;
    }

    /**
     * @param array<string, int|string> $file
     */
    private function createUploadedFile(array $file): UploadedFileInterface
    {
        try {
            $stream = $this->streamFactory->createStreamFromFile($file['tmp_name']);
        } catch (\RuntimeException) {
            $stream = $this->streamFactory->createStream();
        }

        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $file['size'],
            $file['error'],
            $file['name'],
            $file['type']
        );
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->serverRequestFactory->createServerRequest($method, $uri, $serverParams);
    }
}
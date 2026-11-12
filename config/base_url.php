<?php

function youwellBasePath(): string
{
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $applicationRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');

    if ($documentRoot === false || $applicationRoot === false) {
        return '';
    }

    if ($applicationRoot === $documentRoot) {
        return '';
    }

    $documentRootPrefix = rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $comparison = DIRECTORY_SEPARATOR === '\\'
        ? strncasecmp($applicationRoot, $documentRootPrefix, strlen($documentRootPrefix))
        : strncmp($applicationRoot, $documentRootPrefix, strlen($documentRootPrefix));

    if ($comparison !== 0) {
        return '';
    }

    $relativePath = substr($applicationRoot, strlen($documentRootPrefix));
    return '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
}

<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

$filePath = $argv[1];

if (!file_exists($filePath)) {
    throw new Exception("Passed argument must be a file.");
}

$document = new DOMDocument();
$document->load($filePath);

$fileNodes = $document->getElementsByTagName('file');

/** @var DOMElement $node */
foreach ($fileNodes as $node) {
    $name = $node->getAttribute('name');
    if ($name && file_exists($name)) {
        // Resolve the realpath of the file.
        $realName = realpath($name);
        if ($realName !== $name) {
            $node->setAttribute('name', $realName);
        }
    }
}

$document->save($filePath);

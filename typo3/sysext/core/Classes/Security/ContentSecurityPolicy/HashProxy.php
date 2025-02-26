<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Representation of Content-Security-Policy hash source, acting as proxy on
 * files and URLs, to be resolved later when resource contents are actually compiled.
 */
final class HashProxy implements \JsonSerializable, SourceValueInterface
{
    private HashType $type = HashType::sha256;
    private ?string $glob = null;
    /**
     * @var list<string>
     */
    private ?array $urls = null;

    public static function glob(string $glob): self
    {
        $pattern = GeneralUtility::getFileAbsFileName($glob);
        $files = array_filter(glob($pattern), 'is_file');
        if ($files === []) {
            throw new \LogicException('Glob pattern did not resolve any files', 1678615628);
        }
        $target = new self();
        $target->glob = $glob;
        return $target;
    }

    public static function urls(string ...$urls): self
    {
        if ($urls === []) {
            throw new \LogicException('No URL provided', 1678617132);
        }
        foreach ($urls as $url) {
            if (!self::isValidUrl($url)) {
                throw new \LogicException(
                    sprintf('Value "%s" is not a valid file-like URL', $url),
                    1678616641
                );
            }
        }
        $target = new self();
        $target->urls = $urls;
        return $target;
    }

    public static function knows(string $value): bool
    {
        return str_starts_with($value, "'hash-proxy-") && $value[-1] === "'";
    }

    public static function parse(string $value): self
    {
        if (!self::knows($value)) {
            throw new \LogicException(sprintf('Parsing "%s" is not known', $value), 1678619052);
        }
        // extract from `'hash-proxy-[...]'`
        $value = substr($value, 12, -1);
        $properties = json_decode($value, true, 4, JSON_THROW_ON_ERROR);
        if (!empty($properties['glob'])) {
            $target = self::glob($properties['glob']);
        } elseif (!empty($properties['urls'])) {
            $target = self::urls(...$properties['urls']);
        } else {
            throw new \LogicException('Cannot parse payload', 1678619395);
        }
        return $target->withType(HashType::from($properties['type'] ?? ''));
    }

    public function withType(HashType $type): self
    {
        if ($this->type === $type) {
            return $this;
        }
        $target = clone $this;
        $target->type = $type;
        return $target;
    }

    public function isEmpty(): bool
    {
        return $this->glob === null && $this->urls === null;
    }

    public function compile(): ?string
    {
        if ($this->isEmpty()) {
            return null;
        }
        $hashes = array_map(
            fn (string $hash): string => sprintf("'%s-%s'", $this->type->value, $hash),
            $this->compileHashValues()
        );
        return implode(' ', $hashes);
    }

    public function serialize(): ?string
    {
        if ($this->isEmpty()) {
            return null;
        }
        return sprintf("'hash-proxy-%s'", json_encode($this, JSON_UNESCAPED_SLASHES));
    }

    public function jsonSerialize(): mixed
    {
        return [
            'type' => $this->type,
            'glob' => $this->glob,
            'urls' => $this->urls,
        ];
    }

    /**
     * Checks whether the given value is a valid URI and has a file-like part
     */
    private static function isValidUrl(string $value): bool
    {
        try {
            $uri = new Uri($value);
        } catch (\InvalidArgumentException) {
            return false;
        }
        return basename($uri->getPath()) !== '';
    }

    private function compileHashValues(): array
    {
        if ($this->glob !== null) {
            $pattern = GeneralUtility::getFileAbsFileName($this->glob);
            $files = array_filter(glob($pattern), 'is_file');
            return array_map(
                fn (string $file): string => base64_encode(
                    hash_file($this->type->value, $file, true)
                ),
                $files
            );
        }
        if ($this->urls !== null) {
            $contents = array_filter(array_map([$this, 'fetchResponseBody'], $this->urls));
            return array_map(
                fn (string $content): string => base64_encode(
                    hash($this->type->value, $content, true)
                ),
                $contents
            );
        }
        return [];
    }

    private function fetchResponseBody(string $url): string
    {
        $factory = GeneralUtility::makeInstance(RequestFactory::class);
        try {
            return (string)$factory->request($url)->getBody();
        } catch (\Throwable) {
            return '';
        }
    }
}

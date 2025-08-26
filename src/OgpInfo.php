<?php

declare(strict_types=1);

namespace Yohacoo\OgpInfo;

use DOMDocument;

final class OgpInfo
{
  /** @var string Path for the cache directory */
  private static $cacheDir = __DIR__ . '/.ogp-cache';

  /** @var int Cache TTL in seconds */
  private static $cacheTtl = 60 * 60 * 24;

  /**
   * Set cache directory.
   * @param string $dir Path for the cache directory
   */
  public static function setCacheDir(string $dir): void
  {
    self::$cacheDir = $dir;
  }

  /**
   * Set cache TTL.
   * @param int $ttl Cache TTL in seconds
   */
  public static function setCacheTtl(int $ttl): void
  {
    self::$cacheTtl = $ttl;
  }

  /** @var string URL to retrieve OGP information */
  private $url;

  /** @var int HTTP status code */
  private $httpStatus;

  /** @var int Timestamp when data is retrieved via HTTP  */
  private $timestamp;

  /** @var array<string, string> Retrieved values */
  private $values = array();

  /**
   * Constructor
   * @param string $url URL
   */
  private function __construct(string $url)
  {
    $this->url = $url;
  }

  /**
   * Get URL for retrieving information.
   * @return string URL
   */
  public function getUrl(): string
  {
    return $this->url;
  }

  /**
   * Get HTTP status code.
   * @return int HTTP status code
   */
  public function getHttpStatus(): int
  {
    return $this->httpStatus;
  }

  /**
   * Get timestamp.
   * @return int Timestamp when data is retrieved via HTTP
   */
  public function getTimestamp(): int
  {
    return $this->timestamp;
  }

  /**
   * Check if this info has key and value exists.
   * @param string $key Key
   * @return bool Return true if value exists
   */
  private function has(string $key): bool
  {
    return array_key_exists($key, $this->values);
  }

  /**
   * Get value.
   * @param string $key Key
   * @return string Value
   */
  public function get(string $key): string
  {
    return isset($this->values[$key]) ? $this->values[$key] : '';
  }

  /**
   * Set value.
   * @param string $key Key
   * @param string $value Value
   */
  private function set(string $key, string $value): void
  {
    $this->values[$key] = $value;
  }

  /**
   * Get cache file path.
   * @return string Path for the cache file
   */
  private static function getCachePath($url): string
  {
    $host = parse_url($url, PHP_URL_HOST);
    $md5 = md5(urlencode($url));
    $filename = "{$host}-{$md5}.json";
    return self::$cacheDir . '/' . $filename;
  }

  /**
   * Save to cache.
   * Create the cache directory if it does not exist.
   */
  private function saveToCache(): void
  {
    if (!file_exists(self::$cacheDir)) {
      mkdir(self::$cacheDir, 0777, true);
    }

    $cache_data = array(
      'url' => $this->url,
      'httpStatus' => $this->httpStatus,
      'timestamp' => $this->timestamp,
      'values' => $this->values,
    );

    $path = self::getCachePath($this->url);
    $json = json_encode($cache_data);
    file_put_contents($path, $json);
  }

  /**
   * Read the cache file and create an instance if it exists.
   * @param string $url URL for find the cache file
   * @return OgpInfo | null OgpInfo object or null if cache file does not exist
   */
  private static function fromCache(string $url): OgpInfo | null
  {
    $path = self::getCachePath($url);
    if (!file_exists($path)) return null;

    $json = file_get_contents($path);
    $data = json_decode($json, true);

    $info = new self($url);

    $info->httpStatus = $data['httpStatus'];
    $info->timestamp = $data['timestamp'];
    $info->values = $data['values'];

    if (time() > $info->timestamp + self::$cacheTtl) {
      unlink($path);
      return null;
    }

    return $info;
  }

  /**
   * Retrieve OGP information via HTTP.
   * @param string $url URL 
   * @return OgpInfo OgpInfo object
   */
  public static function retrieve(string $url): OgpInfo
  {
    // Check the cache
    $info = self::fromCache($url);
    if ($info) return $info;

    $info = new self($url);

    // Get contents via HTTP
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    $html = curl_exec($ch);
    curl_close($ch);

    // Check HTTP status
    $info->httpStatus = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $info->timestamp = time();
    if ($info->httpStatus !== 200) return $info;

    // Prevent garbled characters
    $html = mb_encode_numericentity($html, [0x80, 0x10ffff, 0, 0x1fffff]);

    // Create DOM tree
    $doc = new DOMDocument();
    @$doc->loadHTML($html);

    // Check meta tags
    $metas = $doc->getElementsByTagName('meta');
    foreach ($metas as $meta) {
      $property = $meta->getAttribute('property');
      $name = $meta->getAttribute('name');
      $content = $meta->getAttribute('content');

      if (str_starts_with($property, 'og:') && !$info->has($property)) {
        $info->set($property, $content);
      }

      if (str_starts_with($property, 'fb:') && !$info->has($property)) {
        $info->set($property, $content);
      }

      if (str_starts_with($name, 'twitter:') && !$info->has($name)) {
        $info->set($name, $content);
      }

      if ($name === 'description' && !$info->has($name)) {
        $info->set($name, $content);
      }
    }

    // Check title tags
    $titles = $doc->getElementsByTagName('title');
    if ($titles->length > 0) {
      $title = $titles->item(0);
      $info->set('title', $title->firstChild->textContent);
    }

    // Save to cache
    $info->saveToCache();

    return $info;
  }
}

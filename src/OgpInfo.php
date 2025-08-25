<?php

declare(strict_types=1);

namespace Yohacoo\OgpInfo;

use DOMDocument;

final class OgpInfo
{
  /**
   * URL to retrieve OGP information
   */
  private $url;

  /**
   * HTTP status code
   */
  private $httpStatus;

  /**
   * Retrieved values
   */
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
   * Check if this info has key and value exists.
   * @param string $key key
   * @return bool true if value exists
   */
  private function has(string $key): bool
  {
    return array_key_exists($key, $this->values);
  }

  /**
   * Get value.
   * @param string $key key
   * @return string value
   */
  public function get(string $key): string
  {
    return isset($this->values[$key]) ? $this->values[$key] : '';
  }

  /**
   * Set value.
   * @param string $key key
   * @param string $value value
   */
  private function set(string $key, string $value): void
  {
    $this->values[$key] = $value;
  }

  /**
   * Magic method to get value.
   * @param string $key key
   * @return string value
   */
  public function __get(string $key): string
  {
    return $this->get($key);
  }

  /**
   * Retrieve OGP information via HTTP.
   * @param string $url URL 
   * @return OgpInfo OgpInfo object
   */
  public static function retrieve(string $url): OgpInfo
  {
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
    if ($info->httpStatus !== 200) return $info;

    // Prevent garbled characters
    $html = mb_encode_numericentity($html, [0x80, 0x10ffff, 0, 0x1fffff]);

    // Create DOM tree
    $doc = new DOMDocument();
    @$doc->loadHTML($html);

    // Check meta tags
    $meta_elements = $doc->getElementsByTagName('meta');
    foreach ($meta_elements as $meta_element) {
      $property = $meta_element->getAttribute('property');
      $name = $meta_element->getAttribute('name');
      $content = $meta_element->getAttribute('content');

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
    $title_elements = $doc->getElementsByTagName('title');
    if ($title_elements->length > 0) {
      $title_element = $title_elements->item(0);
      $info->set('title', $title_element->firstChild->textContent);
    }

    return $info;
  }
}

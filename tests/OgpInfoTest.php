<?php

declare(strict_types=1);

namespace Yohacoo\OgpInfo;

use PHPUnit\Framework\TestCase;

final class OgpInfoTest extends TestCase
{
  public function testUrl(): void
  {
    $url = 'http://localhost:8000/test.html';

    $info = OgpInfo::retrieve($url);

    $this->assertSame($url, $info->getUrl());
    $this->assertSame(200, $info->getHttpStatus());
    $this->assertSame('Test Title', $info->get('title'));
    $this->assertSame('Test Description', $info->get('description'));
    $this->assertSame('en', $info->get('og:locale'));
    $this->assertSame('http://localhost:8000/', $info->get('og:url'));
    $this->assertSame('website', $info->get('og:type'));
    $this->assertSame('Test OG Title', $info->get('og:title'));
    $this->assertSame('Test OG Description', $info->get('og:description'));
    $this->assertSame('http://localhost:8000/ogp.png', $info->get('og:image'));
    $this->assertSame('Test OG Site', $info->get('og:site_name'));
    $this->assertSame('1234567890123456', $info->get('fb:app_id'));
    $this->assertSame('summary_large_image', $info->get('twitter:card'));
    $this->assertSame('http://localhost:8000/ogp-twitter.png', $info->get('twitter:image'));
  }

  public function testExternal(): void
  {
    $file = './tests/external.json';
    if (!file_exists($file)) return;

    echo $file;
    $json = file_get_contents($file);
    $data = json_decode($json, true);

    $sites = $data['sites'];

    foreach ($sites as $site) {
      $url = $site['url'];
      $values = $site['values'];

      $info = OgpInfo::retrieve($url);
      foreach ($values as $key => $value) {
        $this->assertStringStartsWith($value, $info->get($key), "URL: {$url}\nKey: {$key}");
      }
    }
  }
}

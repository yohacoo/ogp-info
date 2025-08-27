<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Yohacoo\OgpInfo\OgpInfo;

final class OgpInfoTest extends TestCase
{
  protected function setUp(): void
  {
    OgpInfo::setCacheDir(__DIR__ . '/.ogp-cache');
  }

  private static function getCacheFile($url): string
  {
    $getCacheFile = new ReflectionMethod(OgpInfo::class, 'getCacheFile');
    $getCacheFile->setAccessible(true);
    return $getCacheFile->invoke(null, $url);
  }

  public function testUrl(): void
  {
    $url = 'http://localhost:8000/test.html';

    $info = OgpInfo::retrieve($url);

    $this->assertSame($url, $info->getUrl());
    $this->assertSame(200, $info->getHttpStatus());
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
    $this->assertSame('Test Title', $info->get('title'));
    $this->assertSame('Test Description', $info->get('description'));
  }

  public function testCache(): void
  {
    $url = 'http://localhost:8000/test.html';
    $file = self::getCacheFile($url);

    if (file_exists($file)) {
      unlink($file);
    }
    $this->assertFileDoesNotExist($file);

    $info = OgpInfo::retrieve($url);
    $this->assertFileExists($file);

    sleep(1);
    $cachedInfo = OgpInfo::retrieve($url);
    $this->assertSame($info->getTimestamp(), $cachedInfo->getTimestamp());

    OgpInfo::setCacheTtl(0);
    $newInfo = OgpInfo::retrieve($url);
    $this->assertGreaterThan($info->getTimestamp(), $newInfo->getTimestamp());

    OgpInfo::setCacheTtl(60 * 60 * 24);
  }

  public function testClearCache(): void
  {
    $url = 'http://localhost:8000/test.html';
    $file = self::getCacheFile($url);

    $info = OgpInfo::retrieve($url);
    $this->assertFileExists($file);

    $timestamp = new ReflectionProperty(OgpInfo::class, 'timestamp');
    $timestamp->setAccessible(true);
    $timestamp->setValue($info, time() - 60 * 60 * 24 * 2);

    $saveToCache = new ReflectionMethod(OgpInfo::class, 'saveToCache');
    $saveToCache->setAccessible(true);
    $saveToCache->invoke($info);
    $this->assertFileExists($file);

    OgpInfo::clearCache();
    $this->assertFileDoesNotExist($file);
  }

  public function testExternal(): void
  {
    $file = './tests/external.json';
    if (!file_exists($file)) return;

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    $sites = $data['sites'];

    foreach ($sites as $site) {
      $url = $site['url'];
      $values = $site['values'];

      $info = OgpInfo::retrieve($url);
      $this->assertSame(200, $info->getHttpStatus());

      foreach ($values as $key => $value) {
        $this->assertStringStartsWith($value, $info->get($key), "URL: {$url}\nKey: {$key}");
      }
    }
  }
}

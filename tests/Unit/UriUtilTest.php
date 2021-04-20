<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use UBC\LTI\Utils\UriUtil;

class UriUtilTest extends TestCase
{

    public function testIsSameSiteDifferentPath()
    {
        $url1 = "http://example.com/somepath";
        $url2 = "http://example.com/anotherpath";

        $this->assertTrue(UriUtil::isSameSite($url1, $url2));
    }

    public function testIsSameSiteTrailingSlash()
    {
        $url1 = "http://example.com";
        $url2 = "http://example.com/";

        $this->assertTrue(UriUtil::isSameSite($url1, $url2));
    }

    public function testIsSameSiteDifferentHost()
    {
        $url1 = "http://example.com/somepath";
        $url2 = "http://example.org/anotherpath";

        $this->assertFalse(UriUtil::isSameSite($url1, $url2));
    }

    public function testIsSameSiteDifferentPort()
    {
        $url1 = "http://example.com:2000/somepath";
        $url2 = "http://example.com:8080/anotherpath";

        $this->assertFalse(UriUtil::isSameSite($url1, $url2));
    }

    public function testIsSameSiteDifferentScheme()
    {
        $url1 = "https://example.com/somepath";
        $url2 = "http://example.com/anotherpath";

        $this->assertFalse(UriUtil::isSameSite($url1, $url2));
    }
}

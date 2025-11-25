<?php

namespace TCG\Voyager\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;

class AssetsTest extends TestCase
{
    protected static bool $assetsPublished = false;

    public function setUp(): void
    {
        parent::setUp();

        $this->publishAssetsToPublic();
        Auth::loginUsingId(1);
    }

    protected function publishAssetsToPublic(): void
    {
        if (self::$assetsPublished) {
            return;
        }

        /** @var Filesystem $filesystem */
        $filesystem = app(Filesystem::class);
        $vendorPath = public_path('vendor');
        $voyagerPath = $vendorPath.DIRECTORY_SEPARATOR.'voyager';

        if (!$filesystem->isDirectory($vendorPath)) {
            $filesystem->makeDirectory($vendorPath, 0755, true);
        }

        if ($filesystem->isDirectory($voyagerPath)) {
            $filesystem->deleteDirectory($voyagerPath);
        }

        $filesystem->copyDirectory(dirname(__DIR__).'/publishable/assets', $voyagerPath);

        self::$assetsPublished = true;
    }

    public function testVoyagerAssetHelperPointsToPublicPath(): void
    {
        $assetUrl = voyager_asset('css/app.css');

        $this->assertSame(asset('vendor/voyager/css/app.css'), $assetUrl);
        $this->assertFileExists(public_path('vendor/voyager/css/app.css'));
    }

    public static function urlProvider()
    {
        return [
            ['../dummy_content/pages/page1.jpg'],
            ['..../dummy_content/pages/page1.jpg'],
            ['images/../../dummy_content/pages/page1.jpg'],
            ['....//dummy_content/pages/page1.jpg'],
            ['..\\dummy_content/pages/page1.jpg'],
            ['....\\dummy_content/pages/page1.jpg'],
            ['images/..\\..\\dummy_content/pages/page1.jpg'],
            ['images/....\\....\\dummy_content/pages/page1.jpg'],
        ];
    }

    #[DataProvider('urlProvider')]
    public function testVoyagerAssetHelperStripsTraversal($url)
    {
        $assetUrl = voyager_asset($url);

        $this->assertStringStartsWith(asset('vendor/voyager'), $assetUrl);
        $this->assertStringNotContainsString('..', $assetUrl);
    }
}

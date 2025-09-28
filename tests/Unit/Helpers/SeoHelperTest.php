<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SeoHelper;
use Tests\TestCase;

class SeoHelperTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_generate_meta_title()
    {
        $title = SeoHelper::generateMetaTitle('Game Dashboard');

        $this->assertStringContainsString('Game Dashboard', $title);
        $this->assertIsString($title);
    }

    /**
     * @test
     */
    public function it_can_generate_meta_title_with_site_name()
    {
        $title = SeoHelper::generateMetaTitle('Game Dashboard', 'My Game Site');

        $this->assertStringContainsString('Game Dashboard', $title);
        $this->assertStringContainsString('My Game Site', $title);
    }

    /**
     * @test
     */
    public function it_can_generate_meta_description()
    {
        $description = SeoHelper::generateMetaDescription('This is a test page for the game dashboard');

        $this->assertEquals('This is a test page for the game dashboard', $description);
        $this->assertIsString($description);
    }

    /**
     * @test
     */
    public function it_can_truncate_long_meta_description()
    {
        $longDescription = str_repeat('This is a very long description. ', 20);
        $description = SeoHelper::generateMetaDescription($longDescription, 160);

        $this->assertLessThanOrEqual(160, strlen($description));
        $this->assertStringEndsWith('...', $description);
    }

    /**
     * @test
     */
    public function it_can_generate_canonical_url()
    {
        $url = SeoHelper::generateCanonicalUrl('/game/dashboard');

        $this->assertStringContainsString('/game/dashboard', $url);
        $this->assertIsString($url);
    }

    /**
     * @test
     */
    public function it_can_generate_canonical_url_with_base_url()
    {
        $url = SeoHelper::generateCanonicalUrl('/game/dashboard', 'https://example.com');

        $this->assertEquals('https://example.com/game/dashboard', $url);
    }

    /**
     * @test
     */
    public function it_can_generate_og_tags()
    {
        $tags = SeoHelper::generateOgTags([
            'title' => 'Game Dashboard',
            'description' => 'Manage your game from this dashboard',
            'image' => 'https://example.com/image.jpg',
            'url' => 'https://example.com/game/dashboard',
        ]);

        $this->assertIsArray($tags);
        $this->assertArrayHasKey('og:title', $tags);
        $this->assertArrayHasKey('og:description', $tags);
        $this->assertArrayHasKey('og:image', $tags);
        $this->assertArrayHasKey('og:url', $tags);
        $this->assertEquals('Game Dashboard', $tags['og:title']);
        $this->assertEquals('Manage your game from this dashboard', $tags['og:description']);
        $this->assertEquals('https://example.com/image.jpg', $tags['og:image']);
        $this->assertEquals('https://example.com/game/dashboard', $tags['og:url']);
    }

    /**
     * @test
     */
    public function it_can_generate_twitter_tags()
    {
        $tags = SeoHelper::generateTwitterTags([
            'title' => 'Game Dashboard',
            'description' => 'Manage your game from this dashboard',
            'image' => 'https://example.com/image.jpg',
            'card' => 'summary_large_image',
        ]);

        $this->assertIsArray($tags);
        $this->assertArrayHasKey('twitter:title', $tags);
        $this->assertArrayHasKey('twitter:description', $tags);
        $this->assertArrayHasKey('twitter:image', $tags);
        $this->assertArrayHasKey('twitter:card', $tags);
        $this->assertEquals('Game Dashboard', $tags['twitter:title']);
        $this->assertEquals('Manage your game from this dashboard', $tags['twitter:description']);
        $this->assertEquals('https://example.com/image.jpg', $tags['twitter:image']);
        $this->assertEquals('summary_large_image', $tags['twitter:card']);
    }

    /**
     * @test
     */
    public function it_can_generate_breadcrumb_json_ld()
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'https://example.com'],
            ['name' => 'Game', 'url' => 'https://example.com/game'],
            ['name' => 'Dashboard', 'url' => 'https://example.com/game/dashboard'],
        ];

        $jsonLd = SeoHelper::generateBreadcrumbJsonLd($breadcrumbs);

        $this->assertIsString($jsonLd);
        $this->assertStringContainsString('@type', $jsonLd);
        $this->assertStringContainsString('BreadcrumbList', $jsonLd);
        $this->assertStringContainsString('Home', $jsonLd);
        $this->assertStringContainsString('Game', $jsonLd);
        $this->assertStringContainsString('Dashboard', $jsonLd);
    }

    /**
     * @test
     */
    public function it_can_generate_organization_json_ld()
    {
        $organization = [
            'name' => 'My Game Company',
            'url' => 'https://example.com',
            'logo' => 'https://example.com/logo.png',
            'description' => 'The best gaming company',
        ];

        $jsonLd = SeoHelper::generateOrganizationJsonLd($organization);

        $this->assertIsString($jsonLd);
        $this->assertStringContainsString('@type', $jsonLd);
        $this->assertStringContainsString('Organization', $jsonLd);
        $this->assertStringContainsString('My Game Company', $jsonLd);
        $this->assertStringContainsString('https://example.com', $jsonLd);
        $this->assertStringContainsString('https://example.com/logo.png', $jsonLd);
    }

    /**
     * @test
     */
    public function it_can_generate_website_json_ld()
    {
        $website = [
            'name' => 'My Game Site',
            'url' => 'https://example.com',
            'description' => 'The best gaming website',
            'potentialAction' => [
                'target' => 'https://example.com/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $jsonLd = SeoHelper::generateWebsiteJsonLd($website);

        $this->assertIsString($jsonLd);
        $this->assertStringContainsString('@type', $jsonLd);
        $this->assertStringContainsString('WebSite', $jsonLd);
        $this->assertStringContainsString('My Game Site', $jsonLd);
        $this->assertStringContainsString('SearchAction', $jsonLd);
    }

    /**
     * @test
     */
    public function it_can_generate_robots_meta_tag()
    {
        $robots = SeoHelper::generateRobotsMetaTag(['index', 'follow', 'noarchive']);

        $this->assertEquals('index, follow, noarchive', $robots);
    }

    /**
     * @test
     */
    public function it_can_generate_robots_meta_tag_with_single_directive()
    {
        $robots = SeoHelper::generateRobotsMetaTag(['noindex']);

        $this->assertEquals('noindex', $robots);
    }

    /**
     * @test
     */
    public function it_can_generate_hreflang_tags()
    {
        $languages = [
            'en' => 'https://example.com/en/page',
            'es' => 'https://example.com/es/page',
            'fr' => 'https://example.com/fr/page',
        ];

        $hreflangs = SeoHelper::generateHreflangTags($languages);

        $this->assertIsArray($hreflangs);
        $this->assertCount(3, $hreflangs);
        $this->assertArrayHasKey('en', $hreflangs);
        $this->assertArrayHasKey('es', $hreflangs);
        $this->assertArrayHasKey('fr', $hreflangs);
        $this->assertEquals('https://example.com/en/page', $hreflangs['en']);
        $this->assertEquals('https://example.com/es/page', $hreflangs['es']);
        $this->assertEquals('https://example.com/fr/page', $hreflangs['fr']);
    }

    /**
     * @test
     */
    public function it_can_clean_text_for_seo()
    {
        $dirtyText = "This is a <script>alert('test')</script> text with HTML & special chars!";
        $cleanText = SeoHelper::cleanTextForSeo($dirtyText);

        $this->assertStringNotContainsString('<script>', $cleanText);
        $this->assertStringNotContainsString('alert', $cleanText);
        $this->assertStringContainsString('This is a', $cleanText);
        $this->assertStringContainsString('text with HTML', $cleanText);
    }

    /**
     * @test
     */
    public function it_can_generate_slug_from_text()
    {
        $text = 'This is a Test Title with Special Characters!';
        $slug = SeoHelper::generateSlug($text);

        $this->assertEquals('this-is-a-test-title-with-special-characters', $slug);
        $this->assertStringNotContainsString(' ', $slug);
        $this->assertStringNotContainsString('!', $slug);
    }

    /**
     * @test
     */
    public function it_can_generate_slug_with_custom_separator()
    {
        $text = 'This is a Test Title';
        $slug = SeoHelper::generateSlug($text, '_');

        $this->assertEquals('this_is_a_test_title', $slug);
        $this->assertStringContainsString('_', $slug);
        $this->assertStringNotContainsString('-', $slug);
    }

    /**
     * @test
     */
    public function it_can_validate_meta_title_length()
    {
        $shortTitle = 'Short Title';
        $longTitle = str_repeat('Very Long Title ', 10);

        $this->assertTrue(SeoHelper::validateMetaTitleLength($shortTitle));
        $this->assertFalse(SeoHelper::validateMetaTitleLength($longTitle));
    }

    /**
     * @test
     */
    public function it_can_validate_meta_description_length()
    {
        $shortDescription = 'Short description';
        $longDescription = str_repeat('Very long description ', 20);

        $this->assertTrue(SeoHelper::validateMetaDescriptionLength($shortDescription));
        $this->assertFalse(SeoHelper::validateMetaDescriptionLength($longDescription));
    }

    /**
     * @test
     */
    public function it_can_extract_keywords_from_text()
    {
        $text = 'This is a game about strategy and battles. Players can build villages and fight enemies.';
        $keywords = SeoHelper::extractKeywords($text, 5);

        $this->assertIsArray($keywords);
        $this->assertLessThanOrEqual(5, count($keywords));
        $this->assertContains('game', $keywords);
        $this->assertContains('strategy', $keywords);
        $this->assertContains('battles', $keywords);
    }

    /**
     * @test
     */
    public function it_can_generate_sitemap_url_entry()
    {
        $entry = SeoHelper::generateSitemapUrlEntry(
            'https://example.com/game/dashboard',
            '2023-01-01',
            'weekly',
            0.8
        );

        $this->assertIsArray($entry);
        $this->assertArrayHasKey('loc', $entry);
        $this->assertArrayHasKey('lastmod', $entry);
        $this->assertArrayHasKey('changefreq', $entry);
        $this->assertArrayHasKey('priority', $entry);
        $this->assertEquals('https://example.com/game/dashboard', $entry['loc']);
        $this->assertEquals('2023-01-01', $entry['lastmod']);
        $this->assertEquals('weekly', $entry['changefreq']);
        $this->assertEquals(0.8, $entry['priority']);
    }

    /**
     * @test
     */
    public function it_can_generate_meta_tags_array()
    {
        $data = [
            'title' => 'Game Dashboard',
            'description' => 'Manage your game',
            'keywords' => 'game, dashboard, strategy',
            'canonical' => 'https://example.com/game/dashboard',
            'robots' => 'index, follow',
        ];

        $metaTags = SeoHelper::generateMetaTagsArray($data);

        $this->assertIsArray($metaTags);
        $this->assertArrayHasKey('title', $metaTags);
        $this->assertArrayHasKey('description', $metaTags);
        $this->assertArrayHasKey('keywords', $metaTags);
        $this->assertArrayHasKey('canonical', $metaTags);
        $this->assertArrayHasKey('robots', $metaTags);
    }
}

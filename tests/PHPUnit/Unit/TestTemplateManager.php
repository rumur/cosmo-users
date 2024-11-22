<?php

namespace Rumur\WordPress\CosmoUsers\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use Rumur\WordPress\CosmoUsers\Support\FileLocator\Locator;
use Rumur\WordPress\CosmoUsers\Support\Template\Manager;
use Rumur\WordPress\CosmoUsers\Support\FileLocator\Theme;
use Rumur\WordPress\CosmoUsers\Support\FileLocator\Direct;
use Rumur\WordPress\CosmoUsers\Support\FileLocator\NotFound;
use Rumur\WordPress\CosmoUsers\Support\Template\TemplateNotFound;

class TestTemplateManager extends TestCase
{
    /**
     * @return MockObject[]
     */
    public function locatorsProvider(): array
    {
        $themeLocator = $this->createMock(Theme::class);
        $directLocator = $this->createMock(Direct::class);

        return [
            [$themeLocator, $directLocator],
        ];
    }

    /**
     * @dataProvider locatorsProvider
     */
    public function testLocatesTemplateSuccessfully(
        MockObject|Locator $themeLocator,
        MockObject|Locator $directLocator
    ): void {
        $themeLocator->method('locate')->willThrowException(new NotFound());
        $directLocator->method('locate')->willReturn('/path/to/template.php');

        $manager = new Manager($themeLocator, $directLocator);

        $this->assertEquals('/path/to/template.php', $manager->locate('template'));
    }

    /**
     * @dataProvider locatorsProvider
     */
    public function testThrowsExceptionWhenTemplateNotFound(
        MockObject|Locator $themeLocator,
        MockObject|Locator $directLocator
    ): void {
        $themeLocator->method('locate')->willThrowException(new NotFound());
        $directLocator->method('locate')->willThrowException(new NotFound());

        $manager = new Manager($themeLocator, $directLocator);

        $this->expectException(TemplateNotFound::class);
        $manager->locate('non-existent-template');
    }

    /**
     * @dataProvider locatorsProvider
     */
    public function testRendersTemplateSuccessfully(
        MockObject|Locator $themeLocator,
        MockObject|Locator $directLocator
    ): void {
        $themeLocator->method('locate')->willThrowException(new NotFound());
        $directLocator->method('locate')->willReturn(__DIR__ . '/Fixtures/template.php');

        $manager = new Manager($themeLocator, $directLocator);

        $this->assertStringContainsString('<!-- /path/to/template.php -->', $manager->render('template'));
    }

    /**
     * @dataProvider locatorsProvider
     */
    public function testsReturnsErrorCommentWhenTemplateNotFound(
        MockObject|Locator $themeLocator,
        MockObject|Locator $directLocator
    ): void {
        $themeLocator->method('locate')->willThrowException(new TemplateNotFound());
        $directLocator->method('locate')->willThrowException(new TemplateNotFound());

        $manager = new Manager($themeLocator, $directLocator);

        // Doing it wrong is expected to be called.
        $this->expected_doing_it_wrong = [
            'Rumur\WordPress\CosmoUsers\Support\Template\Manager::render',
        ];

        $this->assertStringContainsString('<!-- [non-existent-template] is not possible to locate. -->', $manager->render('non-existent-template'));
    }

    /**
     * @dataProvider locatorsProvider
     */
    public function testRendersGivenArgsSuccessfully(
        MockObject|Locator $themeLocator,
        MockObject|Locator $_
    ): void {
        $themeLocator->method('locate')->willThrowException(new NotFound());
        $directLocator = new Direct(__DIR__ . '/Fixtures');

        $manager = new Manager($themeLocator, $directLocator);

        $this->assertStringContainsString('<p>foo:bar</p>', $manager->render('template', ['foo' => 'bar']));
    }
}

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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Uri;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ResourceViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * @test
     */
    public function renderingFailsWithNonExtSyntaxWithoutExtensionNameWithPsr7Request()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639672666);
        $view = new StandaloneView();
        $view->getRenderingContext()->setRequest(new ServerRequest());
        $view->setTemplateSource('<f:uri.resource path="Icons/Extension.svg" />');
        $view->render();
    }

    /**
     * @test
     */
    public function renderingFailsWhenExtensionNameNotSetInExtbaseRequest(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('<f:uri.resource path="Icons/Extension.svg" />');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1640097205);
        $view->render();
    }

    public function renderWithoutRequestDataProvider(): \Generator
    {
        yield 'render returns URI using UpperCamelCase extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="Core" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
        ];
        yield 'render returns URI using extension key as extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="core" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
        ];
        yield 'render returns URI using EXT: syntax' => [
            '<f:uri.resource path="EXT:core/Resources/Public/Icons/Extension.svg" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
        ];
    }

    /**
     * @test
     * @dataProvider renderWithoutRequestDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $view = new StandaloneView();
        // Make sure rendering context has no (extbase) request
        $view->getRenderingContext()->setRequest(null);
        $view->setTemplateSource($template);
        self::assertEquals($expected, $view->render());
    }

    public function renderWithExtbaseRequestDataProvider(): \Generator
    {
        yield 'render returns URI using extensionName from Extbase Request' => [
            '<f:uri.resource path="Icons/Extension.svg" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
        ];
        yield 'render gracefully trims leading slashes from path' => [
            '<f:uri.resource path="/Icons/Extension.svg" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
        ];
        yield 'render returns URI using UpperCamelCase extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="Core" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
        ];
        yield 'render returns URI using extension key as extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="core" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
        ];
        yield 'render returns URI using EXT: syntax' => [
            '<f:uri.resource path="EXT:core/Resources/Public/Icons/Extension.svg" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg',
        ];
    }

    /**
     * @test
     * @dataProvider renderWithExtbaseRequestDataProvider
     */
    public function renderWithExtbaseRequest(string $template, string $expected): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($template);
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Core');
        $extbaseRequest = (new Request())->withAttribute('extbase', $extbaseRequestParameters);
        $view->getRenderingContext()->setRequest($extbaseRequest);
    }
}

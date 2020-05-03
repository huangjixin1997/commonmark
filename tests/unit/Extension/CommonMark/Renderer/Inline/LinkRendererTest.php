<?php

/*
 * This file is part of the league/commonmark package.
 *
 * (c) Colin O'Dell <colinodell@gmail.com>
 *
 * Original code based on the CommonMark JS reference parser (https://bitly.com/commonmark-js)
 *  - (c) John MacFarlane
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\CommonMark\Tests\Unit\Extension\CommonMark\Renderer\Inline;

use League\CommonMark\Configuration\Configuration;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\CommonMark\Renderer\Inline\LinkRenderer;
use League\CommonMark\Node\Inline\AbstractInline;
use League\CommonMark\Tests\Unit\Renderer\FakeChildNodeRenderer;
use League\CommonMark\Util\HtmlElement;
use PHPUnit\Framework\TestCase;

class LinkRendererTest extends TestCase
{
    /**
     * @var LinkRenderer
     */
    protected $renderer;

    protected function setUp(): void
    {
        $this->renderer = new LinkRenderer();
        $this->renderer->setConfiguration(new Configuration());
    }

    public function testRenderWithTitle()
    {
        $inline = new Link('http://example.com/foo.html', '::label::', '::title::');
        $inline->data['attributes'] = ['id' => '::id::', 'title' => '::title2::', 'href' => '::href2::'];
        $fakeRenderer = new FakeChildNodeRenderer();

        $result = $this->renderer->render($inline, $fakeRenderer);

        $this->assertTrue($result instanceof HtmlElement);
        $this->assertEquals('a', $result->getTagName());
        $this->assertStringContainsString('http://example.com/foo.html', $result->getAttribute('href'));
        $this->assertStringContainsString('::title::', $result->getAttribute('title'));
        $this->assertStringContainsString('::children::', $result->getContents(true));
        $this->assertStringContainsString('::id::', $result->getAttribute('id'));
    }

    public function testRenderWithoutTitle()
    {
        $inline = new Link('http://example.com/foo.html', '::label::');
        $fakeRenderer = new FakeChildNodeRenderer();

        $result = $this->renderer->render($inline, $fakeRenderer);

        $this->assertTrue($result instanceof HtmlElement);
        $this->assertEquals('a', $result->getTagName());
        $this->assertStringContainsString('http://example.com/foo.html', $result->getAttribute('href'));
        $this->assertNull($result->getAttribute('title'));
        $this->assertStringContainsString('::children::', $result->getContents(true));
    }

    public function testRenderAllowUnsafeLink()
    {
        $this->renderer->setConfiguration(new Configuration([
            'allow_unsafe_links' => true,
        ]));

        $inline = new Link('javascript:void(0)');
        $fakeRenderer = new FakeChildNodeRenderer();

        $result = $this->renderer->render($inline, $fakeRenderer);

        $this->assertTrue($result instanceof HtmlElement);
        $this->assertStringContainsString('javascript:void(0)', $result->getAttribute('href'));
    }

    public function testRenderDisallowUnsafeLink()
    {
        $this->renderer->setConfiguration(new Configuration([
            'allow_unsafe_links' => false,
        ]));

        $inline = new Link('javascript:void(0)');
        $fakeRenderer = new FakeChildNodeRenderer();

        $result = $this->renderer->render($inline, $fakeRenderer);

        $this->assertTrue($result instanceof HtmlElement);
        $this->assertEquals('', $result->getAttribute('href'));
    }

    public function testRenderWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $inline = $this->getMockForAbstractClass(AbstractInline::class);
        $fakeRenderer = new FakeChildNodeRenderer();

        $this->renderer->render($inline, $fakeRenderer);
    }

    public function testRenderWithExternalTarget()
    {
        $inline = new Link('http://example.com/foo.html', '::label::', '::title::');
        $inline->data['attributes'] = ['target' => '_blank'];
        $fakeRenderer = new FakeChildNodeRenderer();

        $result = $this->renderer->render($inline, $fakeRenderer);

        $this->assertTrue($result instanceof HtmlElement);
        $this->assertEquals('a', $result->getTagName());
        $this->assertStringContainsString('http://example.com/foo.html', $result->getAttribute('href'));
        $this->assertStringContainsString('noopener', $result->getAttribute('rel'));
        $this->assertStringContainsString('noreferrer', $result->getAttribute('rel'));
    }
}
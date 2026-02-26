<?php

namespace Ginkida\AgentRunner\Tests\Unit\Tools;

use Ginkida\AgentRunner\Tests\Fixtures\DummyTool;
use Ginkida\AgentRunner\Tools\ToolRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ToolRegistryTest extends TestCase
{
    private ToolRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ToolRegistry;
    }

    #[Test]
    public function it_registers_and_retrieves_a_tool(): void
    {
        $tool = new DummyTool;
        $this->registry->register($tool);

        $this->assertSame($tool, $this->registry->get('dummy_tool'));
    }

    #[Test]
    public function it_returns_null_for_unknown_tool(): void
    {
        $this->assertNull($this->registry->get('nonexistent'));
    }

    #[Test]
    public function it_checks_if_tool_exists(): void
    {
        $this->assertFalse($this->registry->has('dummy_tool'));

        $this->registry->register(new DummyTool);

        $this->assertTrue($this->registry->has('dummy_tool'));
    }

    #[Test]
    public function it_returns_all_registered_names(): void
    {
        $this->assertSame([], $this->registry->names());

        $this->registry->register(new DummyTool);

        $this->assertSame(['dummy_tool'], $this->registry->names());
    }

    #[Test]
    public function it_returns_all_registered_tools(): void
    {
        $tool = new DummyTool;
        $this->registry->register($tool);

        $all = $this->registry->all();

        $this->assertCount(1, $all);
        $this->assertSame($tool, $all['dummy_tool']);
    }

    #[Test]
    public function it_builds_definitions_for_all_tools(): void
    {
        $this->registry->register(new DummyTool);

        $definitions = $this->registry->definitions();

        $this->assertCount(1, $definitions);
        $this->assertSame('dummy_tool', $definitions[0]['name']);
        $this->assertSame('A dummy tool for testing', $definitions[0]['description']);
        $this->assertArrayHasKey('properties', $definitions[0]['parameters']);
    }

    #[Test]
    public function it_builds_definitions_filtered_by_names(): void
    {
        $this->registry->register(new DummyTool);

        $definitions = $this->registry->definitions(['dummy_tool']);
        $this->assertCount(1, $definitions);

        $definitions = $this->registry->definitions(['nonexistent']);
        $this->assertCount(0, $definitions);
    }

    #[Test]
    public function it_builds_definitions_for_null_filter(): void
    {
        $this->registry->register(new DummyTool);

        $definitions = $this->registry->definitions(null);

        $this->assertCount(1, $definitions);
    }

    #[Test]
    public function it_overwrites_tool_with_same_name(): void
    {
        $tool1 = new DummyTool;
        $tool2 = new DummyTool;

        $this->registry->register($tool1);
        $this->registry->register($tool2);

        $this->assertSame($tool2, $this->registry->get('dummy_tool'));
        $this->assertCount(1, $this->registry->all());
    }
}

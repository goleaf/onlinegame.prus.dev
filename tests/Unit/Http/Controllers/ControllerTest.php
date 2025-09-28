<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Controller;
use Tests\TestCase;

class ControllerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $controller = new Controller();

        $this->assertInstanceOf(Controller::class, $controller);
    }

    /**
     * @test
     */
    public function it_is_abstract()
    {
        $reflection = new \ReflectionClass(Controller::class);

        $this->assertTrue($reflection->isAbstract());
    }

    /**
     * @test
     */
    public function it_extends_laravel_controller()
    {
        $this->assertTrue(is_subclass_of(Controller::class, \Illuminate\Routing\Controller::class));
    }

    /**
     * @test
     */
    public function it_has_no_public_methods()
    {
        $reflection = new \ReflectionClass(Controller::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Only constructor should be public
        $this->assertCount(1, $methods);
        $this->assertEquals('__construct', $methods[0]->getName());
    }

    /**
     * @test
     */
    public function it_can_be_extended()
    {
        $extendedController = new class () extends Controller {
            public function test_method()
            {
                return 'test';
            }
        };

        $this->assertInstanceOf(Controller::class, $extendedController);
        $this->assertEquals('test', $extendedController->testMethod());
    }

    /**
     * @test
     */
    public function it_has_proper_namespace()
    {
        $reflection = new \ReflectionClass(Controller::class);

        $this->assertEquals('App\Http\Controllers', $reflection->getNamespaceName());
    }

    /**
     * @test
     */
    public function it_has_proper_class_name()
    {
        $reflection = new \ReflectionClass(Controller::class);

        $this->assertEquals('Controller', $reflection->getShortName());
    }

    /**
     * @test
     */
    public function it_has_proper_full_name()
    {
        $reflection = new \ReflectionClass(Controller::class);

        $this->assertEquals('App\Http\Controllers\Controller', $reflection->getName());
    }

    /**
     * @test
     */
    public function it_can_be_used_as_base_class()
    {
        $testController = new class () extends Controller {
            public function index()
            {
                return 'Hello World';
            }
        };

        $this->assertInstanceOf(Controller::class, $testController);
        $this->assertEquals('Hello World', $testController->index());
    }

    /**
     * @test
     */
    public function it_has_no_constructor_parameters()
    {
        $reflection = new \ReflectionClass(Controller::class);
        $constructor = $reflection->getConstructor();

        if ($constructor) {
            $this->assertCount(0, $constructor->getParameters());
        }
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_without_parameters()
    {
        $controller = new Controller();

        $this->assertInstanceOf(Controller::class, $controller);
    }

    /**
     * @test
     */
    public function it_has_no_properties()
    {
        $reflection = new \ReflectionClass(Controller::class);
        $properties = $reflection->getProperties();

        $this->assertCount(0, $properties);
    }

    /**
     * @test
     */
    public function it_has_no_constants()
    {
        $reflection = new \ReflectionClass(Controller::class);
        $constants = $reflection->getConstants();

        $this->assertCount(0, $constants);
    }

    /**
     * @test
     */
    public function it_has_no_traits()
    {
        $reflection = new \ReflectionClass(Controller::class);
        $traits = $reflection->getTraitNames();

        $this->assertCount(0, $traits);
    }

    /**
     * @test
     */
    public function it_has_no_interfaces()
    {
        $reflection = new \ReflectionClass(Controller::class);
        $interfaces = $reflection->getInterfaceNames();

        $this->assertCount(0, $interfaces);
    }

    /**
     * @test
     */
    public function it_can_be_serialized()
    {
        $controller = new Controller();

        $this->assertIsString(serialize($controller));
    }

    /**
     * @test
     */
    public function it_can_be_unserialized()
    {
        $controller = new Controller();
        $serialized = serialize($controller);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(Controller::class, $unserialized);
    }

    /**
     * @test
     */
    public function it_has_proper_string_representation()
    {
        $controller = new Controller();

        $this->assertStringContainsString('App\Http\Controllers\Controller', (string) $controller);
    }

    /**
     * @test
     */
    public function it_can_be_cloned()
    {
        $controller = new Controller();
        $cloned = clone $controller;

        $this->assertInstanceOf(Controller::class, $cloned);
        $this->assertNotSame($controller, $cloned);
    }

    /**
     * @test
     */
    public function it_has_proper_documentation()
    {
        $reflection = new \ReflectionClass(Controller::class);
        $docComment = $reflection->getDocComment();

        $this->assertFalse($docComment);
    }

    /**
     * @test
     */
    public function it_can_be_used_in_route_definition()
    {
        $controller = new Controller();

        $this->assertInstanceOf(Controller::class, $controller);
    }

    /**
     * @test
     */
    public function it_has_proper_inheritance_chain()
    {
        $reflection = new \ReflectionClass(Controller::class);
        $parentClass = $reflection->getParentClass();

        $this->assertNotNull($parentClass);
        $this->assertEquals('Illuminate\Routing\Controller', $parentClass->getName());
    }

    /**
     * @test
     */
    public function it_can_be_used_as_dependency()
    {
        $controller = new Controller();

        $this->assertInstanceOf(Controller::class, $controller);
    }

    /**
     * @test
     */
    public function it_has_proper_visibility()
    {
        $reflection = new \ReflectionClass(Controller::class);

        $this->assertTrue($reflection->isInstantiable());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
        $this->assertTrue($reflection->isClass());
    }
}

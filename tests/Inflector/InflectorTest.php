<?php

declare(strict_types=1);

namespace League\Container\Test\Inflector;

use League\Container\Container;
use League\Container\Inflector\Inflector;
use League\Container\Test\Asset\Bar;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class InflectorTest extends TestCase
{
    public function testInflectorSetsExpectedMethodCalls(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $inflector = (new Inflector('Type'))->setContainer($container);

        $inflector->invokeMethod('method1', ['arg1']);

        $inflector->invokeMethods([
            'method2' => ['arg1'],
            'method3' => ['arg1']
        ]);

        $methods = (new ReflectionClass($inflector))->getProperty('methods');
        $methods->setAccessible(true);

        $this->assertSame($methods->getValue($inflector), [
            'method1' => ['arg1'],
            'method2' => ['arg1'],
            'method3' => ['arg1']
        ]);
    }

    public function testInflectorSetsExpectedProperties(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();
        $inflector = (new Inflector('Type'))->setContainer($container);

        $inflector->setProperty('property1', 'value');

        $inflector->setProperties([
            'property2' => 'value',
            'property3' => 'value'
        ]);

        $properties = (new ReflectionClass($inflector))->getProperty('properties');
        $properties->setAccessible(true);

        $this->assertSame($properties->getValue($inflector), [
            'property1' => 'value',
            'property2' => 'value',
            'property3' => 'value'
        ]);
    }

    public function testInflectorInflectsWithProperties(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();

        $bar = new class {
        };

        $container
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo(Bar::class))
            ->willReturn(true)
        ;

        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo(Bar::class))
            ->willReturn($bar)
        ;

        $inflector = (new Inflector('Type'))
            ->setContainer($container)
            ->setProperty('bar', Bar::class)
        ;

        $foo = new class {
            public $bar;
        };

        $inflector->inflect($foo);

        $this->assertSame($bar, $foo->bar);
    }

    public function testInflectorInflectsWithMethodCall(): void
    {
        $container = $this->getMockBuilder(Container::class)->getMock();

        $bar = new class {
        };

        $container
            ->expects($this->once())
            ->method('has')
            ->with($this->equalTo(Bar::class))
            ->willReturn(true)
        ;

        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo(Bar::class))
            ->willReturn($bar)
        ;

        $inflector = (new Inflector('Type'))
            ->setContainer($container)
            ->invokeMethod('setBar', [Bar::class])
        ;

        $foo = new class {
            public $bar;
            public function setBar($bar): void
            {
                $this->bar = $bar;
            }
        };

        $inflector->inflect($foo);
        $this->assertSame($bar, $foo->bar);
    }

    public function testInflectorInflectsWithCallback(): void
    {
        $foo = new class {
            public $bar;
            public function setBar($bar): void
            {
                $this->bar = $bar;
            }
        };

        $bar = new class {
        };

        $inflector = new Inflector('Type', callback: function ($object) use ($bar) {
            $object->setBar($bar);
        });

        $inflector->inflect($foo);
        $this->assertSame($bar, $foo->bar);
    }

    public function testInflectorOnlyInflectsOncePerMatch(): void
    {
        $foo = new class {
            public int $count = 0;
            public function tick(): void
            {
                $this->count++;
            }
        };

        $bar = new class {
        };

        $inflector = new Inflector('Type');
        $inflector->oncePerMatch();
        $inflector->invokeMethod('tick', []);

        $inflector->inflect($foo);
        $inflector->inflect($foo);
        $this->assertSame(1, $foo->count);
    }
}

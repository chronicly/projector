<?php
declare(strict_types=1);

namespace Chronhub\Projector\Tests\Feature;

use Chronhub\Projector\Exception\RuntimeException;
use Chronhub\Projector\Tests\InMemoryTestWithOrchestra;

final class ItTestProjectorFactory extends InMemoryTestWithOrchestra
{
    /**
     * @test
     */
    public function it_raise_exception_setting_initialize_twice(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection already initialized");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->initialize(fn(): array => [])
            ->initialize(fn(): array => []);
    }

    /**
     * @test
     */
    public function it_raise_exception_setting_streams_twice(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection streams all|names|categories already set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->fromAll()
            ->fromStreams('user');
    }

    /**
     * @test
     */
    public function it_raise_exception_setting_streams_twice_2(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection streams all|names|categories already set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->fromAll()
            ->fromCategories('user');
    }

    /**
     * @test
     */
    public function it_raise_exception_setting_query_filter_twice(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection query filter already set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition());
    }

    /**
     * @test
     */
    public function it_raise_exception_setting_event_handlers_twice(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection event handlers already set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->whenAny(function () {
            })
            ->whenAny(function () {
            });
    }

    /**
     * @test
     */
    public function it_raise_exception_setting_event_handlers_twice_2(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection event handlers already set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->whenAny(function () {
            })
            ->when([]);
    }

    /**
     * @test
     */
    public function it_raise_exception_setting_event_handlers_twice_3(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection event handlers already set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->when([])
            ->when([]);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_event_handlers_not_set(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection event handlers not set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->initialize(fn(): array => [])
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->fromStreams('user')
            ->run(false);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_query_filter_not_set(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection query filter not set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->initialize(fn(): array => [])
            ->when([])
            ->fromStreams('user')
            ->run(false);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_streams_not_set(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Projection streams all|names|categories not set");

        $projection = $this->projectorManager->createProjection('user');
        $projection
            ->initialize(fn(): array => [])
            ->when([])
            ->withQueryFilter($this->projectorManager->queryScope()->fromIncludedPosition())
            ->run(false);
    }
}

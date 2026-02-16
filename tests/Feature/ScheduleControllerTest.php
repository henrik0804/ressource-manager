<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\Task;
use App\Models\TaskAssignment;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

test('guests are redirected from the schedule page', function (): void {
    get(route('schedule'))->assertRedirect(route('login'));
});

test('users without VisualOverview permission are forbidden', function (): void {
    actingAsUserWithPermissions([
        'read' => [AccessSection::ResourceManagement],
        'write' => [],
    ]);

    get(route('schedule'))->assertForbidden();
});

describe('authorized users', function (): void {
    beforeEach(function (): void {
        actingAsUserWithPermissions([
            'read' => [AccessSection::VisualOverview],
        ]);
    });

    test('can view the schedule page', function (): void {
        Resource::factory()->count(2)->create();

        get(route('schedule'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('schedule/Index')
                ->has('rows', 2)
                ->has('rangeStart')
                ->has('rangeEnd')
                ->has('precision')
            );
    });

    test('schedule includes task assignments as bars', function (): void {
        $resource = Resource::factory()->create();

        TaskAssignment::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::now()->addDays(5),
            'ends_at' => CarbonImmutable::now()->addDays(10),
        ]);

        get(route('schedule', [
            'start' => CarbonImmutable::now()->format('Y-m-d'),
            'end' => CarbonImmutable::now()->addMonths(3)->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('schedule/Index')
                ->has('rows', fn (Assert $rows) => $rows
                    ->has(Resource::count())
                    ->first(fn (Assert $row) => $row
                        ->has('id')
                        ->has('label')
                        ->has('resourceType')
                        ->has('bars', 1)
                        ->has('bars.0.start')
                        ->has('bars.0.end')
                        ->has('bars.0.ganttBarConfig.id')
                        ->has('bars.0.ganttBarConfig.label')
                        ->has('bars.0.ganttBarConfig.style')
                    )
                )
            );
    });

    test('schedule includes resource absences as bars', function (): void {
        $resource = Resource::factory()->create();

        ResourceAbsence::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::now()->addDays(2),
            'ends_at' => CarbonImmutable::now()->addDays(5),
        ]);

        get(route('schedule', [
            'start' => CarbonImmutable::now()->format('Y-m-d'),
            'end' => CarbonImmutable::now()->addMonths(3)->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('schedule/Index')
                ->has('rows', fn (Assert $rows) => $rows
                    ->has(Resource::count())
                    ->first(fn (Assert $row) => $row
                        ->has('bars', 1)
                        ->where('bars.0.ganttBarConfig.id', "absence-{$resource->resourceAbsences->first()->id}")
                        ->etc()
                    )
                )
            );
    });

    test('schedule respects date range query parameters', function (): void {
        $resource = Resource::factory()->create();

        TaskAssignment::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::parse('2026-06-01'),
            'ends_at' => CarbonImmutable::parse('2026-06-10'),
        ]);

        // Request a range that does NOT include the assignment
        get(route('schedule', [
            'start' => '2026-01-01',
            'end' => '2026-02-01',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('schedule/Index')
                ->has('rows', fn (Assert $rows) => $rows
                    ->has(Resource::count())
                    ->first(fn (Assert $row) => $row
                        ->has('bars', 0)
                        ->etc()
                    )
                )
            );
    });

    test('schedule accepts precision parameter', function (): void {
        get(route('schedule', ['precision' => 'week']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('schedule/Index')
                ->where('precision', 'week')
            );
    });

    test('schedule merges assignments and absences for the same resource', function (): void {
        $resource = Resource::factory()->create();

        $now = CarbonImmutable::now();

        TaskAssignment::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => $now->addDays(1),
            'ends_at' => $now->addDays(5),
        ]);

        ResourceAbsence::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => $now->addDays(10),
            'ends_at' => $now->addDays(12),
        ]);

        get(route('schedule', [
            'start' => $now->format('Y-m-d'),
            'end' => $now->addMonths(3)->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('schedule/Index')
                ->has('rows', fn (Assert $rows) => $rows
                    ->has(Resource::count())
                    ->first(fn (Assert $row) => $row
                        ->has('bars', 2)
                        ->etc()
                    )
                )
            );
    });

    test('task assignment falls back to task dates when own dates are null', function (): void {
        $resource = Resource::factory()->create();
        $task = Task::factory()->create([
            'starts_at' => CarbonImmutable::now()->addDays(3),
            'ends_at' => CarbonImmutable::now()->addDays(7),
        ]);

        TaskAssignment::factory()->create([
            'resource_id' => $resource->id,
            'task_id' => $task->id,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        get(route('schedule', [
            'start' => CarbonImmutable::now()->format('Y-m-d'),
            'end' => CarbonImmutable::now()->addMonths(3)->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('schedule/Index')
                ->has('rows', fn (Assert $rows) => $rows
                    ->has(Resource::count())
                    ->first(fn (Assert $row) => $row
                        ->has('bars', 1)
                        ->etc()
                    )
                )
            );
    });
});

<?php

declare(strict_types=1);

use App\Actions\DeleteResourceAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\ResourceQualification;
use App\Models\TaskAssignment;

test('delete resource without dependents succeeds without confirmation', function (): void {
    $resource = Resource::factory()->create();

    app(DeleteResourceAction::class)->handle($resource);

    expect(Resource::query()->count())->toBe(0);
});

test('delete resource with dependents throws without confirmation', function (): void {
    $resource = Resource::factory()->create();
    TaskAssignment::factory()->create(['resource_id' => $resource->id]);

    app(DeleteResourceAction::class)->handle($resource);
})->throws(HasDependentRelationshipsException::class);

test('delete resource with dependents returns dependent counts', function (): void {
    $resource = Resource::factory()->create();
    ResourceQualification::factory()->count(2)->create(['resource_id' => $resource->id]);
    TaskAssignment::factory()->count(3)->create(['resource_id' => $resource->id]);
    ResourceAbsence::factory()->create(['resource_id' => $resource->id]);

    try {
        app(DeleteResourceAction::class)->handle($resource);
    } catch (HasDependentRelationshipsException $e) {
        expect($e->dependents)->toBe([
            'resource_qualifications' => 2,
            'task_assignments' => 3,
            'resource_absences' => 1,
        ]);

        return;
    }

    $this->fail('Expected HasDependentRelationshipsException was not thrown.');
});

test('delete resource with confirmation cascades all dependents', function (): void {
    $resource = Resource::factory()->create();
    ResourceQualification::factory()->create(['resource_id' => $resource->id]);
    TaskAssignment::factory()->create(['resource_id' => $resource->id]);
    ResourceAbsence::factory()->create(['resource_id' => $resource->id]);

    app(DeleteResourceAction::class)->handle($resource, confirmDependencyDeletion: true);

    expect(Resource::query()->count())->toBe(0);
    expect(ResourceQualification::query()->count())->toBe(0);
    expect(TaskAssignment::query()->count())->toBe(0);
    expect(ResourceAbsence::query()->count())->toBe(0);
});

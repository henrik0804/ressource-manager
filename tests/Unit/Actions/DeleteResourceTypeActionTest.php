<?php

declare(strict_types=1);

use App\Actions\DeleteResourceTypeAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\ResourceType;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;

test('delete resource type without dependents succeeds without confirmation', function (): void {
    $resourceType = ResourceType::factory()->create();

    app(DeleteResourceTypeAction::class)->handle($resourceType);

    expect(ResourceType::query()->count())->toBe(0);
});

test('delete resource type with dependents throws without confirmation', function (): void {
    $resourceType = ResourceType::factory()->create();
    Resource::factory()->create(['resource_type_id' => $resourceType->id]);

    app(DeleteResourceTypeAction::class)->handle($resourceType);
})->throws(HasDependentRelationshipsException::class);

test('delete resource type with dependents returns dependent counts', function (): void {
    $resourceType = ResourceType::factory()->create();
    Resource::factory()->count(2)->create(['resource_type_id' => $resourceType->id]);
    Qualification::factory()->count(3)->create(['resource_type_id' => $resourceType->id]);

    try {
        app(DeleteResourceTypeAction::class)->handle($resourceType);
    } catch (HasDependentRelationshipsException $e) {
        expect($e->dependents)->toBe(['resources' => 2, 'qualifications' => 3]);

        return;
    }

    $this->fail('Expected HasDependentRelationshipsException was not thrown.');
});

test('delete resource type with confirmation cascades all dependents', function (): void {
    $resourceType = ResourceType::factory()->create();
    $resource = Resource::factory()->create(['resource_type_id' => $resourceType->id]);
    $qualification = Qualification::factory()->create(['resource_type_id' => $resourceType->id]);

    ResourceQualification::factory()->create([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
    ]);
    TaskAssignment::factory()->create(['resource_id' => $resource->id]);
    TaskRequirement::factory()->create(['qualification_id' => $qualification->id]);

    app(DeleteResourceTypeAction::class)->handle($resourceType, confirmDependencyDeletion: true);

    expect(ResourceType::query()->count())->toBe(0);
    expect(Resource::query()->count())->toBe(0);
    expect(Qualification::query()->count())->toBe(0);
    expect(ResourceQualification::query()->count())->toBe(0);
    expect(TaskAssignment::query()->count())->toBe(0);
    expect(TaskRequirement::query()->count())->toBe(0);
});

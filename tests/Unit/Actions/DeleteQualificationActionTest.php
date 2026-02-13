<?php

declare(strict_types=1);

use App\Actions\DeleteQualificationAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Qualification;
use App\Models\ResourceQualification;
use App\Models\TaskRequirement;

test('delete qualification without dependents succeeds without confirmation', function (): void {
    $qualification = Qualification::factory()->create();

    app(DeleteQualificationAction::class)->handle($qualification);

    expect(Qualification::query()->count())->toBe(0);
});

test('delete qualification with dependents throws without confirmation', function (): void {
    $qualification = Qualification::factory()->create();
    ResourceQualification::factory()->create(['qualification_id' => $qualification->id]);

    app(DeleteQualificationAction::class)->handle($qualification);
})->throws(HasDependentRelationshipsException::class);

test('delete qualification with dependents returns dependent counts', function (): void {
    $qualification = Qualification::factory()->create();
    ResourceQualification::factory()->count(2)->create(['qualification_id' => $qualification->id]);
    TaskRequirement::factory()->count(3)->create(['qualification_id' => $qualification->id]);

    try {
        app(DeleteQualificationAction::class)->handle($qualification);
    } catch (HasDependentRelationshipsException $e) {
        expect($e->dependents)->toBe([
            'resource_qualifications' => 2,
            'task_requirements' => 3,
        ]);

        return;
    }

    $this->fail('Expected HasDependentRelationshipsException was not thrown.');
});

test('delete qualification with confirmation cascades all dependents', function (): void {
    $qualification = Qualification::factory()->create();
    ResourceQualification::factory()->count(2)->create(['qualification_id' => $qualification->id]);
    TaskRequirement::factory()->count(3)->create(['qualification_id' => $qualification->id]);

    app(DeleteQualificationAction::class)->handle($qualification, confirmDependencyDeletion: true);

    expect(Qualification::query()->count())->toBe(0);
    expect(ResourceQualification::query()->count())->toBe(0);
    expect(TaskRequirement::query()->count())->toBe(0);
});

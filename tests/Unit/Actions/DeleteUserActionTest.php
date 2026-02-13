<?php

declare(strict_types=1);

use App\Actions\DeleteUserAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\TaskAssignment;
use App\Models\User;

test('delete user without resource succeeds without confirmation', function (): void {
    $user = User::factory()->create();

    app(DeleteUserAction::class)->handle($user);

    expect(User::query()->count())->toBe(0);
});

test('delete user with resource throws without confirmation', function (): void {
    $user = User::factory()->create();
    Resource::factory()->create(['user_id' => $user->id]);

    app(DeleteUserAction::class)->handle($user);
})->throws(HasDependentRelationshipsException::class);

test('delete user with resource returns dependent counts', function (): void {
    $user = User::factory()->create();
    Resource::factory()->create(['user_id' => $user->id]);

    try {
        app(DeleteUserAction::class)->handle($user);
    } catch (HasDependentRelationshipsException $e) {
        expect($e->dependents)->toBe(['resource' => 1]);

        return;
    }

    $this->fail('Expected HasDependentRelationshipsException was not thrown.');
});

test('delete user with confirmation cascades resource and its dependents', function (): void {
    $user = User::factory()->create();
    $resource = Resource::factory()->create(['user_id' => $user->id]);
    ResourceQualification::factory()->create(['resource_id' => $resource->id]);
    TaskAssignment::factory()->create(['resource_id' => $resource->id]);

    app(DeleteUserAction::class)->handle($user, confirmDependencyDeletion: true);

    expect(User::query()->count())->toBe(0);
    expect(Resource::query()->count())->toBe(0);
    expect(ResourceQualification::query()->count())->toBe(0);
    expect(TaskAssignment::query()->count())->toBe(0);
});

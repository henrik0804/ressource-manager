<?php

declare(strict_types=1);

use App\Actions\DeleteRoleAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\Role;
use App\Models\User;

test('delete role without dependents succeeds without confirmation', function (): void {
    $role = Role::factory()->create();

    app(DeleteRoleAction::class)->handle($role);

    expect(Role::query()->count())->toBe(0);
});

test('delete role with users throws without confirmation', function (): void {
    $role = Role::factory()->create();
    User::factory()->create(['role_id' => $role->id]);

    app(DeleteRoleAction::class)->handle($role);
})->throws(HasDependentRelationshipsException::class);

test('delete role with users returns dependent counts', function (): void {
    $role = Role::factory()->create();
    User::factory()->count(3)->create(['role_id' => $role->id]);

    try {
        app(DeleteRoleAction::class)->handle($role);
    } catch (HasDependentRelationshipsException $e) {
        expect($e->dependents)->toBe(['users' => 3]);

        return;
    }

    $this->fail('Expected HasDependentRelationshipsException was not thrown.');
});

test('delete role with confirmation cascades users and their resources', function (): void {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);
    $resource = Resource::factory()->create(['user_id' => $user->id]);
    ResourceQualification::factory()->create(['resource_id' => $resource->id]);

    app(DeleteRoleAction::class)->handle($role, confirmDependencyDeletion: true);

    expect(Role::query()->count())->toBe(0);
    expect(User::query()->count())->toBe(0);
    expect(Resource::query()->count())->toBe(0);
    expect(ResourceQualification::query()->count())->toBe(0);
});

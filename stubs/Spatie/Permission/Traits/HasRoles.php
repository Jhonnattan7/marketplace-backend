<?php

namespace Spatie\Permission\Traits;

trait HasRoles
{
    public function hasRole(mixed $roles, ?string $guard = null): bool
    {
        return false;
    }

    public function hasPermissionTo(string $permission, ?string $guardName = null): bool
    {
        return false;
    }

    public function assignRole(mixed ...$roles): static
    {
        return $this;
    }

    public function getRoleNames(): array
    {
        return [];
    }

    public function getAllPermissions(): object
    {
        return new class {
            public function pluck(string $value): array
            {
                return [];
            }
        };
    }
}

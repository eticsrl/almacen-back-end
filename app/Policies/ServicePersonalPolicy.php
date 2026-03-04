<?php

namespace App\Policies;

use App\Models\ServicePersonal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServicePersonalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden ver la lista
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ServicePersonal $servicePersonal): bool
    {
        // Todos los usuarios autenticados pueden ver un registro individual
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo admins o usuarios con permiso pueden crear
        return $user->hasRole('admin') || $user->hasPermissionTo('create_service_personal');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ServicePersonal $servicePersonal): bool
    {
        // Solo admins o usuarios con permiso pueden actualizar
        return $user->hasRole('admin') || $user->hasPermissionTo('update_service_personal');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServicePersonal $servicePersonal): bool
    {
        // Solo admins o usuarios con permiso pueden eliminar
        return $user->hasRole('admin') || $user->hasPermissionTo('delete_service_personal');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServicePersonal $servicePersonal): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServicePersonal $servicePersonal): bool
    {
        return $user->hasRole('admin');
    }
}

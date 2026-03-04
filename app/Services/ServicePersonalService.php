<?php

namespace App\Services;

use App\Models\ServicePersonal;
use App\Models\DocumentType;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

class ServicePersonalService
{
    /**
     * Get all service personals with pagination
     */
    public function getAllPaginated(int $perPage = 10): Paginator
    {
        return ServicePersonal::with('documentType')
            ->orderBy('apellidos_nombres', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get all active service personals
     */
    public function getAllActive(): Collection
    {
        return ServicePersonal::active()
            ->with('documentType')
            ->orderBy('apellidos_nombres', 'asc')
            ->get();
    }

    /**
     * Create a new service personal
     */
    public function create(array $data): ServicePersonal
    {
        return ServicePersonal::create($data);
    }

    /**
     * Update a service personal
     */
    public function update(ServicePersonal $servicePersonal, array $data): ServicePersonal
    {
        $servicePersonal->update($data);
        return $servicePersonal->fresh('documentType');
    }

    /**
     * Delete a service personal
     */
    public function delete(ServicePersonal $servicePersonal): bool
    {
        return $servicePersonal->delete();
    }

    /**
     * Soft delete a service personal
     */
    public function softDelete(ServicePersonal $servicePersonal): bool
    {
        return $servicePersonal->delete();
    }

    /**
     * Restore a deleted service personal
     */
    public function restore(int $id): bool
    {
        return ServicePersonal::withTrashed()
            ->where('id', $id)
            ->restore() > 0;
    }

    /**
     * Find a service personal by ID
     */
    public function findById(int $id): ?ServicePersonal
    {
        return ServicePersonal::with('documentType')->find($id);
    }

    /**
     * Search service personals by apellidos_nombres
     */
    public function search(string $term, int $perPage = 10): Paginator
    {
        return ServicePersonal::search($term)
            ->with('documentType')
            ->paginate($perPage);
    }

    /**
     * Get service personals by document type
     */
    public function getByDocumentType(int $documentTypeId): Collection
    {
        return ServicePersonal::byDocumentType($documentTypeId)
            ->active()
            ->orderBy('apellidos_nombres', 'asc')
            ->get();
    }

    /**
     * Activate a service personal
     */
    public function activate(ServicePersonal $servicePersonal): bool
    {
        return $servicePersonal->activate();
    }

    /**
     * Deactivate a service personal
     */
    public function deactivate(ServicePersonal $servicePersonal): bool
    {
        return $servicePersonal->deactivate();
    }

    /**
     * Get count of active service personals
     */
    public function getActiveCount(): int
    {
        return ServicePersonal::active()->count();
    }

    /**
     * Get count of inactive service personals
     */
    public function getInactiveCount(): int
    {
        return ServicePersonal::inactive()->count();
    }

    /**
     * Check if service personal exists by apellidos_nombres
     */
    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $query = ServicePersonal::where('apellidos_nombres', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}

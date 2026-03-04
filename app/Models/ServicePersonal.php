<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePersonal extends Model
{
    use HasFactory;

    protected $table = 'service_personals';

    protected $fillable = [
        'apellidos_nombres',
        'estado',
        'id_service',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'estado' => true,
    ];

    /**
     * Relationships
     */
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'id_service');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('estado', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('estado', false);
    }

    public function scopeByDocumentType($query, $documentTypeId)
    {
        return $query->where('id_service', $documentTypeId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('apellidos_nombres', 'like', "%{$term}%");
    }

    /**
     * Accessors & Mutators
     */
    protected function apellidosNombres(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => ucwords(strtolower($value)),
            set: fn ($value) => strtoupper($value)
        );
    }

    /**
     * Methods
     */
    public function activate(): bool
    {
        return $this->update(['estado' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['estado' => false]);
    }

    public function isActive(): bool
    {
        return $this->estado === true;
    }

    public function isInactive(): bool
    {
        return $this->estado === false;
    }
}
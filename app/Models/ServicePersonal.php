<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePersonal extends Model
{
    use HasFactory;

    protected $fillable = [
        'apellidos_nombres',
        'estado',
        'id_service',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'id_service');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [

        'nombre',
        'nit',
        'direccion',
        'telefono',
        'persona_contacto',
        'celular',
        'email',
        'observaciones',
        'usr',
        'estado_id',

    ];
    public function user()
    {
        return $this->belongsTo(user::class, 'usr');
    }

}

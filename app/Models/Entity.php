<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    use HasFactory;
    protected $fillable = ['descripcion','estado'];
    public function users()
{
    return $this->belongsToMany(User::class, 'users_entities', 'entities_id', 'users_id')
                ->withPivot('estado', 'observaciones')
                ->withTimestamps();
}
/*
Para asignar una entidad a un usuario con datos extra:
$user = User::find(1);
$user->entities()->attach($entityId, [
    'estado' => 'V',
    'observaciones' => 'Registro inicial'
]);
Para recuperar los datos de la tabla intermedia:
$user = User::with('entities')->find(1);
foreach ($user->entities as $entity) {
    echo $entity->pivot->estado; // Accedes a la columna 'estado'
}
*/
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;
    protected $table = 'document_types';
    protected $fillable = [
        'categoria_id',
        'descripcion',
        'usuario_id',
        'estado'
    ];
    /*
      // Si tienes casts, actualízalos
    protected $casts = [
        'es_activo' => 'boolean',
        'fecha_creacion' => 'datetime',
        // agregar casts para nuevos campos si es necesario
    ];
    */
    public function category()
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }
    public function ususario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    public function medicines()
{
    return $this->hasMany(Medicine::class, 'categoriamed_id')->where('categoria_id', 3);
}


}

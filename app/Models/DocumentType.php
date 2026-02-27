<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;
    protected $fillable = [
        'categoria_id',
        'descripcion',
        'cod_servicio',
        'usr',
        'estado_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'usr');
    }
    public function medicines()
{
    return $this->hasMany(Medicine::class, 'categoriamed_id')->where('categoria_id', 3);
}


}

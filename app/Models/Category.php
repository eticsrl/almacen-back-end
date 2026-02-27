<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'descripcion' ,

    ];
    public function documentTypes()
    {
        return $this->hasMany(Document_Type::class, 'categoria_id');
    }
}

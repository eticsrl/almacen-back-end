<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;
    protected $table='service_personals';
    protected $primaryKey='id';
    public $timestamp=false;

    protected $fillable = [
    'apellidos_nombres',
    'estado',
    'id_service',];

    
    public function service()
    {
        return $this->belongsTo(DocumentType::class, 'id_service', 'id')
                    ->where('categoria_id', 8);
    }

}

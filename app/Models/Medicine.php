<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;
    protected $table='medicines';
    protected $primaryKey='id';
    public $timestamp=false;

    protected $fillable = [
    'codigo',
    'nombre_generico',
    'observaciones',
    'formafarmaceutica_id',
    'categoriamed_id',
    'stockmax',
    'stockmin',
    'darmax',
    'darmin',
    'usuario_id',
    'estado',];

    public function pharmaceuticalForm()
    {
        return $this->belongsTo(PharmaceuticalForm::class, 'formafarmaceutica_id','id');
    }
    public function categoria()
    {
        return $this->belongsTo(DocumentType::class, 'categoriamed_id', 'id')
                    ->where('categoria_id', 3);
    }

}

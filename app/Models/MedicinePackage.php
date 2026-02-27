<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicinePackage extends Model
{
    use HasFactory;

    protected $table = 'medicine_packages';

    protected $fillable = [
        'documento_id',
        'medicamento_id',
        'cantidad',
        'observaciones',
        'dias',
        'usr',
        'estado_id',
    ];

    public function documento()
    {
        return $this->belongsTo(DocumentType::class, 'documento_id','id')
                    ->where('categoria_id', 7);
    }

    public function medicamento()
    {
        return $this->belongsTo(Medicine::class, 'medicamento_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'usr');
    }

    public function estado()
    {
        return $this->belongsTo(DocumentType::class, 'estado_id','id')
                    ->where('categoria_id', 5);
    }
}

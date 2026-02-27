<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    use HasFactory;
    protected $table='entries';
    protected $fillable=[
        'entity_id',
        'tipo_documento_id',
        'numero',
        'fecha_ingreso',
        'proveedor_id',
        'num_factura',
        'observaciones',
        'usr',
        'estado_id',
        'usr_mod',
        'fhr_mod',


    ];
    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'fhr_mod' => 'datetime',
    ];
    public function entryDetails()
    {
        return $this->hasMany(EntryDetail::class, 'ingreso_id');
    }
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class, 'tipo_documento_id','id')
        ->where('categoria_id', 1);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'proveedor_id');
    }
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'entity_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'usr');
    }
    public function estate()
    {
        return $this->belongsTo(DocumentType::class, 'estado_id','id')
        ->where('categoria_id', 5);
    }



}

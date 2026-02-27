<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DischargeDetail extends Model
{
    use HasFactory;
    protected $fillable=[
    'egreso_id',
    'ingreso_detalle_id',
    'receta_item_id',
    'cantidad_solicitada',
    'costo_unitario',
    'costo_total',
    'observaciones',
    'usr',
    'estado_id'
    ];
    //, 'usr_mod','fhr_mod'

    public function discharge()
    {
        return $this->belongsTo(Discharge::class, 'egreso_id');
    }
    public function entryDetail()
    {
        return $this->belongsTo(EntryDetail::class, 'ingreso_detalle_id');
    }
    public function estate()
    {
        return $this->belongsTo(DocumentType::class, 'estado_id','id')
        ->where('categoria_id', 5);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'usr');
    }
     //  reingresos originados por este egreso
    public function returns()
    { return $this->hasMany(EntryDetail::class, 'origen_discharge_detail_id'); }

}

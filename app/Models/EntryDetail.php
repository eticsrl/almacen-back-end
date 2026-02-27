<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntryDetail extends Model
{
    use HasFactory;
    protected $table='entry_details';
    protected $fillable=[
        'ingreso_id',
        'medicamento_id',
        'lote',
        'fecha_vencimiento',
        'cantidad',
        'costo_unitario',
        'costo_total',
        'stock_actual',
        'observaciones',
        'estado_id',
        'usr',
        'item_id',
        'receta_item_id',
        'origen_discharge_detail_id',// para reingresos por paciente
    ];
    protected $casts = [
        'costo_unitario' => 'float',
        'costo_total' => 'float',
        'cantidad' => 'integer',
    ];
    public function entry()
    {
        return $this->belongsTo(Entry::class,'ingreso_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicamento_id');
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
    public function dischargeDetail()
    {
        return $this->hasMany(DischargeDetail::class, 'ingreso_detalle_id');

    }
    public function parent() //para acceder al ingreso original (si es un reingreso).
    {
        return $this->belongsTo(EntryDetail::class, 'item_id');
    }

    public function reentries() //para ver qué reingresos fueron hechos a partir de un ingreso.
    {
        return $this->hasMany(EntryDetail::class, 'item_id');
    }
    public function origenEgresoDetalle()
    {
        return $this->belongsTo(DischargeDetail::class, 'origen_discharge_detail_id');
    }
}




<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariablesConfiguracion extends Model
{
    use HasFactory;
    protected $table='variablesConfiguracion';
    protected $primaryKey='id';

    protected $fillable = [
    'nombre',
    'observaciones',
    'estado',
    'tipo',
    'valor'
    ];
}
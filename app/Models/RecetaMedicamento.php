<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecetaMedicamento extends Model
{
    protected $connection = 'mysql_sissu';
    protected $table = 'receta_medicamentos';
}

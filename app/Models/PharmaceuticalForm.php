<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmaceuticalForm extends Model
{
    use HasFactory;
    protected $table='pharmaceutical_forms';
    protected $primaryKey='id';
   // public $timestamp=false;

    protected $fillable = [
        'formafarmaceutica' ,

    ];
    public function medicines()
    {
        return $this->hasMany(Medicine::class, 'formafarmaceutica_id');
    }
}

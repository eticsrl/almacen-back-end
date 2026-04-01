<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DischageMedicinesReportController extends Controller
{
    public function index(Request $req)
    {
        return response()->json([
            'message' => 'Reporte deshabilitado: la conexion secundaria fue retirada del sistema.'
        ], 410);
    }
}

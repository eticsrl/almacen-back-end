<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\EntryRequest;
use App\Http\Resources\V1\EntryResource;
use App\Models\Entry;
use App\Models\EntryDetail;
use App\Models\DischargeDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log; // <-- ¡Añadir esta línea!
class EntryController extends Controller
{

    public function index(Request $request)
    {
        $query = Entry::with([
            'entryDetails',
            'entity',
            'documentType',
            'supplier',
            'estate',

        ])->orderByDesc('id');

        // Filtrar SOLO por la entidad del usuario autenticado
        $query->where('entity_id', auth()->user()->entity_id);


        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            // Si vienen las dos fechas desde el request
            $fecha_inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
            $fecha_fin    = Carbon::parse($request->fecha_fin)->endOfDay();
        } else {
            // Si no vienen, usar fecha actual
            $fecha_inicio = Carbon::today()->startOfDay();
            $fecha_fin    = Carbon::today()->endOfDay();
        }

        $query->whereBetween('fecha_ingreso', [$fecha_inicio, $fecha_fin]);


        if ($request->filled('tipo_documento_id')) {
            $query->where('tipo_documento_id', $request->tipo_documento_id);
        }
        /*if ($request->filled('entity_id')) {
        $query->where('entity_id', $request->entity_id);
    }*/
        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }


        $entries = $query->get();

        return EntryResource::collection($entries);
    }

    public function store(EntryRequest $request)
    {
        //dd($request->all());
        DB::beginTransaction();
        try {
            // Validar los datos explícitamente (aunque ya fueron validados, esto permite trabajar con $validated)
            $validated = $request->validated();

            // Obtener el número consecutivo
            $numero = $this->generateNumero($validated['entity_id'], $validated['tipo_documento_id']);

            // Crear el Entry
            $entry = Entry::create([
                'entity_id' => $validated['entity_id'],
                'tipo_documento_id' => $validated['tipo_documento_id'],
                'numero' => $numero,
                'fecha_ingreso' => $validated['fecha_ingreso'],
                'proveedor_id' => $validated['proveedor_id'],
                'num_factura' => $validated['num_factura'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'usr' => auth()->id(),
                'estado_id' => 27, // PENDIENTE
            ]);

            // Crear detalles
            foreach ($validated['entry_details'] as $detail) {
                $entry->entryDetails()->create(array_merge($detail, [
                    'stock_actual' => $detail['cantidad'],
                    'estado_id' => 27,

                    'usr' => auth()->id(), // añade el usuario también a cada detalle
                    'item_id' => $detail['item_id'] ?? null,
                ]));
            }

            DB::commit();
            return new EntryResource($entry->fresh('entryDetails'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al guardar', 'error' => $e->getMessage()], 500);
        }
    }
    public function update(EntryRequest $request, $id)
    {
        /*
        //dd(['id_recibido' => $id, 'metodo' => $request->method()]);
        // 1. Registrar el valor del ID antes de la lógica principal
        Log::info("DEBUG: Iniciando actualización de entrada.", ['ID' => $id]); 

        // 2. Registrar el cuerpo completo de la solicitud (Payload)
        Log::info("DEBUG: Payload PUT recibido.", $request->all());
        
        // 3. Registrar el estado del modelo después de cargarlo
        Log::info("DEBUG: Modelo Entry cargado.", [
            'entrada_id' => $entry->id, 
            'estado_actual' => $entry->estado_id
        ]);*/
        $entry = Entry::with('entryDetails')->findOrFail($id);
        //Log::info("entry", ['datos'=> $entry]);

        // Solo se puede modificar si todos tienen estado pendiente
        if ($entry->estado_id != 27 && $entry->estado_id != 29 || $entry->entryDetails->where('estado_id', 28)->count()) {
            return response()->json(['message' => 'Solo puede modificarse si está en estado pendiente.'], 403);
        }

        return DB::transaction(function () use ($entry, $request, $id) {
            $data = $request->validated();
            //$data =$request->all();
            Log::info("data: ", [
                'datos' => $data
            ]);

            // Actualizar datos del ingreso
            $entry->update([
                ...$data,
                'usr_mod' => auth()->id(),
                'fhr_mod' => now(),
            ]);

            // ANTES de la línea problemática
            //logger('Datos recibidos en request:', request()->all());
            //logger('Datos recibidos despues del validadte data:', $data);
            //logger('Estructura de entry_details:', $data['entry_details'] ?? []);

            // Verifica la estructura específica
            /*if (isset($data['entry_details'])) {
                foreach ($data['entry_details'] as $index => $detail) {
                    logger("Detalle $index - ID: " . ($detail['ingreso_id'] ?? 'NO EXISTE'));
                }
            }*/

            // 1. Obtener IDs enviados
            $idsEnviados = collect($data['entry_details'])
                ->pluck('medicamento_id')
                ->filter(fn($id) => !empty($id))
                ->toArray();
            logger('IDs recopilados:', $idsEnviados);

            // 2. Obtener IDs actuales y eliminar los que no vinieron
            $idsActuales = \App\Models\EntryDetail::where('ingreso_id', $id)
                ->pluck('medicamento_id')
                ->toArray();

            logger('IDs actuales:', $idsActuales);

            $idsAEliminar = array_diff($idsActuales, $idsEnviados);
            logger('IDs eliminar:', $idsAEliminar);


            //Eliminando los que no vinieron
            if (!empty($idsAEliminar)) {
                \App\Models\EntryDetail::whereIn('medicamento_id', $idsAEliminar)
                    ->where('ingreso_id', $id)
                    ->update([
                        'estado_id' => 29,
                        'usr' => auth()->id()
                    ]);
            }
            //actualizando detalle ids que enviaron
            foreach ($entry->entryDetails as $existingDetail) {
                if (in_array($existingDetail->medicamento_id, $idsEnviados)) {
                    $detailData = collect($data['entry_details'])->firstWhere('medicamento_id', $existingDetail->medicamento_id);
                    $existingDetail->update([
                        ...$detailData,
                        'stock_actual' => $detailData['cantidad'],
                        'usr' => auth()->id(),
                        'estado_id' => 27,
                        'updated_at' => now(),
                    ]);
                }
            }




            logger('medicamento DATA DETAILS ', $data['entry_details']);
            // 3. Procesar detalles
            foreach ($data['entry_details'] as $detalle) {
                $detalleData = [
                    'ingreso_id' => $id,
                    'medicamento_id' => $detalle['medicamento_id'],
                    'lote' => $detalle['lote'],
                    'fecha_vencimiento' => $detalle['fecha_vencimiento'],
                    'cantidad' => $detalle['cantidad'],
                    'costo_unitario' => $detalle['costo_unitario'],
                    'costo_total' => $detalle['costo_total'],
                    'stock_actual' => $detalle['cantidad'],
                    'observaciones' => $detalle['observaciones'] ?? null,
                    'estado_id' => $detalle['estado_id'] ?? 27,
                    'usr' => auth()->id(),
                ];

                if (!in_array($detalle['medicamento_id'], $idsActuales)) {
                    // Create
                    \App\Models\EntryDetail::create($detalleData);
                    logger('medicamento registrado:', $detalleData);
                }
            }
            // LINEA ABAJO PARA DETENER LA EJECUCION Y REVISAR LOGS
            //return response()->json(['message' => 'idsenviados algo'], 403);

            return response()->json([
                'message' => 'Registro actualizado con éxito.',
                'entry' => new EntryResource(
                    $entry->refresh()->load([
                        'entryDetails.medicine',
                        'entryDetails.estate',
                        'entryDetails.user',
                        'entity',
                        'documentType',
                        'supplier',
                        'estate'
                    ])
                )
            ]);
        });
    }


    public function show($id)
    {
        $entry = Entry::with('entryDetails', 'entity', 'documentType', 'supplier', 'estate')->findOrFail($id);
        return new EntryResource($entry);
    }



    public function destroy($id)
    {
        $entry = Entry::with('entryDetails')->findOrFail($id);

        // No eliminar si está activo
        if ($entry->estado_id != 27) {
            return response()->json(['message' => 'Solo puede anularse si está en estado pendiente.'], 403);
        }

        // Validar que ningún detalle esté en discharge
        foreach ($entry->entryDetails as $detail) {
            if (DischargeDetail::where('ingreso_detalle_id', $detail->id)->exists()) {
                return response()->json(['message' => 'Uno o más detalles ya están usados en egresos y no pueden anularse.'], 403);
            }
        }

        return DB::transaction(function () use ($entry) {
            $entry->update([
                'estado_id' => 29, // ANULADO
                'usr_mod' => auth()->id(),
                'fhr_mod' => now(),
            ]);

            foreach ($entry->entryDetails as $detail) {
                $detail->update([
                    'estado_id' => 29, // ANULADO
                ]);
            }

            return response()->json(['message' => 'Ingreso y detalles anulados correctamente.']);
        });
    }


    private function generateNumero($entity_id, $tipo_documento_id)
    {
        $maxNumero = Entry::where('entity_id', $entity_id)
            ->where('tipo_documento_id', $tipo_documento_id)
            ->max('numero');

        return $maxNumero ? $maxNumero + 1 : 1;
    }
    public function entryDetailsConStock()
    {
        $details = EntryDetail::query()
            ->from('entry_details as ed')
            ->with([
                'entry.documentType',
                'medicine.pharmaceuticalForm',
                'estate',
                'user',
                'parent.entry',
            ])
            /* ───────────── JOINs ───────────── */
            ->leftJoin('entry_details as p', 'ed.item_id', '=', 'p.id')
            ->leftJoin('entries        as pe', 'p.ingreso_id', '=', 'pe.id')
            ->join('entries        as e',  'ed.ingreso_id', '=', 'e.id')
            /* ──────────── FILTROS ─────────── */
            ->where('ed.stock_actual', '>', 0)
            ->wherein('ed.estado_id', [27,28]) // PENDIENTE o ACTIVO
            ->where('e.entity_id', auth()->user()->entity_id)
            ->where('e.estado_id', 28)
            ->orderByRaw('COALESCE(pe.id, e.id) ASC')
            ->orderByRaw('COALESCE(p.id, ed.id) ASC')
            ->orderBy('ed.id')
            ->select('ed.*')
            ->get();

        return response()->json($details->map(function ($d) {
            return [
                'id'                   => $d->id,
                'medicamento_id'       => $d->medicamento_id,
                'liname'               => $d->medicine->liname ?? null,
                'medicamento'          => $d->medicine->nombre_generico ?? null,
                'formafarmaceutica_id' => $d->medicine->formafarmaceutica_id ?? null,
                'formafarmaceutica'    => $d->medicine->pharmaceuticalForm->formafarmaceutica ?? null,
                'lote'                 => $d->lote,
                'fecha_vencimiento'    => $d->fecha_vencimiento,
                'cantidad'             => $d->cantidad,
                'costo_unitario'       => $d->costo_unitario,
                'costo_total'          => $d->costo_total,
                'stock_actual'         => $d->stock_actual,
                'ingreso_id'           => $d->entry->id ?? null,
                'tipo_ingreso'         => $d->entry->documentType->descripcion ?? null,
                'fecha_ingreso'        => $d->entry->fecha_ingreso ?? null,
                'observaciones'        => $d->observaciones,
                'estado_id'            => $d->estado_id,
                'estado'               => $d->estate->descripcion ?? null,
                'usr'                  => $d->usr,
                'usuario'              => $d->user->name ?? null,
            ];
        }));
    }
    public function activate($entry_id)
    {   // Verifica que el ingreso esté en estado pendiente
        //dd($entry);
        $entry = Entry::with('entryDetails')->findOrFail($entry_id);
        if ($entry->estado_id !== 27) {
            return response()->json([
                'message' => 'Solo se puede activar si el ingreso está en estado pendiente.'
            ], 403);
        }

        // Verifica que al menos uno de los detalles esté pendiente
        $pendingDetails = $entry->entryDetails->filter(fn($d) => $d->estado_id === 27);

        if ($pendingDetails->isEmpty()) {
            return response()->json([
                'message' => 'No hay detalles pendientes para activar.'
            ], 403);
        }

        // Activar el ingreso
        $entry->estado_id = 28;
        $entry->usr_mod = auth()->id();
        $entry->fhr_mod = now();
        $entry->save();

        // Activar solo los detalles pendientes
        foreach ($pendingDetails as $detail) {
            $detail->estado_id = 28; // ACTIVO
            $detail->usr = auth()->id();
            $detail->save();
        }

        return new EntryResource($entry->fresh('entryDetails'));
    }
    /*
    public function activate(Entry $entry)
{   // Verifica que el ingreso esté en estado pendiente
    //dd($entry);
    if ($entry->estado_id !== 27) {
        return response()->json([
            'message' => 'Solo se puede activar si el ingreso está en estado pendiente.'
        ], 403);
    }

    // Verifica que al menos uno de los detalles esté pendiente
    $pendingDetails = $entry->entryDetails->filter(fn($d) => $d->estado_id === 27);

    if ($pendingDetails->isEmpty()) {
        return response()->json([
            'message' => 'No hay detalles pendientes para activar.'
        ], 403);
    }

    // Activar el ingreso
    $entry->estado_id = 28;
    $entry->usr_mod = auth()->id();
    $entry->fhr_mod = now();
    $entry->save();

    // Activar solo los detalles pendientes
    foreach ($pendingDetails as $detail) {
        $detail->estado_id = 28; // ACTIVO
        $detail->usr = auth()->id();
        $detail->save();
    }

    return new EntryResource($entry->fresh('entryDetails'));

}
*/
    public function entryDetailsForReentry()
    {
        $entryDetails = EntryDetail::with([
            'entry.documentType',
            'entry',
            'medicine.pharmaceuticalForm',
            'estate',
            'user'
        ])
->whereNull('item_id')

    // Filtro por la relación 'entry' (ENCABEZADO):
    ->whereHas('entry', function ($q) {
        $q->where('entity_id', auth()->user()->entity_id)
          ->where('estado_id', 28); // Sigue filtrando por Ingresos ACTIVOS
    })
    
    // Filtro por el EntryDetail (DETALLE):
    ->whereIn('entry_details.estado_id', [27, 28]) // ✅ Se corrigió a whereIn (camelCase)
    ->get();
    //dd($entryDetails); // 👈 ¡Inserta esto!
    logger('DATA DETAILS ', $entryDetails->toArray());
    //return response()->json(['message' => 'idsenviados algo'], 403);
        return response()->json($entryDetails->map(function ($detail) {
            return [
                'id' => $detail->id,
                'medicamento_id' => $detail->medicamento_id,
                'liname' => $detail->medicine->liname ?? null,
                'medicamento' => $detail->medicine->nombre_generico ?? null,
                'formafarmaceutica' => $detail->medicine->pharmaceuticalForm->formafarmaceutica ?? null,
                'lote' => $detail->lote,
                'fecha_vencimiento' => $detail->fecha_vencimiento,
                'costo_unitario' => $detail->costo_unitario,
                'costo_total' => $detail->costo_total,
                'stock_actual' => $detail->stock_actual,
                'cantidad' => $detail->cantidad,
                'entry_id' => $detail->entry->id,
                'numero_ingreso' => $detail->entry->numero,
                'tipo_ingreso' =>  $detail->entry->documentType->descripcion ?? null,
                'fecha_ingreso' => $detail->entry->fecha_ingreso,
                'observaciones' => $detail->observaciones,
                'entity_id' => $detail->entry->entity_id,
                'entidad' => $detail->entry->entity->descripcion ?? null,
                'estado_id' => $detail->estado_id,
                'estado' => $detail->estate->descripcion ?? null,
            ];
        }));
    }


    public function lotsByMedicine(Request $r)
    {
        $r->validate([
            'medicine_id' => 'required|integer',
        ]);

        $entityId = auth()->user()->entity_id;
        $refDate  = now()->toDateString(); // fecha actual

        // 1) Agregado por familia (padre + hijos), excluyendo vencidos, ORDEN PEPS
        $families = \App\Models\EntryDetail::query()
            ->from('entry_details as ed')
            ->join('entries as e',  'ed.ingreso_id', '=', 'e.id')
            ->leftJoin('entry_details as p', 'ed.item_id', '=', 'p.id')      // detalle padre
            ->leftJoin('entries as pe', 'p.ingreso_id', '=', 'pe.id')        // ingreso del padre
            ->with(['medicine.pharmaceuticalForm'])
            ->where('ed.medicamento_id', $r->medicine_id)
            ->where('ed.stock_actual', '>', 0)
            ->whereDate('ed.fecha_vencimiento', '>=', $refDate)
            ->where('e.entity_id', $entityId)
            ->where('e.estado_id', 28)
            ->groupByRaw('COALESCE(ed.item_id, ed.id), ed.medicamento_id, COALESCE(p.lote, ed.lote)')
            // PEPS: primero el ingreso más antiguo de la familia; vencimiento solo como desempate
            ->orderByRaw('MIN(COALESCE(pe.fecha_ingreso, e.fecha_ingreso)) ASC')
            ->orderByRaw('MIN(ed.fecha_vencimiento) ASC')
            ->selectRaw('
            COALESCE(ed.item_id, ed.id)                         AS family_id,
            ed.medicamento_id                                   AS medicamento_id,
            COALESCE(p.lote, ed.lote)                           AS lote,
            MIN(COALESCE(pe.fecha_ingreso, e.fecha_ingreso))    AS fecha_ingreso_familia,
            MIN(ed.fecha_vencimiento)                           AS fecha_vencimiento,
            SUM(ed.stock_actual)                                AS stock_total,
            MIN(COALESCE(p.costo_unitario, ed.costo_unitario))  AS costo_unitario
        ')
            ->get();

        // 2) Detalles reales (padre + hijos) de esas familias, excluyendo vencidos
        $familyIds = $families->pluck('family_id');

        $detailsByFamily = \App\Models\EntryDetail::with(['entry.documentType'])
            ->where(function ($q) use ($familyIds) {
                $q->whereIn('id', $familyIds)         // padre
                    ->orWhereIn('item_id', $familyIds); // hijos
            })
            ->where('stock_actual', '>', 0)
            ->whereDate('fecha_vencimiento', '>=', $refDate)
            // PEPS también en el detalle: primero el ingreso más antiguo
            ->orderBy('ingreso_id', 'asc')
            ->orderBy('fecha_vencimiento', 'asc') // desempate
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy(function ($d) {
                return $d->item_id ?: $d->id; // family_id
            });

        // 3) Resumen global del medicamento
        $medicine = \App\Models\Medicine::with('pharmaceuticalForm')->find($r->medicine_id);
        $stockTotalGlobal = (int) $families->sum('stock_total');

        $summary = [
            'medicine_id'        => (int) $r->medicine_id,
            'liname'             => optional($medicine)->liname
                ?? optional($families->first()?->medicine)->liname,
            'nombre'             => optional($medicine)->nombre_generico
                ?? optional($families->first()?->medicine)->nombre_generico,
            'presentacion'       => optional(optional($medicine)->pharmaceuticalForm)->formafarmaceutica
                ?? optional(optional($families->first()?->medicine)->pharmaceuticalForm)->formafarmaceutica,
            'stock_total_global' => $stockTotalGlobal,
        ];

        // 4) Familias + detalles
        $familias = $families->map(function ($f) use ($detailsByFamily) {
            $det = ($detailsByFamily[$f->family_id] ?? collect())->map(function ($d) {
                return [
                    'id'                => $d->id,               // usar este id al descargar
                    'lote'              => $d->lote,
                    'fecha_vencimiento' => $d->fecha_vencimiento,
                    'stock_actual'      => (int) $d->stock_actual,
                    'costo_unitario'    => (float) $d->costo_unitario,
                    'costo_total'       => (float) $d->costo_total,
                    'ingreso_id'        => $d->ingreso_id,
                    'tipo_ingreso'      => optional($d->entry->documentType)->descripcion,
                    'observaciones'     => $d->observaciones,
                ];
            })->values();

            return [
                'family_id'           => (int) $f->family_id,
                'medicamento_id'      => (int) $f->medicamento_id,
                'lote'                => $f->lote,
                'fecha_ingreso'       => $f->fecha_ingreso_familia, // útil para auditar PEPS
                'fecha_vencimiento'   => $f->fecha_vencimiento,
                'stock_total'         => (int) $f->stock_total,
                'costo_unitario'      => (float) $f->costo_unitario,
                'detalles'            => $det,
            ];
        })->values();

        return response()->json([
            'summary'  => $summary,
            'familias' => $familias,
        ]);
    }

    public function storeReturn(Request $request)
    {
        $validated = $request->validate([
            'fecha_ingreso' => ['required', 'date'],
            'observaciones' => ['nullable', 'string'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.discharge_detail_id' => ['required', 'integer', 'exists:discharge_details,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:1'],
            'detalles.*.receta_item_id' => ['nullable', 'integer'],
        ]);

        $user     = auth()->user();
        $entityId = (int) $user->entity_id;

        $entry = DB::transaction(function () use ($validated, $user, $entityId) {
            // Genera número para tipo 6 (Reingreso por devolución)
            $numero = $this->generateNumero($entityId, 6);

            // Crear el Ingreso en ACTIVO (28) para que sume stock de inmediato
            $entry = Entry::create([
                'entity_id'          => $entityId,
                'tipo_documento_id'  => 6,
                'numero'             => $numero,
                'fecha_ingreso'      => $validated['fecha_ingreso'],
                'proveedor_id'       => null,
                'num_factura'        => null,
                'observaciones'      => $validated['observaciones'] ?? 'Reingreso por devolución',
                'usr'                => $user->id,
                'estado_id'          => 28, // ACTIVO
            ]);

            foreach ($validated['detalles'] as $row) {
                /** @var \App\Models\DischargeDetail $dd */
                $dd = DischargeDetail::with(['entryDetail', 'discharge'])
                    ->where('id', $row['discharge_detail_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                // Defensa: el egreso debe pertenecer a la misma entidad
                if ((int) $dd->discharge->entity_id !== $entityId) {
                    throw new \Exception('El egreso no pertenece a su entidad.');
                }

                // Validar que no se devuelva más de lo entregado (menos lo ya devuelto)
                $entregado = (int) $dd->cantidad_entregada;
                $devueltoAcumulado = (int) EntryDetail::where('origen_discharge_detail_id', $dd->id)->sum('cantidad');
                $saldo = $entregado - $devueltoAcumulado;

                $aDevolver = (int) $row['cantidad'];
                if ($aDevolver > $saldo) {
                    throw new \Exception("La devolución supera el saldo disponible del detalle de egreso #{$dd->id}. Disponible: {$saldo}.");
                }

                // Volver al lote "padre" (familia)
                $original = $dd->entryDetail; // detalle usado en el egreso
                $parentId = $original->item_id ?: $original->id;
                $parent   = $original->item_id ? EntryDetail::findOrFail($parentId) : $original;

                $cu = (float) $parent->costo_unitario;

                // Crear detalle del reingreso como hijo del padre
                EntryDetail::create([
                    'ingreso_id'                 => $entry->id,
                    'medicamento_id'             => $parent->medicamento_id,
                    'lote'                       => $parent->lote,
                    'fecha_vencimiento'          => $parent->fecha_vencimiento,
                    'cantidad'                   => $aDevolver,
                    'costo_unitario'             => $cu,
                    'costo_total'                => round($aDevolver * $cu, 4),
                    'stock_actual'               => $aDevolver, // entra disponible
                    'observaciones'              => 'Devolución del egreso ' . $dd->egreso_id . ' (detalle ' . $dd->id . ')',
                    'estado_id'                  => 28, // ACTIVO
                    'usr'                        => $user->id,
                    'item_id'                    => $parentId, // vuelve al padre
                    'receta_item_id'             => $row['receta_item_id'] ?? ($dd->receta_item_id ?? null),
                    'origen_discharge_detail_id' => $dd->id,
                ]);
            }

            // Devolver con relaciones para el front
            return $entry->load([
                'entryDetails.medicine.pharmaceuticalForm',
                'entryDetails.estate',
                'entity',
                'documentType',
                'estate'
            ]);
        });

        return new EntryResource($entry);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PopupResource;
use App\Models\Popup;
use Illuminate\Http\JsonResponse;

class PopupController extends Controller
{
    /**
     * Pop-up del sitio. Devuelve `data: null` cuando no hay ninguno que mostrar
     * (no existe, está oculto o no tiene contenido), para que la web no muestre nada.
     */
    public function show(): JsonResponse
    {
        $popup = Popup::query()->where('is_visible', true)->with('items.mediaFile')->first();

        $data = $popup && $popup->items->isNotEmpty()
            ? (new PopupResource($popup))->resolve()
            : null;

        return response()
            ->json(['data' => $data])
            ->header('Cache-Control', 'public, max-age=60');
    }
}

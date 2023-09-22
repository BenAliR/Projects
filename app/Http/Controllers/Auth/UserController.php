<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DemandeInscription;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponser;
    /**
     * Get authenticated user.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function current(Request $request)
    {
        return response($request->user(), 200);

    }
    public function accountdetails(Request $request)
    {

        return $this->successResponse($request->user(), "Enregistrement effectué avec succès!",201);
    }
}

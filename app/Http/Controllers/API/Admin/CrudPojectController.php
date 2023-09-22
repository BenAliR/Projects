<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Team;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrudPojectController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {


        // Get the projects associated with the owned and member teams
        $projects = Project::with(['team.owner', 'team.users'])
            ->get();

        return $this->successResponse($projects,"Enregistrement effectué avec succès!",201);

    }
}

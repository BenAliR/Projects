<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\Controller;
use App\Services\ActivityService;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    use ApiResponser;

    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }


    public function index()
    {
// Retrieve the user instance you want to get activity logs for
        $user = Auth::user();
// Retrieve activity logs associated with the user
        $activityLogs = Activity::causedBy($user)
            ->orderBy('created_at', 'desc') ->get();

        return $this->successResponse($activityLogs, "Enregistrement effectué avec succès!", 201);
    }

    public function ToDayActivity()
    {
// Retrieve the user instance you want to get activity logs for
        $user = Auth::user();
        $today = date('Y-m-d'); // Today's date in 'YYYY-MM-DD' format
// Retrieve activity logs associated with the user
        $activityLogs = Activity::causedBy($user)->whereDate('created_at', '>=', $today)
            ->orderBy('created_at', 'desc') ->get();

        return $this->successResponse($activityLogs, "Enregistrement effectué avec succès!", 201);
    }
}

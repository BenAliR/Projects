<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Pusher\Pusher;
class NotificationController extends Controller
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
        // Get the authenticated user
        $user = Auth::user();

        // Get notifications associated with the user
        $notifications = Notification::where('user_id', $user->id)
            ->get();

        return $this->successResponse($notifications,"Enregistrement effectué avec succès!",201);

    }
}


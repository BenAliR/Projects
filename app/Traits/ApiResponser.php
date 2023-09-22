<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponser{

    protected function successResponse($data, $message = null, $code = 200)
    {
        return response()->json([
            'status'=> true,
            'message' => $message,
            'data' => $data,
            'errors' =>[]
        ], $code);
    }

    protected function errorResponse($code,$data = null,$message = null )
    {
        return response()->json([
            'status'=>false,
            'message' => $message,
            'data' => $data,
            'errors' =>$data
        ], $code);
    }

}

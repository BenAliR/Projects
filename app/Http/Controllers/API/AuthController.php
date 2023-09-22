<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);
        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), 'quelque chose s\'est mal passé' );
        }
        $user = User::where('email', $request->email)->first();

        if ($user) {
            if ($user->banned !== "1"){
                if (Hash::check($request->password, $user->password)) {
                    $user->update([
                        'last_login_at' => Carbon::now()->toDateTimeString(),
                        'last_login_ip' =>  $request->getClientIp()
                    ]);
                    $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                    $user->api_token = $token;
                    $response = ['status'=>"success",'user'=> $user ,'token' => $token];
                    return response($user, 200);

                }
                else {

                    return $this->errorResponse( 422,["votre mot de passe est incorrect" ], "votre mot de passe est incorrect" );
                }
            }

            $message = '';
            if ($user->banned === "1") {

                if (now()->lessThan($user->banned_at)) {
                    $banned_days = now()->diffInDays($user->banned_at) + 1;
                    $message = 'Votre compte a été suspendu pour ' . $banned_days . ' ' . Str::plural('jour', $banned_days);
                }else{

                    $message = 'Votre compte a été définitivement banni.';
                }
                return $this->errorResponse( 422,[$message], $message );

            }

//            $user->sendEmailVerificationNotification();

        } else {

            return $this->errorResponse( 422,["L'utilisateur n'existe pas"], "L'utilisateur n'existe pas" );

        }
    }
    /**
 * This method returns authenticated user details
 */
    public function authenticatedUserDetails(){
        //returns details
        if (!auth()->user()) {
            return $this->errorResponse( 422,null, "Invalid Credentials" );
        }
        return response()->json(['authenticated-user' => auth()->user()], 200);
    }
    /**
     * This method returns authenticated user details
     */
    public function logout(){
        //returns details
        if (!auth()->user()) {
            return $this->errorResponse( 422,null, "Invalid Credentials" );
        }
        $user = auth()->user()->token();

        $user->revoke();
        return $this->successResponse(null,"Enregistrement effectué avec succès!",200);

    }
    public function verifyToken(Request $request)
    {

        return response()->json(['authenticated-user' => auth()->user()], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

use Illuminate\Validation\ValidationException;
class VerifyEmailController extends Controller
{
    use VerifiesEmails;

/* Mark the authenticated user’s email address as verified.

*

* @param \Illuminate\Http\Request $request

* @return \Illuminate\Http\Response

*/

    public function verify(Request $request) {
//        if (!$request->hasValidSignature(false)) {
//            //abort(401, 'This link is not valid.');
//            $verified = false;
//            return response()->json($request->only('expires', 'signature'));   // When we redirect, we will have the message in our session
//        }
        if ($request->hasValidSignature()) {
            $userID = $request['user'];

            $user = User::where('email', '=', $userID)->first();

           // $date = date("Y-m-d H:i:s");

            $user->email_verified_at = Carbon::now()->timestamp; // to enable the “email_verified_at field of that user be a current time stamp by mimicing the must verify email feature

            $user->save();

            return response()->json('Email verified!');
//            return response()->json($request->only('expires', 'signature'));   // When we redirect, we will have the message in our session

        }

        if (! URL::hasValidSignature($request)) {
           // Log::info('throwing InvalidSignatureException');
            return response()->json("Ce lien n'est pas valide.!");

        }


    }

    /**
     * Resend the email verification notification.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function resend(Request $request)

    {
    //    $this->validate($request, ['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (is_null($user)) {
            return response()->json(['status' => 'not found']);
        }

        if ($user->email_verified_at) {
            return response()->json(['status' => 'verified']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['status' => $user]);

                //    return response()->json(  $request->user());

//        if ($request->user()->hasVerifiedEmail()) {
//
//            return response()->json('User already have verified email!', 422);
//
//            // return redirect($this->redirectPath());
//
//        }
//
//        $request->user()->sendEmailVerificationNotification();
//
//        return response()->json('The notification has been resubmitted');

    }
}

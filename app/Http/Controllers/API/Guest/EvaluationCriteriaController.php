<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriteria;

use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class EvaluationCriteriaController extends Controller
{
    use ApiResponser;

    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $criteria = EvaluationCriteria::all();
            return $this->successResponse($criteria, "Enregistrement effectué avec succès!", 201);



    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $criteria = EvaluationCriteria::find($id);

        if (!$criteria) {

            return $this->errorResponse(404, ['Evaluation Criteria introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($criteria, "Enregistrement effectué avec succès!", 201);

    }

    /**
     * Display resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function store(Request $request)
    {
        // here we'll look up the user by the token sent provided in the URL
        // Look up the invite
        $data = $request->all();


        $rules = [
            'title' => 'required|string',
    'description'=> 'required|string',

        ];
        $niceNames = array(

            'title' => 'required',


        );
        $messages = array(

            'title.required' => 'Veuillez fournir titre!',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), "quelque chose s'est mal passé");
        }

        $criteria = EvaluationCriteria::create(
  [          'title' => $request->title,
            'description' => $request->description,
          ]
        );
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse([],'Enregistrement effectué avec succès!', 201);

    }


    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            $criteria = EvaluationCriteria::findOrFail($id);
            if (!$criteria) {
                return $this->errorResponse(404, ["Evaluation Criteria n'existe pas"], 'quelque chose s\'est mal passé');
            }



            $rules = [
                'title' => 'required|string',
                'description'=> 'required|string',

            ];
            $niceNames = array(

                'title' => 'required',


            );
            $messages = array(

                'title.required' => 'Veuillez fournir titre!',


            );
            $validator = Validator::make($data, $rules,$messages, $niceNames);;

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            $criteria->title = $request->input('title');


            $criteria->description = $request->input('description');
            $criteria->save();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);
        } catch (\Exception $e) {
            return $this->errorResponse(500,  ["Une erreur s'est produite lors de la mise à jour"], "Une erreur s'est produite lors de la mise à jour");
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id)
    {
        try {

            $criteria = EvaluationCriteria::find($id);

            if (!$criteria) {

                return $this->errorResponse(404, ['Evaluation Criteria introuvable'], 'quelque chose s\'est mal passé');
            }


            $criteria->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, ['Evaluation Criteria  introuvable'], 'quelque chose s\'est mal passé');
        }
    }



}

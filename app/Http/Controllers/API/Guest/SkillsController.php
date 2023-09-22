<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class SkillsController extends Controller
{
    use ApiResponser;

    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $skill = Skill::all();
            return $this->successResponse($skill, "Enregistrement effectué avec succès!", 201);



    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $skill = Skill::find($id);

        if (!$skill) {

            return $this->errorResponse(404, ['skill introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($skill, "Enregistrement effectué avec succès!", 201);

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
            'skill' => 'required|string',


        ];
        $niceNames = array(

            'skill' => 'required',


        );
        $messages = array(

            'skill.required' => 'Veuillez fournir skill!',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), "quelque chose s'est mal passé");
        }

        $skill = Skill::create(
  [          'name' => $request->skill,

          ]
        );
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse([],'Enregistrement effectué avec succès!', 201);

    }


    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            $skill = Skill::findOrFail($id);
            if (!$skill) {
                return $this->errorResponse(404, ["skill n'existe pas"], 'quelque chose s\'est mal passé');
            }



            $rules = [
                'skill' => 'required|string',


            ];
            $niceNames = array(

                'skill' => 'required',


            );
            $messages = array(

                'skill.required' => 'Veuillez fournir skill!',


            );
            $validator = Validator::make($data, $rules,$messages, $niceNames);;

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            $skill->name = $request->input('skill');


            $skill->save();
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

            $skill = Skill::find($id);

            if (!$skill) {

                return $this->errorResponse(404, ['skill introuvable'], 'quelque chose s\'est mal passé');
            }


            $skill->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, [' skill  introuvable'], 'quelque chose s\'est mal passé');
        }
    }



}

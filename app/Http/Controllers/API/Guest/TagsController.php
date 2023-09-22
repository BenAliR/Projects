<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;


use App\Models\Tag;
use App\Traits\ApiResponser;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class TagsController extends Controller
{
    use ApiResponser;

    /**
     * Display resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $tag = Tag::all();
            return $this->successResponse($tag, "Enregistrement effectué avec succès!", 201);



    }
    /**
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {

            return $this->errorResponse(404, ['tag introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($tag, "Enregistrement effectué avec succès!", 201);

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
            'tag' => 'required|string',


        ];
        $niceNames = array(

            'tag' => 'required',


        );
        $messages = array(

            'tag.required' => 'Veuillez fournir tag!',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), "quelque chose s'est mal passé");
        }

        $tag = Tag::create(
  [          'name' => $request->tag,

          ]
        );
        // here you would probably log the user in and show them the dashboard, but we'll just prove it worked
        return $this->successResponse([],'Enregistrement effectué avec succès!', 201);

    }


    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();

            $tag = Tag::findOrFail($id);
            if (!$tag) {
                return $this->errorResponse(404, ["tag n'existe pas"], 'quelque chose s\'est mal passé');
            }



            $rules = [
                'tag' => 'required|string',


            ];
            $niceNames = array(

                'tag' => 'required',


            );
            $messages = array(

                'tag.required' => 'Veuillez fournir tag!',


            );
            $validator = Validator::make($data, $rules,$messages, $niceNames);;

            // Check if validation fails
            if ($validator->fails()) {
                return $this->errorResponse(422, $validator->errors(), 'quelque chose s\'est mal passé');
            }
            $tag->name = $request->input('tag');


            $tag->save();
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

            $tag = Tag::find($id);

            if (!$tag) {

                return $this->errorResponse(404, ['tag introuvable'], 'quelque chose s\'est mal passé');
            }


            $tag->delete();
            return $this->successResponse([], "Enregistrement effectué avec succès!", 201);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(404, [' tag  introuvable'], 'quelque chose s\'est mal passé');
        }
    }



}

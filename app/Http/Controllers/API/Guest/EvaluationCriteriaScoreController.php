<?php

namespace App\Http\Controllers\API\Guest;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriteriaScore;
use App\Models\Feedback;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class EvaluationCriteriaScoreController extends Controller
{
    use ApiResponser;


    /**
     * Display a listing of the evaluation criteria scores.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $scores = EvaluationCriteriaScore::with(['evaluationCriteria', 'project'])->get();
        return $this->successResponse($scores, "Enregistrement effectué avec succès!", 201);
    }

    /**
     * Display the specified evaluation criteria score.
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        $score = EvaluationCriteriaScore::find($id);

        if (!$score) {

            return $this->errorResponse(404, ['Evaluation introuvable'], 'quelque chose s\'est mal passé');
        }
        return $this->successResponse($score, "Enregistrement effectué avec succès!", 201);

    }

    /**
     * Store a newly created evaluation criteria score in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->all();


        $rules = [
            'score' => 'required|numeric|min:0|max:10',
            'evaluation_criteria_id' => 'required|exists:evaluation_criteria,id',
            'project_id' => 'required|exists:projects,id',

        ];
        $niceNames = array(

            'score' => 'required',


        );
        $messages = array(

            'score.required' => 'Veuillez fournir une evaluation du projet!',


        );
        $validator = Validator::make($data, $rules,$messages, $niceNames);



        if($validator->fails()) {
            return $this->errorResponse( 422,$validator->errors(), "quelque chose s'est mal passé");
        }





        $score = EvaluationCriteriaScore::create(
            [          'score' => $request->score,
                'evaluation_criteria_id' => $request->evaluation_criteria_id,
                'project_id' => $request->project_id,]
        );

        return $this->successResponse($score, "Enregistrement effectué avec succès!", 201);
    }

    /**
     * Update the specified evaluation criteria score in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request,$id)
    {
        $score = EvaluationCriteriaScore::find($id);

        if (!$score) {

            return $this->errorResponse(404, ['Evaluation introuvable'], 'quelque chose s\'est mal passé');
        }
        $validatedData = $request->validate([
            'score' => 'required|numeric|min:0|max:10',
        ]);

        $score->update(['score' => $request->score,]);

        return $this->successResponse($score, "Enregistrement effectué avec succès!", 201);
    }

    /**
     * Remove the specified evaluation criteria score from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {

        $score = EvaluationCriteriaScore::find($id);

        if (!$score) {

            return $this->errorResponse(404, ['Evaluation introuvable'], 'quelque chose s\'est mal passé');
        }


        $score->delete();

        return $this->successResponse([], "Enregistrement effectué avec succès!", 201);
    }



}

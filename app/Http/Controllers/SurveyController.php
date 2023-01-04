<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        return SurveyResource::collection(
            Survey::where(
                'user_id',
                $user->id
            )->paginate()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreSurveyRequest $request
     *
     * @return SurveyResource
     */
    public function store(StoreSurveyRequest $request): SurveyResource
    {
        $data = $request->validated();

        // Check if image was given and save on local file system
        if (isset($data['image'])) {
            $relativePath = $this->saveImage($data['image']);
            $data['image'] = $relativePath;
        }

        $survey = Survey::create($data);

        return new SurveyResource($survey);
    }

    /**
     * Display the specified resource.
     *
     * @param Survey $survey
     * @param Request $request
     *
     * @return SurveyResource
     */
    public function show(Survey $survey, Request $request): SurveyResource
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unauthorized action.');
        }
        return new SurveyResource($survey);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateSurveyRequest $request
     * @param Survey $survey
     *
     * @throws Exception
     * @return SurveyResource
     */
    public function update(UpdateSurveyRequest $request, Survey $survey): SurveyResource
    {
        $data = $request->validated();

        // Check if image was given and save on local file system
        if (isset($data['image'])) {
            $relativePath = $this->saveImage($data['image']);
            $data['image'] = $relativePath;

            // If there is an old image, delete it
            if($survey->image) {
                $absolutePath = public_path($survey->image);
                File::delete($absolutePath);
            }
        }

        $survey->update($data);
        return new SurveyResource($survey);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Survey $survey
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function destroy(Survey $survey, Request $request): Response|Application|ResponseFactory
    {
        $user = $request->user();
        if ($user->id !== $survey->user_id) {
            return abort(403, 'Unauthorized action.');
        }

        $survey->delete();

        // If there is an image, delete it as well
        if($survey->image) {
            $absolutePath = public_path($survey->image);
            File::delete($absolutePath);
        }

        return response('', 204);
    }

    /**
     * @throws Exception
     */
    protected function saveImage(mixed $image): string
    {
        // Check if image is valid base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            // Take out the base64 encoded text without mime type
            $image = substr($image, strpos($image, ',') + 1);
            $type = strtolower($type[1]);

            // Check if file is an image
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new Exception('Invalid image type');
            }
            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);
            if (!$image) {
                throw new Exception('base64_decode failed');
            }
        } else {
            throw new Exception('Did not match data URI with image data');
        }

        $dir = 'images/';
        $file = Str::random() . '.' . $type;
        $absolutePath = public_path($dir);
        $relativePath = $dir.$file;
        if (!File::exists($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }
        file_put_contents($relativePath, $image);

        return $relativePath;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 12/15/17
 * Time: 3:34 PM
 */

namespace App\Repository\Services;


use App\Exceptions\GeneralException;
use App\Exceptions\LoraException;
use App\Project;
use App\Thing;
use App\Repository\Services\Core\PMCoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\ObjectId;

class ProjectService
{
    protected $pmService;
    protected $loraService;


    public function __construct(PMCoreService $pmService, LoraService $loraService)
    {
        $this->pmService = $pmService;
        $this->loraService = $loraService;
    }

    /**
     * @param Request $request
     * @return void
     * @throws GeneralException
     */
    public function validateCreateProject(Request $request)
    {
        $messages = [
            'name.required' => 'لطفا نام پروژه را وارد کنید',
            'name.unique' => 'این نام قبلا وجود دارد',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:projects',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    /**
     * @param Request $request
     * @return Project
     * @throws GeneralException
     * @throws LoraException
     */
    public function insertProject(Request $request)
    {
        $id = new ObjectId();
        $container = $this->pmService->create($id, Auth::user()['email']);
        $project = Project::create([
            '_id' => $id,
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'active' => true,
            'container' => $container,
        ]);
        return $project;
    }

    /**
     * @param Request $request
     * @return void
     * @throws GeneralException
     */
    public function validateUpdateProject(Request $request)
    {
        $messages = [
            'name.unique' => 'این پرژوه قبلا وجود دارد',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255|unique:projects',
            'description' => 'string',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);
    }

    /**
     * @param Request $request
     * @param Project $project
     * @return Project
     */
    public function updateProject(Request $request, Project $project)
    {
        if ($request->get('name'))
            $project->name = $request->get('name');
        if ($request->get('description'))
            $project->description = $request->get('description');
        $project->save();

        return $project;
    }


    /**
     * @param Project $project
     * @param array $aliases
     * @throws GeneralException
     */
    public function setAliases(Project $project, $aliases)
    {
        if (!$aliases || !$this->validateAlias($aliases))
            throw new GeneralException('لطفا اطلاعات را درست وارد کنید.', GeneralException::VALIDATION_ERROR);
        $project['aliases'] = $aliases;
        $project->save();
    }


    private function validateAlias($aliases)
    {
        foreach ($aliases as $a)
            if (gettype($a) !== 'string' && gettype($a) !== 'integer')
                return false;
        return true;
    }
}

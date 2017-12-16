<?php
/**
 * Created by PhpStorm.
 * User: sajjad
 * Date: 12/15/17
 * Time: 3:34 PM
 */

namespace App\Repository\Services;


use App\Exceptions\ProjectException;
use App\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProjectService
{

    /**
     * @param Request $request
     * @return void
     * @throws ProjectException
     */
    public function validateCreateProject(Request $request)
    {
        $messages = [
            'name.required' => 'لطفا نام پروژه را وارد کنید',
            'description.required' => 'لطفا توضیحات را درست وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
        ], $messages);

        if ($validator->fails())
            throw new  ProjectException($validator->errors()->first(), ProjectException::C_GE);
    }

    /**
     * @param Request $request
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    public function insertProject(Request $request)
    {
        return Project::create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
        ]);
    }

    /**
     * @param Request $request
     * @return void
     * @throws ProjectException
     */
    public function validateUpdateProject(Request $request)
    {
        $messages = [
            'name.filled' => 'لطفا نام پروژه را وارد کنید',
            'description.filled' => 'لطفا توضیحات را درست وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'name' => 'filled|string|max:255',
            'description' => 'filled|string',
        ], $messages);

        if ($validator->fails())
            throw new  ProjectException($validator->errors()->first(), ProjectException::C_GE);
    }

    /**
     * @param Request $request
     * @param Project $project
     * @return $this|\Illuminate\Database\Eloquent\Model
     */
    public function updateProject(Request $request, Project $project)
    {
        if($request->get('name'))
            $project->name = $request->get('name');
        if($request->get('description'))
            $project->description = $request->get('description');
        $project->save();

        return $project;
    }
}
<?php

namespace App\Http\Controllers\v1;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Repository\Helper\Response;
use App\Repository\Services\ConfigService;
use App\Repository\Services\Core\DMCoreService;
use App\Repository\Services\FileService;
use App\Repository\Services\UserService;
use App\Thing;
use Carbon\Carbon;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    protected $userService;
    protected $fileService;
    protected $configService;
    protected $dmService;

    /**
     * UserController constructor.
     * @param userService $userService
     * @param FileService $fileService
     * @param DMCoreService $dmService
     * @param ConfigService $configService
     */
    public function __construct(UserService $userService,
                                FileService $fileService,
                                DMCoreService $dmService,
                                ConfigService $configService)
    {
        $this->userService = $userService;
        $this->fileService = $fileService;
        $this->configService = $configService;
        $this->dmService = $dmService;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function update(Request $request)
    {
        $this->userService->validateUpdateUser($request);

        $user = $this->userService->updateUser($request);

        return Response::body(compact('user'));
    }


    /**
     * @param Request $request
     * @return array
     * @throws GeneralException
     */
    public function changePassword(Request $request)
    {
        $new_password = $request->get('new_password');
        if (strlen($new_password) < 6)
            throw new GeneralException('رمز عبور باید حداقل ۶ کاراکتر باشد.', GeneralException::VALIDATION_ERROR);
        $this->userService->changePassword($request);
        return Response::body(['success' => true]);
    }


    /**
     * @return array
     */
    public function profile()
    {
        $user = Auth::user();
        return Response::body(['user' => $user]);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function upload(Request $request)
    {
        $user = Auth::user();
        $file = $request->file('file');
        $path = $this->fileService->saveFile('legal', $file);

        $user->legal_doc = '/data/' . $path;

        $user->save();
        return Response::body(['path' => $user->legal_doc]);
    }

    public function picture(Request $request)
    {
        $messages = [
            'picture.required' => 'لطفا عکس را انتخاب کنید',
            'picture.mimes' => 'نوع فایل را درست وارد کنید',
        ];

        $validator = Validator::make($request->all(), [
            'picture' => 'required|file|mimes:jpeg,bmp,png',
        ], $messages);

        if ($validator->fails())
            throw new  GeneralException($validator->errors()->first(), GeneralException::VALIDATION_ERROR);

        $user = Auth::user();
        $file = $request->file('picture');
        $path = $this->fileService->savePicture($file);

        $user->picture = '/data/' . $path . '?rand=' . str_random(5);

        $user->save();
        return Response::body(['user' => $user]);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $config = $user->config()->first();
        $charts = collect($config['widgets'])->map(function ($widget) {
            try {
                $thing = Thing::where('dev_eui', $widget['devEUI'])->first()->setAppends([]);
                $since = Carbon::now()->subHours((int)$widget['window'])->getTimestamp();
                $until = Carbon::now()->getTimestamp();
                return [
                    'title' => $widget['title'],
                    'type' => $widget['type'],
                    'thing' => $thing,
                    'alias' => $widget['alias'],
                    'data' => collect($this->dmService->fetchThing($thing['dev_eui'], $since, $until))
                        ->filter(function ($data) use ($widget) {
                            $data = json_decode(json_encode($data), True);
                            return isset($data['data'][$widget['key']]);
                        })
                        ->map(function ($data) use ($widget) {
                            $data = json_decode(json_encode($data), True);
                            return ['timestamp' => $data['timestamp'], 'value' => $data['data'][$widget['key']]];
                        })->values()];
            } catch (Error $e) {
                return [];
            } catch (Exception $e) {
                return [];
            }
        });
        return Response::body([
            'charts' => $charts,
            'things_num' => $user->things()->count(),
            'project_num' => $user->projects()->count()
        ]);
    }

    public function setWidgetChart(Request $request)
    {
        $widgets = $this->configService->setWidgetChart(collect($request->all()));
        return Response::body(['widgets' => $widgets]);
    }

    public function deleteWidgetChart(Request $request)
    {
        $this->configService->deleteWidgetChart(collect($request->all()));
        return Response::body(['success' => true]);
    }

}

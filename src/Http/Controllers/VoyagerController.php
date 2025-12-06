<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Media\VoyagerImage as Image;

class VoyagerController extends Controller
{
    public function index()
    {
        return Voyager::view('voyager::index');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('voyager.login');
    }

    public function upload(Request $request)
    {
        $fullFilename = null;
        $resizeWidth = 1800;
        $resizeHeight = null;
        $slug = $request->input('type_slug');
        $file = $request->file('image');

        if (!$file) {
            $files = $request->file('files');
            if (is_array($files)) {
                $file = $files[0] ?? null;
            } else {
                $file = $files;
            }
        }

        if (!$file) {
            return;
        }

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->firstOrFail();

        if ($this->userCannotUploadImageIn($dataType, 'add') && $this->userCannotUploadImageIn($dataType, 'edit')) {
            abort(403);
        }

        $path = $slug.'/'.date('FY').'/';

        $filename = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension());
        $filename_counter = 1;

        // Make sure the filename does not exist, if it does make sure to add a number to the end 1, 2, 3, etc...
        while (Storage::disk(config('voyager.storage.disk'))->exists($path.$filename.'.'.$file->getClientOriginalExtension())) {
            $filename = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension()).(string) ($filename_counter++);
        }

        $fullPath = $path.$filename.'.'.$file->getClientOriginalExtension();

        $ext = $file->guessClientExtension();

        if (in_array($ext, ['jpeg', 'jpg', 'png', 'gif'])) {
            $image = Image::make($file)
                ->resize($resizeWidth, $resizeHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            if ($ext !== 'gif') {
                $image->orientate();
            }
            $image->encode($file->getClientOriginalExtension(), 75);

            // move uploaded file from temp to uploads directory
            if (Storage::disk(config('voyager.storage.disk'))->put($fullPath, (string) $image, 'public')) {
                $status = __('voyager::media.success_uploading');
                $fullFilename = $fullPath;
            } else {
                $status = __('voyager::media.error_uploading');
            }
        } else {
            $status = __('voyager::media.uploading_wrong_type');
        }

        // Return URL for Jodit (as JSON)
        $url = Voyager::image($fullFilename);
        // Convert to relative path to avoid domain lock-in (e.g. /storage/path/img.jpg)
        $path = parse_url($url, PHP_URL_PATH);
        
        return response()->json($path);
    }

    protected function userCannotUploadImageIn($dataType, $action)
    {
        return auth()->user()->cannot($action, app($dataType->model_name))
                || $dataType->{$action.'Rows'}->where('type', 'rich_text_box')->count() === 0;
    }
}

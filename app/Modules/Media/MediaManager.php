<?php

namespace App\Modules\Media;

use App\Modules\Base\BaseManager;
use Facades\App\Services\Util;
use Illuminate\Http\Request;
use App\Enums\ErrorTypesEnum;
use App\Enums\MediaTypesEnum;
use App\Media;
use DB;
use Log;
use Auth;
use Storage;
use Image;

class MediaManager extends BaseManager
{
    /**
     * Array off allowed images extension
     *
     * @var array
     */
    protected $imagesAllowed = ['gif', 'png', 'jpg', 'jpeg'];
/**
     * Mazimum upload size for image in MB
     *
     * @var string
     */
    protected $maxImageSizeAllowed = '5';
/**
     * Mazimum upload size for image in MB
     *
     * @var string
     */
    protected $maxVideoSizeAllowed = '5';
/**
     * Array of allowed videos extension
     *
     * @var array
     */
    protected $videosAllowed = ['mp4', 'webm', 'ogg'];
/**
     * Thumbnail sizes.
     *
     * @var array
     */
    protected $thumbnailSizes = [
        'small' => [
            'width' => 200,
            'height' => null
        ],
    ];
    public function index()
    {
        $pagination = Media::where([
            ['type', MediaTypesEnum::IMAGE],
            ['uploaded_by', Auth::id()],
        ])
            ->latest()
            ->paginate();
        $paginationItems = $pagination->items();
        foreach ($paginationItems as $paginationItem) {
            $paginationItem->url = route(
                $paginationItem->public ? 'media.public' : 'media.protected',
                ['refId' => $paginationItem->ref_id, 'filename' => $paginationItem->name]
            );
            $thumbnail = $this->thumbnail($paginationItem, 'small');
            if (!empty($thumbnail)) {
                $paginationItem->thumbnails = [$thumbnail];
            } else {
                $pagination->thumbnails = [];
            }
        }

        $pagination = $pagination->toArray();
        unset($pagination['data']);
        return $this->fillResponse([
            'data' => $paginationItems,
            'pagination' => $pagination
        ])->response();
    }

    /**
     * Get image.
     *
     * @param string $filename
     * @return void
     */
    public function show($filename)
    {
        $userId = Auth::id();
        $filePath = 'media/users/' . $userId . '/' . $filename;
        if (!Storage::disk('local')->exists($filePath)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Unable to found image.',
            ])->response();
        }

        return response()->file(storage_path('app/') . $filePath);
    }

    /**
     * Get public media.
     *
     * @param string $refId Media unique reference.
     * @param string $filename Media orginal filename.
     * @return void
     */
    public function media($refId, $filename)
    {
        $media = Media::where('ref_id', $refId)->firstOrFail();
        $size = $this->getThumbnailSize($filename);
        if ($media->name !== $filename && empty($size)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Media name is incorrect',
            ])->response();
        }

        $filePath = $media->path . '/media__' . $media->id . '.' . $media->extension;
        if (!empty($size)) {
            $filePath = $media->path . '/media__' . $media->id . '.' . $size . '.' . $media->extension;
        }

        if (!Storage::disk('local')->exists($filePath)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Media no longer exists.'
            ])->response();
        }

        return response()->file(storage_path('app/') . $filePath);
    }

    /**
     * Get protected media.
     *
     * @param string $refId Media unique reference.
     * @param string $filename Media orginal filename.
     * @return void
     */
    public function protectedMedia($refId, $filename)
    {
        $media = Media::where('ref_id', $refId)->firstOrFail();
        if ($media->uploaded_by !== Auth::id()) {
            return $this->fillResponse([
                'code' => 403,
                'error_type' => ErrorTypesEnum::UNAUTHORIZED,
                'error_message' => 'You\'r not authorized to use this media',
            ])->response();
        }

        if ($media->name !== $filename) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Media name is incorrect',
            ])->response();
        }

        $filePath = $media->path . '/media__' . $media->id . '.' . $media->extension;
        if (!Storage::disk('local')->exists($filePath)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Media no longer exists.'
            ])->response();
        }

        return response()->file(storage_path('app/') . $filePath);
    }

    /**
     * Get media by filename.
     *
     * @param string $filename Media filename.
     * @return void
     */
    public function publicMedia($filename)
    {
        $filenameParts = explode('__', pathinfo($filename)['filename']);
        if (count($filenameParts) < 2) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Media Not Found',
            ])->response();
        }

        if (!is_numeric($filenameParts[count($filenameParts) - 1])) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Media filename is incorrect',
            ])->response();
        }

        $media = Media::findOrFail($filenameParts[1]);
        if (!$media->public) {
            return $this->fillResponse([
                'code' => 403,
                'error_type' => ErrorTypesEnum::UNAUTHORIZED,
                'error_message' => 'Media filename is incorrect',
            ])->response();
        }

        $userId = $media->uploaded_by;
        $filePath = 'media/users/' . $userId . '/' . $filename;
        if (!Storage::disk('local')->exists($filePath)) {
            return $this->fillResponse([
                'code' => 404,
                'error_type' => ErrorTypesEnum::RESOURCE_NOT_FOUND,
                'error_message' => 'Unable to found image.'
            ])->response();
        }

        return response()->file(storage_path('app/') . $filePath);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();
        if (!$request->file('media_file')->isValid()) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::INCOMPLETE_UPLOAD,
                'error_message' => 'File didn\'t upload properly.',
            ])->response();
        }

        $media = $request->file('media_file');
        $mime = $media->getMimeType();
        $mediaExtension = pathinfo($media->getClientOriginalName(), PATHINFO_EXTENSION);
        if (!$this->verifySize($media)) {
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::MEDIA_SIZE_EXCEEDED,
                'error_message' => 'Media size should be under ' . $this->maxImageSizeAllowed . ' MB.',
            ])->response();
        }

        if ($this->isVideo($mime)) {
            if (!in_array($mediaExtension, $this->videosAllowed)) {
                return $this->fillResponse([
                    'code' => 400,
                    'error_type' => ErrorTypesEnum::MEDIA_TYPE_NOT_ALLOWED,
                    'error_message' => 'Video of extension ' . $mediaExtension . ' is not allowed',
                ])->response();
            }
        } elseif ($this->isImage($mime)) {
            if (!in_array(strtolower($mediaExtension), $this->imagesAllowed)) {
                return $this->fillResponse([
                    'code' => 400,
                    'error_type' => ErrorTypesEnum::MEDIA_TYPE_NOT_ALLOWED,
                    'error_message' => 'Image of extension ' . $mediaExtension . ' is not allowed',
                ])->response();
            }
        } else {
            return $this->fillResponse([
                'code' => 400,
                'error_type', ErrorTypesEnum::MEDIA_TYPE_UNKNOWN,
                'error_message' => 'The mime type of uploaded media is unknown.',
            ])->response();
        }

        try {
            DB::beginTransaction();
            $isPublic = false;
            if ($request->has('public')) {
                $isPublic = $request->input('public');
            }

            $m = Media::create([
                'name' => $media->getClientOriginalName(),
                'path' => 'media/users/' . $userId,
                'extension' => $mediaExtension,
                'type' => $this->isImage($mime) ? MediaTypesEnum::IMAGE : MediaTypesEnum::VIDEO,
                'uploaded_by' => $userId,
                'public' => $isPublic,
                'ref_id' => Util::uuid4()
            ]);
            $mediaName = 'media__' . $m->id . '.' . $m->extension;
            $media->storeAs($m->path, $mediaName);
            $mediaPath = $m->path . '/media__' . $m->id . '.' . $m->extension;
            if (file_exists(storage_path('app/') . $mediaPath)) {
                $m->size_bytes = Image::make(storage_path('app/') . $mediaPath)->filesize();
                $m->save();
            }

            $thumbnailPath = $m->path . '/media__' . $m->id . '.small.' . $m->extension;
            $thumbnail = $this->thumbnail($m, 'small');
            DB::commit();
            $m->url = $this->getMediaUrl($m);
            $m->thumbnails = [$thumbnail];
            return $this->fillResponse([
                'code' => 200,
                'data' => $m->toArray(),
            ])->response();
        } catch (\Exception $e) {
            DB::rollback();
            Util::logException($e);
            return $this->fillResponse([
                'code' => 400,
                'error_type' => ErrorTypesEnum::RESOURCE_CREATE_ERROR,
                'error_message' => 'Unable to save image please try again.'
            ])->response();
        }
    }

    public function destroy(Media $media)
    {
        $media->delete();
        return $this->response();
    }

    /**
     * Get media URL.
     *
     * @param Media $media
     * @return string
     */
    protected function getMediaUrl(Media $media)
    {
        return route(
            $media->public ? 'media.public' : 'media.protected',
            ['refId' => $media->ref_id, 'filename' => $media->name]
        );
    }

    /**
     * General thumbnail.
     *
     * @param Media $media Media instance.
     * @param string $size Thumbnail size.
     * @return void
     */
    protected function thumbnail(Media $media, $size)
    {
        if (empty($this->thumbnailSizes[$size])) {
            return;
        }

        $thumbnailSize = $this->thumbnailSizes[$size];
        $mediaPath = $media->path . '/media__' . $media->id . '.' . $media->extension;
        $thumbnailPath = $media->path . '/media__' . $media->id . '.' . $size . '.' . $media->extension;
        if (!file_exists(storage_path('app/') . $mediaPath)) {
            return false;
        }

        if (!file_exists(storage_path('app/') . $thumbnailPath)) {
            $thumbnail = Image::make(storage_path('app/') . $mediaPath)
                ->resize($thumbnailSize['width'], $thumbnailSize['height'], function ($constraint) {

                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save(storage_path('app/') . $thumbnailPath);
        } else {
            $thumbnail = Image::make(storage_path('app/') . $thumbnailPath);
        }

        $extension = pathinfo($media->name, PATHINFO_EXTENSION);
        $name = str_replace($extension, $size . '.' . $extension, $media->name);
        return [
            'size' => $size,
            'width' => $thumbnail->width(),
            'height' => $thumbnail->height(),
            'url' => route(
                $media->public ? 'media.public' : 'media.protected',
                ['refId' => $media->ref_id, 'filename' => $name]
            )
        ];
    }

    /**
     * Check thumbnail size is registered.
     *
     * @param string $size
     * @return boolean
     */
    protected function isThumbnailSize($size)
    {
        if (empty($size)) {
            return false;
        }

        if (empty($this->thumbnailSizes[$size])) {
            return false;
        }

        return true;
    }

    /**
     * Get thumbnail size from filename.
     *
     * @param string $filename
     * @return void
     */
    protected function getThumbnailSize($filename)
    {
        $fileParts = explode('.', $filename);
        if (count($fileParts) < 3) {
            $size = null;
        } else {
            $size = $fileParts[count($fileParts) - 2];
        }

        return $this->isThumbnailSize($size) ? $size : null;
    }

    /**
     * Verify media size.
     *
     * @param Illuminate\Http\UploadedFile $media
     * @return void
     */
    protected function verifySize($media)
    {
        $maxSize = $this->bytesToMB($media->getMaxFilesize());
        $fileSize = $this->bytesToMB($media->getSize());

        if (
            $fileSize > $this->maxImageSizeAllowed ||
            $fileSize > $maxSize
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check mime is of type video.
     *
     * @param string $mime
     * @return boolean
     */
    protected function isVideo($mime)
    {
        return strstr($mime, 'video/');
    }

     /**
     * Check mime is of type image.
     *
     * @param string $mime
     * @return boolean
     */
    protected function isImage($mime)
    {
        return strstr($mime, 'image/');
    }

     /**
     * Convert bytes to mega bytes.
     *
     * @param string $bytes
     * @return mixed
     */
    protected function bytesToMB($bytes)
    {
        return round($bytes / (1024 * 1024), 2);
    }
}

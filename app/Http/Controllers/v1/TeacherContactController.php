<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Admin\ContactAdminTeacher;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeacherContactController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function teacherAdminContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|max:255',
            'teacher_query' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ], [
            'topic.required' => 'Topic is required',
            'teacher_query.required' => 'Query content is required',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be jpeg, png, jpg, or gif',
            'image.max' => 'Image size must not exceed 2MB'
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $imagePath = null;
            $imageUrl = null;

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $imagePath = $file->store('accounts/contacts/teacher-images', 's3');

                if ($imagePath !== false) {
                    Storage::disk('s3')->setVisibility($imagePath, 'public');
                    $imageUrl = Storage::disk('s3')->url($imagePath);
                } else {
                    return $this->responseService->errorResponse(
                        'Image upload failed',
                        500
                    );
                }
            }

            $contact = ContactAdminTeacher::create([
                'teacher_detail_id' => $user->teacherDetail->id,
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'topic' => $request->topic,
                'teacher_query' => $request->teacher_query,
                'image' => $imagePath,
                'admin_reply' => false
            ]);

            $responseData = $contact->toArray();
            $responseData['image_url'] = $imageUrl;

            return $this->responseService->success(
                $responseData,
                'Teacher contact request submitted successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    public function teacherAdminContactList()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $contacts = ContactAdminTeacher::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(30)
                ->get()
                ->map(function ($contact) {
                    $contactData = $contact->toArray();
                    $contactData['image_url'] = $contact->image
                        ? Storage::disk('s3')->url($contact->image)
                        : null;
                    return $contactData;
                });

            return $this->responseService->success(
                $contacts,
                'Teacher contact list retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    public function teacherAdminContactReply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_id' => 'required|integer|exists:contact_admin_teachers,id',
        ], [
            'contact_id.required' => 'Contact ID is required',
            'contact_id.exists' => 'Contact record not found',
        ]);

        if ($validator->fails()) {
            return $this->responseService->errorResponse(
                implode(', ', $validator->errors()->all()),
                400
            );
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return $this->responseService->errorResponse(
                    'Authentication required',
                    401
                );
            }

            $contact = ContactAdminTeacher::find($request->contact_id);

            if ($contact->user_id !== $user->id) {
                return $this->responseService->errorResponse(
                    'Unauthorized access to this contact',
                    403
                );
            }

            if ($contact->admin_reply) {
                return $this->responseService->success(
                    [
                        'admin_text' => $contact->admin_text,
                        'has_reply' => true,
                        'replied_at' => $contact->updated_at
                    ],
                    'Admin reply retrieved successfully'
                );
            } else {
                return $this->responseService->success(
                    [
                        'admin_text' => null,
                        'has_reply' => false,
                        'message' => 'Reply not yet received'
                    ],
                    'Waiting for admin reply'
                );
            }
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }
}

<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Student\Chapter;
use App\Models\Student\Topic;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function saveChapterTopic(Request $request)
    {
        try {
            $user = Auth::user();
            $organizationId = $user->organization_id;

            // Validate the request structure
            $request->validate([
                'chapters' => 'required|array',
                'chapters.*.standard_id' => 'required|exists:standards,id',
                'chapters.*.section_id' => 'required|exists:sections,id',
                'chapters.*.subject_id' => 'required|exists:subjects,id',
                'chapters.*.name' => 'required|string',
                'chapters.*.description' => 'nullable|string',
                'chapters.*.topics' => 'required|array',
                'chapters.*.topics.*.topic_name' => 'required|string',
                'chapters.*.topics.*.topic_content' => 'required|string',
                'chapters.*.topics.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'chapters.*.topics.*.pdf' => 'nullable|mimes:pdf|max:5120',
            ]);

            DB::beginTransaction();

            $savedData = [];

            foreach ($request->chapters as $chapterData) {
                // Create or update chapter
                $chapter = Chapter::updateOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'standard_id' => $chapterData['standard_id'],
                        'section_id' => $chapterData['section_id'],
                        'subject_id' => $chapterData['subject_id'],
                        'name' => $chapterData['name'],
                    ],
                    [
                        'user_id' => $user->id,
                        'description' => $chapterData['description'] ?? null,
                    ]
                );

                $chapterTopics = [];

                foreach ($chapterData['topics'] as $topicData) {
                    $imagePath = null;
                    $pdfPath = null;

                    // Handle image upload if present
                    if (!empty($topicData['image']) && $topicData['image'] instanceof \Illuminate\Http\UploadedFile) {
                        $imageFile = $topicData['image'];
                        $imagePath = $imageFile->store('admin/content/topic-images', 's3');
                        Storage::disk('s3')->setVisibility($imagePath, 'public');
                        $imageUrl = Storage::disk('s3')->url($imagePath); // Get full URL
                    } else {
                        $imageUrl = null;
                    }

                    // Handle PDF upload if present
                    if (!empty($topicData['pdf']) && $topicData['pdf'] instanceof \Illuminate\Http\UploadedFile) {
                        $pdfFile = $topicData['pdf'];
                        $pdfPath = $pdfFile->store('admin/content/topic-pdfs', 's3');
                        Storage::disk('s3')->setVisibility($pdfPath, 'public');
                        $pdfUrl = Storage::disk('s3')->url($pdfPath); // Get full URL
                    } else {
                        $pdfUrl = null;
                    }

                    // Create topic
                    $topic = Topic::create([
                        'organization_id' => $organizationId,
                        'chapter_id' => $chapter->id,
                        'topic_name' => $topicData['topic_name'],
                        'topic_content' => $topicData['topic_content'],
                        'image_path' => $imageUrl,
                        'pdf_path' => $pdfUrl,
                    ]);

                    $chapterTopics[] = [
                        'id' => $topic->id,
                        'topic_name' => $topic->topic_name,
                        'topic_content' => $topic->topic_content,
                        'image_url' => $imageUrl ?? null,
                        'pdf_url' => $pdfUrl ?? null,
                    ];
                }

                $savedData[] = [
                    'chapter_id' => $chapter->id,
                    'chapter_name' => $chapter->name,
                    'topics' => $chapterTopics,
                ];
            }

            DB::commit();

            return $this->responseService->success(
                $savedData,
                'Chapters and topics saved successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Validation error: ' . $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'An error occurred: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Create a single chapter (no topics required).
     *
     * Used by the teacher syllabus screen where chapters are added one at a time
     * and may start empty. Idempotent on (org, standard, section, subject, name).
     */
    public function createChapter(Request $request)
    {
        try {
            $user = Auth::user();

            $request->validate([
                'standard_id' => 'required|exists:standards,id',
                'section_id'  => 'required|exists:sections,id',
                'subject_id'  => 'required|exists:subjects,id',
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string',
                'order'       => 'nullable|integer|min:0',
            ]);

            $chapter = Chapter::updateOrCreate(
                [
                    'organization_id' => $user->organization_id,
                    'standard_id'     => $request->standard_id,
                    'section_id'      => $request->section_id,
                    'subject_id'      => $request->subject_id,
                    'name'            => $request->name,
                ],
                [
                    'user_id'     => $user->id,
                    'description' => $request->description,
                    'order'       => $request->input('order', 0),
                ]
            );

            $chapter->load('topics');

            return $this->responseService->success($chapter, 'Chapter created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseService->errorResponse('Validation error: ' . $e->getMessage(), 422, $e->errors());
        } catch (Exception $e) {
            return $this->responseService->errorResponse('Failed to create chapter: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a single topic under an existing chapter.
     *
     * Syllabus topics are just names, so topic_content is optional here (unlike
     * the bulk content upload which expects full topic content).
     */
    public function createTopic(Request $request)
    {
        try {
            $user = Auth::user();

            $request->validate([
                'chapter_id'    => 'required|exists:chapters,id',
                'topic_name'    => 'required|string|max:255',
                'topic_content' => 'nullable|string',
                'order'         => 'nullable|integer|min:0',
                'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
                'pdf'           => 'nullable|mimes:pdf|max:10240',
                'link'          => 'nullable|string|max:2048',
            ]);

            // Ensure the chapter belongs to the caller's organization.
            $chapter = Chapter::where('organization_id', $user->organization_id)
                ->findOrFail($request->chapter_id);

            // Optional study-content media (image / pdf) — same disk + layout the
            // bulk importer and updateTopic use, so admin & student see the same.
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('admin/content/topic-images', 's3');
                Storage::disk('s3')->setVisibility($imagePath, 'public');
            }

            $pdfPath = null;
            if ($request->hasFile('pdf')) {
                $pdfPath = $request->file('pdf')->store('admin/content/topic-pdfs', 's3');
                Storage::disk('s3')->setVisibility($pdfPath, 'public');
            }

            $topic = Topic::create([
                'organization_id' => $user->organization_id,
                'chapter_id'      => $chapter->id,
                'topic_name'      => $request->topic_name,
                'topic_content'   => $request->topic_content,
                'order'           => $request->input('order', 0),
                'image_path'      => $imagePath,
                'pdf_path'        => $pdfPath,
                'link'            => $request->link,
            ]);

            return $this->responseService->success($topic, 'Topic created successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseService->errorResponse('Chapter not found', 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseService->errorResponse('Validation error: ' . $e->getMessage(), 422, $e->errors());
        } catch (Exception $e) {
            return $this->responseService->errorResponse('Failed to create topic: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all chapters with topics
     */
    public function getChapterTopics(Request $request)
    {
        try {
            $user = Auth::user();

            $query = Chapter::with(['topics' => function ($q) {
                $q->orderBy('order')->orderBy('id');
            }])->where('organization_id', $user->organization_id);

            if ($user->role === 'user') {
                // Students only ever see chapters for their own class + section.
                // Force the scope from their record and ignore any client-supplied
                // standard/section so a student cannot read another class's syllabus.
                $student = \App\Models\Student\StudentDetail::where('user_id', $user->id)
                    ->first(['standard_id', 'section_id']);

                if (!$student) {
                    return $this->responseService->errorResponse('Student record not found', 404);
                }

                $query->where('standard_id', $student->standard_id);
                if ($student->section_id) {
                    $query->where(function ($q) use ($student) {
                        $q->where('section_id', $student->section_id)
                          ->orWhere('section_id', 0)
                          ->orWhereNull('section_id');
                    });
                }

                if ($request->filled('subject_id')) {
                    $query->where('subject_id', $request->subject_id);
                }
            } else {
                // Teachers / admins filter explicitly by the class+section+subject.
                if ($request->filled('standard_id')) {
                    $query->where('standard_id', $request->standard_id);
                }
                if ($request->filled('section_id')) {
                    $query->where('section_id', $request->section_id);
                }
                if ($request->filled('subject_id')) {
                    $query->where('subject_id', $request->subject_id);
                }
            }

            // Pagination
            $perPage = $request->per_page ?? 10;
            $chapters = $query->orderBy('order')->orderBy('id')->paginate($perPage);

            // Format response with pagination metadata
            $response = [
                'data' => $chapters->items(),
                'pagination' => [
                    'total' => $chapters->total(),
                    'per_page' => $chapters->perPage(),
                    'current_page' => $chapters->currentPage(),
                    'last_page' => $chapters->lastPage(),
                    'from' => $chapters->firstItem(),
                    'to' => $chapters->lastItem()
                ]
            ];

            return $this->responseService->success($response, 'Chapters fetched successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to fetch chapters: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update chapter name
     */
    public function updateChapterName(Request $request, $chapterId)
    {
        try {
            $chapter = Chapter::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($chapterId);

            // Only touch fields that were actually sent so the lean name+order
            // editor doesn't wipe an existing description.
            $payload = [];
            if ($request->filled('name')) {
                $payload['name'] = $request->name;
            }
            if ($request->has('description')) {
                $payload['description'] = $request->description;
            }
            if ($request->has('order')) {
                $payload['order'] = (int) $request->input('order', 0);
            }
            $chapter->update($payload);

            return $this->responseService->success(
                $chapter,
                'Chapter updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseService->errorResponse('Chapter not found', 404);
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to update chapter: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Delete chapter and its topics
     */
    public function deleteChapter($chapterId)
    {
        DB::beginTransaction();
        try {
            $chapter = Chapter::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($chapterId);
            // Delete all associated topics and their files
            $chapter->topics->each(function ($topic) {
                if ($topic->image_path) Storage::disk('s3')->delete($topic->image_path);
                if ($topic->pdf_path) Storage::disk('s3')->delete($topic->pdf_path);
                $topic->delete();
            });

            $chapter->delete();
            DB::commit();

            return $this->responseService->success(
                [],
                'Chapter and its topics deleted successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->responseService->errorResponse('Chapter not found', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Failed to delete chapter: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update topic
     */
    public function updateTopic(Request $request, $topicId)
    {
        try {
            $topic = Topic::whereHas('chapter', function ($q) {
                $q->where('organization_id', Auth::user()->organization_id);
            })
                ->findOrFail($topicId);

            DB::beginTransaction();

            // Handle file updates
            $imagePath = $topic->image_path;
            $pdfPath = $topic->pdf_path;

            if ($request->hasFile('image')) {
                if ($imagePath) Storage::disk('s3')->delete($imagePath);
                $imagePath = $request->file('image')->store('admin/content/topic-images', 's3');
                Storage::disk('s3')->setVisibility($imagePath, 'public');
            }

            if ($request->hasFile('pdf')) {
                if ($pdfPath) Storage::disk('s3')->delete($pdfPath);
                $pdfPath = $request->file('pdf')->store('admin/content/topic-pdfs', 's3');
                Storage::disk('s3')->setVisibility($pdfPath, 'public');
            }

            $topic->update([
                'topic_name' => $request->topic_name ?? $topic->topic_name,
                'topic_content' => $request->has('topic_content') ? $request->topic_content : $topic->topic_content,
                'order' => $request->has('order') ? (int) $request->input('order', 0) : $topic->order,
                'image_path' => $imagePath,
                'pdf_path' => $pdfPath,
                'link' => $request->has('link') ? $request->link : $topic->link,
            ]);

            DB::commit();

            return $this->responseService->success(
                $topic,
                'Topic updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Validation error: ' . $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Failed to update topic: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Delete topic
     */
    public function deleteTopic($topicId)
    {
        DB::beginTransaction();
        try {
            $topic = Topic::whereHas('chapter', function ($q) {
                $q->where('organization_id', Auth::user()->organization_id);
            })
                ->findOrFail($topicId);

            // Delete associated files
            if ($topic->image_path) Storage::disk('s3')->delete($topic->image_path);
            if ($topic->pdf_path) Storage::disk('s3')->delete($topic->pdf_path);

            $topic->delete();
            DB::commit();

            return $this->responseService->success(
                [],
                'Topic deleted successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->responseService->errorResponse('Topic not found', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Failed to delete topic: ' . $e->getMessage(),
                500
            );
        }
    }
}

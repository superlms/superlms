<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Student\StudentSyllabus;
use App\Services\ResponseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyllabusController extends Controller
{
    protected ResponseService $responseService;

    public function __construct(ResponseService $responseService)
    {
        $this->responseService = $responseService;
    }

    public function saveSyllabus(Request $request)
    {
        try {
            $user = Auth::user();
            $organizationId = $user->organization_id;

            // Validate the request
            $request->validate([
                'standard_id' => 'required|exists:standards,id',
                'section_id' => 'required|exists:sections,id',
                'subject_id' => 'required|exists:subjects,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'syllabus_pdf' => 'required|mimes:pdf|max:10240', 
                'metadata' => 'nullable|array',
            ]);

            DB::beginTransaction();

            // Handle PDF upload
            $pdfPath = null;
            $pdfUrl = null;

            if ($request->hasFile('syllabus_pdf') && $request->file('syllabus_pdf')->isValid()) {
                $pdfFile = $request->file('syllabus_pdf');
                $pdfPath = $pdfFile->store('admin/syllabus/pdfs', 's3');
                Storage::disk('s3')->setVisibility($pdfPath, 'public');
                $pdfUrl = Storage::disk('s3')->url($pdfPath);
            }

            // Create syllabus
            $syllabus = StudentSyllabus::create([
                'organization_id' => $organizationId,
                'standard_id' => $request->standard_id,
                'section_id' => $request->section_id,
                'subject_id' => $request->subject_id,
                'user_id' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'metadata' => array_merge($request->metadata ?? [], [
                    'pdf_path' => $pdfPath,
                    'pdf_url' => $pdfUrl,
                    'file_name' => $request->file('syllabus_pdf')->getClientOriginalName(),
                    'file_size' => $request->file('syllabus_pdf')->getSize(),
                ])
            ]);

            DB::commit();

            $responseData = [
                'syllabus_id' => $syllabus->id,
                'name' => $syllabus->name,
                'description' => $syllabus->description,
                'standard_id' => $syllabus->standard_id,
                'section_id' => $syllabus->section_id,
                'subject_id' => $syllabus->subject_id,
                'pdf_url' => $pdfUrl,
                'file_name' => $request->file('syllabus_pdf')->getClientOriginalName(),
                'uploaded_at' => $syllabus->created_at,
            ];

            return $this->responseService->success(
                $responseData,
                'Syllabus uploaded successfully'
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
     * Update syllabus with optional PDF update
     */
    public function updateSyllabus(Request $request, $syllabusId)
    {
        try {
            $syllabus = StudentSyllabus::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($syllabusId);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'standard_id' => 'sometimes|required|exists:standards,id',
                'section_id' => 'sometimes|required|exists:sections,id',
                'subject_id' => 'sometimes|required|exists:subjects,id',
                'syllabus_pdf' => 'nullable|mimes:pdf|max:10240',
                'metadata' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $updateData = $request->only([
                'name',
                'description',
                'standard_id',
                'section_id',
                'subject_id'
            ]);

            // Handle PDF update if provided
            if ($request->hasFile('syllabus_pdf') && $request->file('syllabus_pdf')->isValid()) {
                // Delete old PDF if exists
                if ($syllabus->metadata && isset($syllabus->metadata['pdf_path'])) {
                    Storage::disk('s3')->delete($syllabus->metadata['pdf_path']);
                }

                // Upload new PDF
                $pdfFile = $request->file('syllabus_pdf');
                $pdfPath = $pdfFile->store('admin/syllabus/pdfs', 's3');
                Storage::disk('s3')->setVisibility($pdfPath, 'public');
                $pdfUrl = Storage::disk('s3')->url($pdfPath);

                // Update metadata with new PDF info
                $metadata = $syllabus->metadata ?? [];
                $updateData['metadata'] = array_merge($metadata, [
                    'pdf_path' => $pdfPath,
                    'pdf_url' => $pdfUrl,
                    'file_name' => $pdfFile->getClientOriginalName(),
                    'file_size' => $pdfFile->getSize(),
                    'updated_at' => now()->toISOString(),
                ]);
            }

            $syllabus->update($updateData);

            DB::commit();

            $responseData = [
                'syllabus_id' => $syllabus->id,
                'name' => $syllabus->name,
                'description' => $syllabus->description,
                'standard_id' => $syllabus->standard_id,
                'section_id' => $syllabus->section_id,
                'subject_id' => $syllabus->subject_id,
                'pdf_url' => $syllabus->metadata['pdf_url'] ?? null,
                'file_name' => $syllabus->metadata['file_name'] ?? null,
                'updated_at' => $syllabus->updated_at,
            ];

            return $this->responseService->success(
                $responseData,
                'Syllabus updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Validation error: ' . $e->getMessage(),
                422,
                $e->errors()
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->responseService->errorResponse('Syllabus not found', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Failed to update syllabus: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all syllabuses with PDF information
     */
    public function getSyllabuses(Request $request)
    {
        try {
            $user = Auth::user();

            $query = StudentSyllabus::with(['standard', 'section', 'subject'])
                ->where('organization_id', $user->organization_id);

            // Apply filters if provided
            if ($request->has('standard_id')) {
                $query->where('standard_id', $request->standard_id);
            }

            if ($request->has('section_id')) {
                $query->where('section_id', $request->section_id);
            }

            if ($request->has('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Sorting
            $allowedSortFields = ['created_at', 'name', 'updated_at'];
            $sortBy = in_array($request->sort_by, $allowedSortFields) ? $request->sort_by : 'created_at';
            $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->per_page ?? 10;
            $syllabuses = $query->paginate($perPage);

            // Format response with PDF information
            $formattedSyllabuses = $syllabuses->map(function ($syllabus) {
                return [
                    'id' => $syllabus->id,
                    'name' => $syllabus->name,
                    'description' => $syllabus->description,
                    'standard_id' => $syllabus->standard_id,
                    'section_id' => $syllabus->section_id,
                    'subject_id' => $syllabus->subject_id,
                    'pdf_url' => $syllabus->metadata['pdf_url'] ?? null,
                    'file_name' => $syllabus->metadata['file_name'] ?? null,
                    'file_size' => $syllabus->metadata['file_size'] ?? null,
                    'standard_name' => $syllabus->standard->name ?? null,
                    'section_name' => $syllabus->section->name ?? null,
                    'subject_name' => $syllabus->subject->name ?? null,
                    'created_at' => $syllabus->created_at,
                    'updated_at' => $syllabus->updated_at,
                ];
            });

            $response = [
                'data' => $formattedSyllabuses,
                'pagination' => [
                    'total' => $syllabuses->total(),
                    'per_page' => $syllabuses->perPage(),
                    'current_page' => $syllabuses->currentPage(),
                    'last_page' => $syllabuses->lastPage(),
                    'from' => $syllabuses->firstItem(),
                    'to' => $syllabuses->lastItem()
                ]
            ];

            return $this->responseService->success($response, 'Syllabuses fetched successfully');
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to fetch syllabuses: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Delete syllabus and its PDF file
     */
    public function deleteSyllabus($syllabusId)
    {
        DB::beginTransaction();
        try {
            $syllabus = StudentSyllabus::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($syllabusId);

            // Delete PDF file from S3 if exists
            if ($syllabus->metadata && isset($syllabus->metadata['pdf_path'])) {
                Storage::disk('s3')->delete($syllabus->metadata['pdf_path']);
            }

            $syllabus->delete();
            DB::commit();

            return $this->responseService->success(
                [],
                'Syllabus deleted successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->responseService->errorResponse('Syllabus not found', 404);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->responseService->errorResponse(
                'Failed to delete syllabus: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Download syllabus PDF
     */
    public function downloadSyllabus($syllabusId)
    {
        try {
            $syllabus = StudentSyllabus::where('organization_id', Auth::user()->organization_id)
                ->findOrFail($syllabusId);

            if (!isset($syllabus->metadata['pdf_path'])) {
                return $this->responseService->errorResponse('PDF not found for this syllabus', 404);
            }

            $filePath = $syllabus->metadata['pdf_path'];
            $fileName = $syllabus->metadata['file_name'] ?? 'syllabus.pdf';

            if (!Storage::disk('s3')->exists($filePath)) {
                return $this->responseService->errorResponse('PDF file not found', 404);
            }

            $fileContent = Storage::disk('s3')->get($filePath);

            return response($fileContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->header('Content-Length', strlen($fileContent));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->responseService->errorResponse('Syllabus not found', 404);
        } catch (Exception $e) {
            return $this->responseService->errorResponse(
                'Failed to download syllabus: ' . $e->getMessage(),
                500
            );
        }
    }
}

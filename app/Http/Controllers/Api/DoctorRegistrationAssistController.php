<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DoctorRegistrationAboutAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DoctorRegistrationAssistController extends Controller
{
    /**
     * Draft “about” text for doctor registration using Google Gemini.
     */
    public function generateAbout(Request $request, DoctorRegistrationAboutAiService $aboutAi)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'about_details' => 'required|string|min:20|max:4000',
            'fluent_languages' => 'nullable|array',
            'fluent_languages.*' => 'string|max:64',
            'education_summary' => 'nullable|string|max:2000',
            'experience_summary' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $aboutAi->generate(
                (string) $request->input('name'),
                (string) $request->input('job_title'),
                (string) $request->input('about_details'),
                is_array($request->input('fluent_languages')) ? $request->input('fluent_languages') : [],
                $request->input('education_summary'),
                $request->input('experience_summary'),
            );

            $status = $result['success'] ? 200 : (isset($result['ai_disabled']) ? 503 : 502);

            return response()->json($result, $status);
        } catch (\Throwable $e) {
            Log::error('Gemini doctor about draft exception', ['e' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not reach AI service. Please try again or write manually.',
            ], 502);
        }
    }
}

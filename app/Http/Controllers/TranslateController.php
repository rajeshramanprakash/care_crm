<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslateController extends Controller
{
    /**
     * Translate text using Gemini API
     * Supports Hinglish and any language to target language
     */
    public function translate(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:5000',
            'source_lang' => 'nullable|string|max:20',
            'target_lang' => 'nullable|string|max:20'
        ]);

        $text = $request->input('text');
        $sourceLang = $request->input('source_lang', 'hinglish'); // Default Hinglish
        $targetLang = $request->input('target_lang', 'en'); // Default English

        try {
            $apiKey = env('GEMINI_API_KEY');
            
            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'error' => 'Gemini API key not configured'
                ], 500);
            }

            $prompt = "You are a translator. The input text is in {$sourceLang}. Translate it to {$targetLang}. If the source is Hinglish (mix of Hindi and English written in Roman script), understand it properly and translate accurately. Only return the translated text, nothing else:\n\n{$text}";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 1000
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $translated = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                
                if ($translated) {
                    return response()->json([
                        'success' => true,
                        'translated' => trim($translated)
                    ]);
                }
            }

            Log::error('Gemini Translation Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Translation failed'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Translation Exception: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Translation service error'
            ], 500);
        }
    }
}

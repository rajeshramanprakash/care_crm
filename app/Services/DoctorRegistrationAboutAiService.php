<?php

namespace App\Services;

use App\Models\DoctorConsultationService;
use App\Models\DoctorRequest;

class DoctorRegistrationAboutAiService
{
    public function __construct(
        protected GeminiService $gemini
    ) {}

    /**
     * Admin: draft About from full registration record + optional admin notes in the textarea.
     *
     * @return array{success: bool, about_text?: string, message: string, ai_disabled?: bool}
     */
    public function generateForAdminDoctor(DoctorRequest $doctor, string $adminNotes = ''): array
    {
        $apiKey = (string) config('services.gemini.api_key', '');
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'AI is not configured on this server (GEMINI_API_KEY missing).',
                'ai_disabled' => true,
            ];
        }

        $name = trim((string) ($doctor->name ?? $doctor->customer_name ?? ''));
        $jobTitle = trim((string) ($doctor->job_title ?? ''));
        if ($name === '' || $jobTitle === '') {
            return [
                'success' => false,
                'message' => 'Doctor must have a name and consultation service before generating About.',
            ];
        }

        $profileBlock = $this->buildAdminDoctorProfileBlock($doctor);
        if ($profileBlock === '') {
            return [
                'success' => false,
                'message' => 'Not enough registration data on file. Add basic details, consultation service, education or experience first.',
            ];
        }

        $adminNotes = trim($adminNotes);
        $notesSection = $adminNotes !== ''
            ? "ADMIN NOTES (optional extra points from the admin editor — use together with registration data):\n---\n{$adminNotes}\n---\n\n"
            : '';

        $prompt = "You help write a professional \"About me\" section for an Indian telehealth doctor's patient-facing profile on Carelix.\n\n"
            ."TASK:\n"
            ."1. Carefully read ALL registration sections below (Basic details, Consultation service, Education, Experience, Languages).\n"
            ."2. Write 2–4 short paragraphs for patients using ONLY facts from those sections"
            .($adminNotes !== '' ? ' and the admin notes' : '')
            .".\n\n"
            ."RULES:\n"
            ."- Do NOT invent degrees, hospitals, years, cities, or achievements not present in the data.\n"
            ."- English or Hinglish tone matching the registration data is fine.\n"
            ."- Warm, professional, patient-friendly. No markdown, bullet lists, headings, or placeholders.\n"
            ."- Output ONLY the final About text, nothing else.\n\n"
            .$notesSection
            ."DOCTOR REGISTRATION DATA (source of truth — review every section):\n"
            ."---\n"
            .$profileBlock
            ."\n---";

        $text = $this->gemini->generateText($prompt, 0.5, 2048);
        $text = is_string($text) ? trim($text) : '';

        if ($text === '') {
            return [
                'success' => false,
                'message' => 'Could not generate text right now. Please try again or write manually.',
            ];
        }

        if (mb_strlen($text) > 8000) {
            $text = mb_substr($text, 0, 8000);
        }

        return [
            'success' => true,
            'about_text' => $text,
            'message' => 'About text generated from registration details.',
        ];
    }

    /**
     * Public doctor registration form: draft from applicant notes + light context.
     *
     * @param  array<int, string>  $fluentLanguageKeys
     * @return array{success: bool, about_text?: string, message: string, ai_disabled?: bool}
     */
    public function generate(
        string $name,
        string $jobTitle,
        string $aboutDetails,
        array $fluentLanguageKeys = [],
        ?string $educationSummary = null,
        ?string $experienceSummary = null,
    ): array {
        $apiKey = (string) config('services.gemini.api_key', '');
        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'AI is not configured on this server (GEMINI_API_KEY missing).',
                'ai_disabled' => true,
            ];
        }

        $aboutDetails = trim($aboutDetails);
        if (mb_strlen($aboutDetails) < 20) {
            return [
                'success' => false,
                'message' => 'Enter at least 20 characters of details about the doctor before generating.',
            ];
        }

        $name = trim($name);
        $jobTitle = trim($jobTitle);
        if ($name === '' || $jobTitle === '') {
            return [
                'success' => false,
                'message' => 'Doctor name and consultation service are required.',
            ];
        }

        $svc = DoctorConsultationService::query()
            ->where('is_active', true)
            ->where('name', $jobTitle)
            ->first();

        $langLabels = $this->fluentLanguageLabels($fluentLanguageKeys);
        $langStr = $langLabels !== [] ? implode(', ', $langLabels) : 'Not specified';

        $contextLines = [
            'Doctor display name: '.$name,
            'Primary consultation service: '.$jobTitle,
        ];
        if ($svc?->category) {
            $contextLines[] = 'Service category: '.$svc->category;
        }
        $contextLines[] = 'Languages fluent in: '.$langStr;
        $edu = trim((string) $educationSummary);
        $exp = trim((string) $experienceSummary);
        if ($edu !== '') {
            $contextLines[] = 'Education (from form): '.$edu;
        }
        if ($exp !== '') {
            $contextLines[] = 'Experience (from form): '.$exp;
        }

        $prompt = "You help Indian telehealth doctors write a professional \"About me\" section for their patient-facing profile on Carelix.\n\n"
            ."RULES:\n"
            ."- Use ONLY facts from the doctor's notes and context below. Do NOT invent degrees, hospitals, years, or achievements.\n"
            ."- Write 2–4 short paragraphs in the same language style as the doctor's notes (English or Hinglish is fine).\n"
            ."- Warm, professional tone for patients. No markdown, no bullet lists, no headings, no placeholders.\n"
            ."- Output ONLY the final about text, nothing else.\n\n"
            ."DOCTOR'S NOTES (what they wrote about themselves — base the about text on this):\n"
            ."---\n"
            .$aboutDetails."\n"
            ."---\n\n"
            ."ADDITIONAL CONTEXT:\n"
            .implode("\n", $contextLines);

        return $this->runPrompt($prompt);
    }

    /**
     * @return array{success: bool, about_text?: string, message: string}
     */
    private function runPrompt(string $prompt): array
    {
        $text = $this->gemini->generateText($prompt, 0.5, 2048);
        $text = is_string($text) ? trim($text) : '';

        if ($text === '') {
            return [
                'success' => false,
                'message' => 'Could not generate text right now. Please try again or write manually.',
            ];
        }

        if (mb_strlen($text) > 8000) {
            $text = mb_substr($text, 0, 8000);
        }

        return [
            'success' => true,
            'about_text' => $text,
            'message' => 'About text generated.',
        ];
    }

    private function buildAdminDoctorProfileBlock(DoctorRequest $doctor): string
    {
        $sections = [];

        $basic = [];
        $displayName = trim((string) ($doctor->name ?? $doctor->customer_name ?? ''));
        $basic[] = 'Full name: '.($displayName !== '' ? $displayName : '—');
        if ($doctor->lead_id) {
            $basic[] = 'Lead ID: '.$doctor->lead_id;
        }
        $mobile = trim((string) ($doctor->mobile ?? $doctor->contact_no ?? ''));
        if ($mobile !== '') {
            $basic[] = 'Mobile: '.$mobile;
        }
        $email = trim((string) ($doctor->email ?? ''));
        if ($email !== '') {
            $basic[] = 'Email: '.$email;
        }
        $gender = strtolower(trim((string) ($doctor->gender ?? '')));
        if ($gender === '' && ! empty($doctor->age) && is_string($doctor->age) && str_contains($doctor->age, '|')) {
            $gender = strtolower(trim((string) (explode('|', $doctor->age, 2)[1] ?? '')));
        }
        if ($gender !== '') {
            $basic[] = 'Gender: '.ucfirst($gender);
        }
        $city = trim((string) ($doctor->city ?? $doctor->location ?? ''));
        if ($city !== '') {
            $basic[] = 'City: '.$city;
        }
        if (count($basic) > 0) {
            $sections[] = "BASIC DETAILS\n".implode("\n", $basic);
        }

        $svcLines = [];
        $jobTitle = trim((string) ($doctor->job_title ?? ''));
        if ($jobTitle !== '') {
            $svcLines[] = 'Consultation service: '.$jobTitle;
            $svc = DoctorConsultationService::query()
                ->where('is_active', true)
                ->where('name', $jobTitle)
                ->first();
            if ($svc?->category) {
                $svcLines[] = 'Service category: '.$svc->category;
            }
        }
        $modeLabels = ['online' => 'Online', 'home_visit' => 'Home visit', 'clinic_visit' => 'Clinic visit'];
        $modes = is_array($doctor->consultation_modes) ? $doctor->consultation_modes : [];
        if ($modes !== []) {
            $labels = array_map(static fn ($m) => $modeLabels[$m] ?? $m, $modes);
            $svcLines[] = 'Consultation modes: '.implode(', ', $labels);
        }
        $specs = is_array($doctor->specializations) ? array_filter(array_map('trim', $doctor->specializations)) : [];
        if ($specs !== []) {
            $svcLines[] = 'Specializations / tags: '.implode(', ', $specs);
        }
        if ($doctor->online_charges !== null && in_array('online', $modes, true)) {
            $svcLines[] = 'Online consultation charges (₹): '.$doctor->online_charges;
        }
        if ($doctor->home_visit_charges !== null && in_array('home_visit', $modes, true)) {
            $svcLines[] = 'Home visit charges (₹): '.$doctor->home_visit_charges;
            if ($doctor->coverage_radius_km !== null) {
                $svcLines[] = 'Home visit coverage radius (km): '.$doctor->coverage_radius_km;
            }
            if (trim((string) ($doctor->base_location_address ?? '')) !== '') {
                $svcLines[] = 'Home visit base location: '.trim($doctor->base_location_address);
            }
        }
        if ($doctor->clinic_consultation_charges !== null && in_array('clinic_visit', $modes, true)) {
            $svcLines[] = 'Clinic consultation charges (₹): '.$doctor->clinic_consultation_charges;
            if (trim((string) ($doctor->clinic_name ?? '')) !== '') {
                $svcLines[] = 'Clinic name: '.trim($doctor->clinic_name);
            }
            if (trim((string) ($doctor->clinic_address ?? '')) !== '') {
                $svcLines[] = 'Clinic address: '.trim($doctor->clinic_address);
            }
        }
        if (count($svcLines) > 0) {
            $sections[] = "CONSULTATION SERVICE\n".implode("\n", $svcLines);
        }

        $eduLines = [];
        $edu = is_array($doctor->education_history) ? $doctor->education_history : [];
        foreach ($edu as $i => $row) {
            $deg = trim((string) ($row['degree'] ?? ''));
            $inst = trim((string) ($row['institution'] ?? ''));
            $year = trim((string) ($row['year_completed'] ?? ''));
            if ($deg === '' && $inst === '') {
                continue;
            }
            $line = ($i + 1).'. ';
            $line .= $deg !== '' && $inst !== '' ? $deg.' @ '.$inst : ($deg ?: $inst);
            if ($year !== '') {
                $line .= ' ('.$year.')';
            }
            $eduLines[] = $line;
        }
        if ($eduLines !== []) {
            $sections[] = "EDUCATION\n".implode("\n", $eduLines);
        }

        $expLines = [];
        $exp = is_array($doctor->experience_history) ? $doctor->experience_history : [];
        foreach ($exp as $i => $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $org = trim((string) ($row['organization'] ?? ''));
            $from = trim((string) ($row['from_year'] ?? ''));
            if ($title === '' && $org === '') {
                continue;
            }
            $line = ($i + 1).'. ';
            $line .= $title !== '' && $org !== '' ? $title.' @ '.$org : ($title ?: $org);
            if ($from !== '') {
                $to = trim((string) ($row['to_year'] ?? ''));
                $line .= ' ('.$from.($to !== '' ? ' – '.$to : ' – Present').')';
            }
            $details = trim((string) ($row['details'] ?? ''));
            if ($details !== '') {
                $line .= ' — '.$details;
            }
            $expLines[] = $line;
        }
        if ($expLines !== []) {
            $sections[] = "EXPERIENCE\n".implode("\n", $expLines);
        }

        $langLabels = $this->fluentLanguageLabels(is_array($doctor->fluent_languages) ? $doctor->fluent_languages : []);
        if ($langLabels !== []) {
            $sections[] = "LANGUAGES (fluent)\n".implode(', ', $langLabels);
        }

        $existingAbout = trim((string) ($doctor->about_text ?? ''));
        if ($existingAbout !== '') {
            $sections[] = "CURRENT ABOUT TEXT ON FILE (may revise or replace using registration facts)\n".$existingAbout;
        }

        return implode("\n\n", $sections);
    }

    /**
     * @param  array<int, string>  $fluentLanguageKeys
     * @return list<string>
     */
    private function fluentLanguageLabels(array $fluentLanguageKeys): array
    {
        $map = config('doctor_registration.fluent_languages', []);
        $langLabels = [];
        foreach ($fluentLanguageKeys as $k) {
            if (isset($map[$k])) {
                $langLabels[] = $map[$k];
            }
        }

        return $langLabels;
    }
}

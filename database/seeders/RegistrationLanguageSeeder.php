<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Services\RegistrationI18nService;
use Illuminate\Database\Seeder;

class RegistrationLanguageSeeder extends Seeder
{
    public function run(): void
    {
        $i18n = app(RegistrationI18nService::class);

        if (Language::query()->where('code', 'en')->exists()) {
            return;
        }

        $english = Language::create([
            'name' => 'English',
            'code' => 'en',
            'native_name' => 'English',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $i18n->seedTranslationsFromDefaults($english);
    }
}

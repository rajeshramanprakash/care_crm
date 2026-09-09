<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait HandlesPartnerUserDocuments
{
    protected function partnerDocumentValidationRules(): array
    {
        return [
            'mou_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'company_documents' => ['nullable', 'array', 'max:20'],
            'company_documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'remove_company_documents' => ['nullable', 'array'],
            'remove_company_documents.*' => ['string', 'max:500'],
            'remove_mou' => ['nullable', 'boolean'],
        ];
    }

    protected function partnerStorageFolder(string $type, int $userId): string
    {
        return $type.'-documents/'.$userId;
    }

    /** @return list<string> */
    protected function partnerCompanyDocumentPaths(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        return [];
    }

    protected function processPartnerDocuments(Request $request, Model $user, string $type): void
    {
        $folder = $this->partnerStorageFolder($type, (int) $user->id);
        $updates = [];

        $mouPath = $user->mou_file;
        if ($request->boolean('remove_mou') && $mouPath) {
            $this->deleteStoragePath($mouPath);
            $mouPath = null;
        }
        if ($request->hasFile('mou_file')) {
            if ($mouPath) {
                $this->deleteStoragePath($mouPath);
            }
            $mouPath = $request->file('mou_file')->store($folder.'/mou', 'public');
        }
        $updates['mou_file'] = $mouPath;

        $docs = $this->partnerCompanyDocumentPaths($user->company_documents);
        $remove = $request->input('remove_company_documents', []);
        if (is_array($remove) && $remove !== []) {
            $docs = array_values(array_filter($docs, function ($path) use ($remove) {
                return ! in_array($path, $remove, true);
            }));
            foreach ($remove as $path) {
                $this->deleteStoragePath($path);
            }
        }

        if ($request->hasFile('company_documents')) {
            foreach ($request->file('company_documents') as $file) {
                if ($file && $file->isValid()) {
                    $docs[] = $file->store($folder.'/company', 'public');
                }
            }
        }

        $updates['company_documents'] = array_values(array_unique($docs));

        $user->update($updates);
    }

    protected function deletePartnerUserFiles(Model $user): void
    {
        if (! empty($user->mou_file)) {
            $this->deleteStoragePath($user->mou_file);
        }
        foreach ($this->partnerCompanyDocumentPaths($user->company_documents) as $path) {
            $this->deleteStoragePath($path);
        }
    }

    protected function deleteStoragePath(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

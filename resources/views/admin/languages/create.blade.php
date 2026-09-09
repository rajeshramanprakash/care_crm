@extends('admin.layouts.app')
@section('title', 'Add Language')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Add Registration Language</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Auto-translate:</strong> Sirf language ka naam likhein (jaise <em>Hindi</em>, <em>Tamil</em>, <em>French</em>).
                    Save par registration form ke saare labels <strong>automatically</strong> us language mein translate ho jayenge (Google Gemini AI).
                    Manual Translations page optional hai — sirf edit karna ho toh use karein.
                </div>
                <form method="POST" action="{{ route('admin.languages.store') }}">
                    @csrf
                    @include('admin.languages.partials.form', ['language' => null, 'simple_create' => true])
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-magic mr-1"></i> Add &amp; auto-translate
                    </button>
                    <a href="{{ route('admin.languages.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

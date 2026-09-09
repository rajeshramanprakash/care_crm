@extends('admin.layouts.app')
@section('title', 'Registration translations')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h3 class="card-title mb-0">Registration form — {{ $language->displayLabel() }}</h3>
                    <p class="text-muted small mb-0 mt-1">Optional: manually fix any label. Normally auto-translate on add is enough.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.languages.auto_translate', $language) }}" class="d-inline"
                          onsubmit="return confirm('Re-translate all labels using AI?');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-magic mr-1"></i> Auto-translate again
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.languages.translations.reset', $language) }}" class="d-inline"
                          onsubmit="return confirm('Reset and auto-translate again?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Reset &amp; auto-translate</button>
                    </form>
                    <a href="{{ route('admin.languages.index') }}" class="btn btn-secondary btn-sm">Back to languages</a>
                </div>
            </div>
            <div class="card-body pb-5">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('admin.languages.translations.update', $language) }}">
                    @csrf
                    @method('PUT')

                    @foreach($grouped as $groupKey => $items)
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2 mb-3 text-primary">
                                {{ $groups[$groupKey] ?? ucfirst($groupKey) }}
                            </h5>
                            <div class="row">
                                @foreach($items as $item)
                                    <div class="col-lg-6 mb-3">
                                        <label class="small font-weight-bold text-muted d-block mb-1" for="t_{{ $item['key'] }}">
                                            {{ $item['label'] }}
                                            <span class="text-secondary font-weight-normal">({{ $item['key'] }})</span>
                                        </label>
                                        <textarea name="translations[{{ $item['key'] }}]" id="t_{{ $item['key'] }}"
                                                  class="form-control" rows="2">{{ old('translations.'.$item['key'], $item['value']) }}</textarea>
                                        @if($item['default'] && $item['default'] !== $item['value'])
                                            <small class="text-muted">Default: {{ Str::limit($item['default'], 80) }}</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save translations
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

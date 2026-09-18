@php
    $isEdit = isset($item);
    $subRows = [];

    $buildRow = function ($index, array $data) {
        $tags = $data['specialization_options_text'] ?? '';
        if ($tags === '' && isset($data['specialization_options']) && is_array($data['specialization_options'])) {
            $tags = implode("\n", $data['specialization_options']);
        }

        return [
            'id' => $data['id'] ?? null,
            'name' => $data['name'] ?? '',
            'consultation_duration_minutes' => $data['consultation_duration_minutes'] ?? 30,
            'sort_order' => $data['sort_order'] ?? 0,
            'icon_path' => $data['icon_path'] ?? null,
            'specialization_options_text' => $tags,
        ];
    };

    $oldSubs = old('sub_services');
    if (is_array($oldSubs)) {
        foreach ($oldSubs as $idx => $row) {
            if (! is_array($row) || ! empty($row['_delete'])) {
                continue;
            }
            if (trim((string) ($row['name'] ?? '')) === '' && empty($row['id'])) {
                continue;
            }
            $subRows[] = $buildRow(count($subRows), $row);
        }
    } elseif ($isEdit) {
        foreach ($item->subServices as $sub) {
            $subRows[] = $buildRow(count($subRows), [
                'id' => $sub->id,
                'name' => $sub->name,
                'consultation_duration_minutes' => $sub->consultation_duration_minutes,
                'sort_order' => $sub->sort_order,
                'icon_path' => $sub->icon_path,
                'specialization_options' => $sub->specialization_options,
            ]);
        }
    }
@endphp

<div class="dcs-panel dcs-sub-services-panel mt-4">
    <div class="dcs-panel-head d-flex flex-wrap justify-content-between align-items-center">
        <h2 class="mb-0"><i class="fas fa-sitemap"></i> Sub-services</h2>
        <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" id="dcs_add_sub_service">
            <i class="fas fa-plus mr-1"></i> Add sub-service
        </button>
    </div>
    <div class="dcs-panel-body">
        <p class="dcs-hint mb-3">Optional. Har parent service ke andar alag sub-services add kar sakte hain (name, icon, duration, order, tags).</p>
        <div id="dcs_sub_services_list">
            @foreach($subRows as $idx => $row)
                @include('admin.doctor_consultation_services.partials.sub-service-row', ['index' => $idx, 'row' => $row])
            @endforeach
        </div>
        <p class="text-muted small mb-0 d-none" id="dcs_sub_services_empty">No sub-services yet. Use <strong>Add sub-service</strong> to create one.</p>
    </div>
</div>

<template id="dcs_sub_service_row_tpl">
    @include('admin.doctor_consultation_services.partials.sub-service-row', [
        'index' => '__INDEX__',
        'row' => [
            'name' => '',
            'consultation_duration_minutes' => '',
            'sort_order' => 0,
            'specialization_options_text' => '',
        ],
    ])
</template>

@once('location-select-styles')
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
<style>
.location-select2.select2-container { width: 100% !important; display: block; }
.location-select2.select2-container--default .select2-selection--single {
    height: calc(1.5em + 0.75rem + 2px); min-height: 38px; border: 1px solid #ced4da;
    border-radius: 0.375rem; background-color: #fff; position: relative; display: flex; align-items: center;
}
.location-select2.select2-container--default.select2-container--focus .select2-selection--single,
.location-select2.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.location-select2.select2-container--default .select2-selection--single .select2-selection__rendered {
    display: flex !important; align-items: center; width: 100%;
    padding: 0.375rem 3.25rem 0.375rem 0.75rem !important; line-height: 1.5 !important;
    white-space: nowrap !important; overflow: hidden; color: #374151; box-sizing: border-box;
}
.location-select2.select2-container--default .select2-selection--single .select2-selection__placeholder,
.location-select2.select2-container--default .select2-selection--multiple .select2-selection__placeholder {
    color: #9ca3af !important;
}
.location-select2.select2-container--default .select2-selection--single .select2-selection__arrow { height: 100%; top: 0; right: 6px; }
.location-select2.select2-container--default .select2-selection--single .select2-selection__clear {
    position: absolute; right: 1.75rem; top: 50%; transform: translateY(-50%); margin: 0; z-index: 1;
    font-size: 1.1rem; line-height: 1;
}
.loc-opt-row {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    width: 100%; min-width: 0; max-width: 100%; overflow: hidden;
}
.location-select2 .loc-opt-row { flex: 1 1 auto; min-width: 0; }
.loc-opt-city {
    flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    color: #374151; font-weight: 500;
}
.loc-opt-state {
    flex: 0 0 auto; margin-left: auto; padding-left: 8px; color: #9ca3af;
    font-size: 0.92em; font-weight: 400; white-space: nowrap; text-align: right;
}
.loc-opt-placeholder { color: #9ca3af; }
.select2-results__option--highlighted .loc-opt-city { color: #fff; }
.select2-results__option--highlighted .loc-opt-state { color: rgba(255, 255, 255, 0.85); }
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background: #f3f4f6; border-color: #e5e7eb; color: #374151;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice .loc-opt-state { color: #9ca3af; }
.select2-container .select2-search--inline .select2-search__field { color: #374151; }
.select2-container .select2-search--inline .select2-search__field::placeholder { color: #9ca3af; }
.select2-dropdown { border-color: #e5e7eb; z-index: 1060; }
.select2-container--default .select2-results__option--selectable { padding: 8px 12px; }
.location-select2.select2-container--default .select2-selection--multiple {
    min-height: 38px; border: 1px solid #ced4da; border-radius: 0.375rem; padding: 2px 8px;
}
.location-select2.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: flex; flex-wrap: wrap; align-items: center; gap: 4px; padding: 0; margin: 0; white-space: normal !important;
}
.location-select2.select2-container--default .select2-selection--multiple .select2-selection__choice {
    display: inline-flex; align-items: center; max-width: 100%; margin: 2px 0;
}
.location-select2.select2-container--default .select2-selection--multiple .select2-selection__choice .loc-opt-row { max-width: 220px; }
</style>
@endonce

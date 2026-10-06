{{-- AI lead review card on a lead page. Include with ['aiType' => 'sales'|'operation', 'aiLeadId' => id]. --}}
@include('lead_ai._widget')
@if (\App\Support\LeadAi\LeadAiAccess::routePrefix())
<div id="leadAiCard" class="lead-ai-slot mb-2" style="display:none;"></div>
<script>
    if (window.LeadAiPanel) window.LeadAiPanel.mount(document.getElementById('leadAiCard'), @json($aiType), {{ (int) $aiLeadId }});
</script>
@endif

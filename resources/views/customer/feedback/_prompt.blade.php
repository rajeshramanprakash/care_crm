@php
    $feedbackPromptService = null;
    if (! session('customer_feedback_prompt_dismissed')) {
        try {
            $promptCustomer = \App\Support\CustomerPortal::current();
            $feedbackPromptService = $promptCustomer ? \App\Support\CustomerPortal::servicesAwaitingFeedback($promptCustomer)->first() : null;
        } catch (\Throwable $e) {
            $feedbackPromptService = null;
        }
    }
@endphp
@if ($feedbackPromptService)
<div id="fbPrompt" style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:2000;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:14px;max-width:420px;width:100%;padding:24px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.2);">
        <div style="font-size:40px;color:#f5a623;line-height:1;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
        <h4 class="mt-2 mb-1" style="font-weight:700;">How was your experience?</h4>
        <p class="text-muted mb-3">Your service #{{ $feedbackPromptService->id }}{{ $feedbackPromptService->patient_name ? ' for ' . $feedbackPromptService->patient_name : '' }} is completed. Please rate us — it takes 30 seconds.</p>
        <a href="{{ route('customer.feedback.index', ['service' => $feedbackPromptService->id]) }}" class="btn btn-success btn-block mb-2">Give Feedback</a>
        <button type="button" class="btn btn-link text-muted" id="fbPromptLater">Maybe later</button>
    </div>
</div>
<script>
    (function () {
        var btn = document.getElementById('fbPromptLater');
        if (!btn) return;
        btn.addEventListener('click', function () {
            document.getElementById('fbPrompt').remove();
            fetch(@json(route('customer.feedback.dismiss-prompt')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).catch(function () {});
        });
    })();
</script>
@endif

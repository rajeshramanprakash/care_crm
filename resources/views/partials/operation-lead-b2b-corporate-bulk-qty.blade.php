@if(!empty($b2bCorporateLead) && $b2bCorporateLead->bulk_qty !== null)
<div class="col-sm-6">
    <span class="text-bold mx-1" style="color: #F7941D">Bulk Qty: </span>
    <span class="mx-1">{{ (int) $b2bCorporateLead->bulk_qty }}</span>
</div>
@endif

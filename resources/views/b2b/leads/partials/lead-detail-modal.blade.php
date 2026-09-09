{{-- Lead journey modal shell — content filled via AJAX from b2b.leads.show --}}
<div class="modal fade" id="b2bLeadDetailModal" tabindex="-1" role="dialog" aria-labelledby="b2bLeadDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content b2b-detail-modal">
            <div class="modal-header b2b-detail-modal__head">
                <div>
                    <h5 class="modal-title font-weight-bold mb-0" id="b2bLeadDetailModalLabel">
                        <i class="fas fa-route mr-2 text-warning"></i>Lead journey
                    </h5>
                    <small class="text-muted" id="b2bLeadDetailUpdated">—</small>
                </div>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="b2bLeadDetailRefresh" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body b2b-detail-modal__body" id="b2bLeadDetailBody">
                <div class="text-center py-5 text-muted" id="b2bLeadDetailLoading">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <p class="mb-0">Loading lead details…</p>
                </div>
            </div>
        </div>
    </div>
</div>

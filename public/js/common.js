function handle_view_message(value = "N/A") {
    const div = document.createElement('div');
    div.classList = "modal fade";
    div.id = "viewMessageModal"
    div.setAttribute("tabindex", "-1");
    const modal_elem = `<div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Message</h4>
                <button type="button" class="btn text-secondary" onclick="handle_remove_modal('viewMessageModal')" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
            </div>
            <div class="modal-body text-sm">
                <div class="container">
                    ${value}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" onclick="handle_remove_modal('viewMessageModal')" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>`;
    div.innerHTML = modal_elem;
    document.body.appendChild(div);
    const modal = new bootstrap.Modal(div);
    modal.show();
}

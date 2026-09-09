<footer class="main-footer text-sm">
    <div class="d-flex footer-flex" style="justify-content: space-between">
        <div class="footer-left">
            <strong>Copyright &copy; {{ date('Y') }} <a href="javascript:void(0);">Carelix Healthcare</a>.</strong>
        </div>
        <div class="footer-right">
            <div> <a href="#">All rights reserved.</a></div>
        </div>
    </div>
</footer>
<style>
@media (max-width: 480px) {
    .main-footer {
        font-size: 13px !important;
        padding: 10px 8px !important;
    }
    .footer-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 4px !important;
    }
    .footer-left, .footer-right {
        width: 100% !important;
        text-align: left !important;
        margin-bottom: 2px !important;
    }
    .footer-left strong, .footer-right a {
        font-size: 13px !important;
        line-height: 1.3 !important;
    }
    .footer-right a {
        display: inline-block;
        padding: 2px 0;
    }
}
</style>
<script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('js/boot.js') }}"></script>
<script>
    function handle_view_image(image_url, image_change_request_url = null) {
        const existingModal = document.getElementById('viewImageModal');
        if (existingModal) {
            existingModal.remove();
        }
        const defaultImageUrl = "{{ asset('images/default-user.png') }}";
        if (!image_url) {
            image_url = defaultImageUrl;
        }
        const div = document.createElement('div');
        div.classList = "modal fade";
        div.id = "viewImageModal";
        div.setAttribute("tabindex", "-1");
        const modal_elem = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Image</h4>
                    <button type="button" class="btn text-secondary" onclick="handle_remove_modal('viewImageModal')" data-bs-dismiss="modal" aria-label="Close"><i class="fa fa-times"></i></button>
                </div>
                <div class="modal-body text-center">
                    <img src="${image_url}" class="rounded img-fluid" style="min-width: 20rem; height: 20rem;" />
                </div>
                <div class="modal-footer justify-content-between align-items-end">
                    <form id="imageForm" action="${image_change_request_url}" method="post" class="w-50" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Update Image?</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="customFile" name="profile_image" required>
                                <label class="custom-file-label" for="customFile">Choose file</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm m-1 text-light" style="background-color: var(--wb-dark-red);">Submit</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="handle_remove_modal('viewImageModal')" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    `;
        div.innerHTML = modal_elem;
        document.body.appendChild(div);
        const modal = new bootstrap.Modal(div);
        modal.show();

        const fileInput = document.querySelector('#customFile');
        const label = document.querySelector('label[for="customFile"]');
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                label.textContent = file.name;
                const img = document.querySelector('.modal-body img');
                img.src = URL.createObjectURL(file);
            }
        });

        const form = document.getElementById('imageForm');
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(form);

            fetch(image_change_request_url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const imgTag = document.querySelector(
                            `a[href="javascript:void(0);"] img[src="${image_url}"]`);
                        imgTag.src = data.profile_image_url;

                        modal.hide();
                        alert('Profile image updated successfully.');
                    } else {
                        alert(data.message || 'Something went wrong.');
                    }
                })
                .catch(error => {
                    alert('Error occurred. Please try again.');
                    console.error('Error:', error);
                });
        });
    }

    function handle_remove_modal(modalId) {
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            modalElement.remove();
        }
    }
</script>

{{-- </script> --}}

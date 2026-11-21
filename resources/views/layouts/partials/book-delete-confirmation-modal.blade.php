<div class="modal fade" id="bookDeleteConfirmationModal" tabindex="-1"
    aria-labelledby="bookDeleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-slate-900" id="bookDeleteConfirmationModalLabel">
                    <i class="bi bi-box-arrow-right text-danger me-1"></i> Konfirmasi Hapus Buku
                </h5>   
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-slate-600">
                Yakin ingin menghapus buku <strong class="text-slate-900" id="bookDeleteConfirmTitle"></strong>?
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn btn-danger" id="confirmBookDeleteButton">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

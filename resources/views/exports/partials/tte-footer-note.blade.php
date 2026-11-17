{{-- Teks tanda tangan elektronik di footer SPT/SPPD. Draft yang ditandatangani simulasi
     (TTE Testing) diberi label merah agar tidak dikira ditandatangani BSrE. --}}
@if ($pdfData['tte_testing'] ?? false)
	<span style="color: #dc2626; font-weight: bold; font-style: normal;">TTE TESTING</span> · Simulasi, bukan tanda tangan elektronik BSrE
@else
	Dokumen ini ditandatangani secara elektronik menggunakan Layanan BSrE
@endif

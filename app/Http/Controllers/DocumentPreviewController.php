<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

class DocumentPreviewController extends Controller
{
  /**
   * Tampilkan berkas unggahan sementara Livewire secara INLINE (bukan unduh).
   *
   * Livewire `temporaryUrl()` menyajikan berkas dengan Content-Disposition:
   * attachment sehingga memicu unduhan. Endpoint ini menyajikan berkas yang
   * sama dengan disposition inline agar bisa dipratinjau di iframe.
   */
  public function tempUpload(Request $request)
  {
    abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin_opd']), 403);

    // Hanya nama berkas (cegah path traversal).
    $filename = basename((string) $request->query('file'));
    abort_if($filename === '', 404);

    $storage = FileUploadConfiguration::storage();
    $path = FileUploadConfiguration::path($filename);

    abort_unless($storage->exists($path), 404);

    return $storage->response($path, $filename, [
      'Content-Type' => FileUploadConfiguration::mimeType($filename),
      'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
  }
}

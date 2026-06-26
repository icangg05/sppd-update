<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $department_id
 * @property string|null $program
 * @property string|null $activity
 * @property string|null $account_code
 * @property string $description
 * @property string|null $type
 * @property string|null $source Sumber anggaran, misalnya APBD, APBN, Hibah, dll
 * @property int $year Tahun anggaran
 * @property int $total_amount Pagu anggaran
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department $department
 * @property-read float $balance
 * @property-read float $realization
 * @property-read float $realization_percentage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdRequest> $sppdRequests
 * @property-read int|null $sppd_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereAccountCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereProgram($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Budget whereYear($value)
 */
	class Budget extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $parent_id
 * @property int|null $head_id
 * @property string $name
 * @property string|null $letterhead
 * @property string|null $letterhead_second
 * @property string|null $code
 * @property \App\Enums\DepartmentType $type
 * @property int $level 0=root, 1=dinas, 2=bidang, 3=seksi
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $allChildren
 * @property-read int|null $all_children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Budget> $budgets
 * @property-read int|null $budgets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $children
 * @property-read int|null $children_count
 * @property-read \App\Models\User|null $head
 * @property-read Department|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereHeadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereLetterhead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereLetterheadSecond($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 */
	class Department extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $level Urutan jabatan, semakin kecil semakin tinggi
 * @property \App\Enums\PositionScope $uniqueness_scope Batas jumlah pemangku jabatan: none, department, system
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereUniquenessScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Position whereUpdatedAt($value)
 */
	class Position extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $requested_by
 * @property int|null $department_id
 * @property string $name
 * @property string|null $reason
 * @property \App\Enums\PositionRequestStatus $status
 * @property int|null $position_id
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Position|null $position
 * @property-read \App\Models\User $requester
 * @property-read \App\Models\User|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereReviewNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PositionRequest whereUpdatedAt($value)
 */
	class PositionRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Regency> $regencies
 * @property-read int|null $regencies_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province whereUpdatedAt($value)
 */
	class Province extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $group Golongan: I, II, III, IV
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rank newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rank query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rank whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rank whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rank whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rank whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rank whereUpdatedAt($value)
 */
	class Rank extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $province_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Province $province
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Regency newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Regency newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Regency query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Regency whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Regency whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Regency whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Regency whereProvinceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Regency whereUpdatedAt($value)
 */
	class Regency extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sppd_request_id
 * @property int $user_id
 * @property string $description
 * @property int $amount
 * @property string|null $receipt_file Path file bukti/nota
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SppdRequest $sppdRequest
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense whereReceiptFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense whereSppdRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdActualExpense whereUserId($value)
 */
	class SppdActualExpense extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sppd_request_id
 * @property int $user_id
 * @property int $amount
 * @property string|null $receipt_number
 * @property string|null $receipt_file
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SppdRequest $sppdRequest
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt whereReceiptFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt whereReceiptNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt whereSppdRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdAdvanceReceipt whereUserId($value)
 */
	class SppdAdvanceReceipt extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sppd_request_id
 * @property int $approver_id
 * @property string $role_label Label jabatan penyetuju, misal: Kasubag, Sekda, Walikota
 * @property int $step_order Urutan langkah persetujuan
 * @property \App\Enums\ApprovalStatus $status pending, approved, rejected, revision
 * @property bool $signs_spt
 * @property bool $signs_sppd
 * @property \Illuminate\Support\Carbon|null $acted_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $approver
 * @property-read \App\Models\SppdRequest $sppdRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval readyForApprover(int $approverId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereActedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereApproverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereRoleLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereSignsSppd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereSignsSpt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereSppdRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereStepOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdApproval whereUpdatedAt($value)
 */
	class SppdApproval extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdRequest> $sppdRequests
 * @property-read int|null $sppd_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCategory whereUpdatedAt($value)
 */
	class SppdCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sppd_request_id
 * @property int $user_id
 * @property \App\Enums\CostCategory $cost_category
 * @property string $description Uraian biaya, misal: Tiket pesawat, Uang harian
 * @property string|null $airline_name
 * @property string|null $ticket_number
 * @property int $unit_cost
 * @property int $quantity
 * @property int $total unit_cost * quantity
 * @property string|null $receipt_photo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SppdRequest $sppdRequest
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereAirlineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereCostCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereReceiptPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereSppdRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereTicketNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereUnitCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdCostDetail whereUserId($value)
 */
	class SppdCostDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sppd_request_id
 * @property int|null $province_id
 * @property int|null $regency_id
 * @property string|null $address Alamat detail tujuan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Province|null $province
 * @property-read \App\Models\Regency|null $regency
 * @property-read \App\Models\SppdRequest $sppdRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination whereProvinceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination whereRegencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination whereSppdRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDestination whereUpdatedAt($value)
 */
	class SppdDestination extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sppd_request_id
 * @property int $signer_id
 * @property \App\Enums\SignatureStatus $status pending, signed, rejected
 * @property string $document_type sppd, spt, kuitansi
 * @property string|null $provider_id ID dokumen dari API provider
 * @property string|null $error_message Pesan error jika signing gagal
 * @property string|null $signed_file_path Path file PDF yang sudah di-sign
 * @property string|null $qr_code_data Data QR code yang di-embed
 * @property int $sign_page Halaman penandatangan
 * @property int $sign_x Koordinat X penandatangan (px)
 * @property int $sign_y Koordinat Y penandatangan (px)
 * @property int $sign_width Lebar area tanda tangan (px)
 * @property int $sign_height Tinggi area tanda tangan (px)
 * @property string $provider_name Nama provider: local_proxy, bssn, dll
 * @property \Illuminate\Support\Carbon|null $signed_at
 * @property string|null $signature_data Data tanda tangan digital atau hash
 * @property string|null $certificate_serial Nomor sertifikat elektronik
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $signed_file_url
 * @property-read \App\Models\User $signer
 * @property-read \App\Models\SppdRequest $sppdRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature processing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereCertificateSerial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereDocumentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereProviderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereQrCodeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignPage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignX($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignY($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignatureData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignedFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSignerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereSppdRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdDigitalSignature whereUpdatedAt($value)
 */
	class SppdDigitalSignature extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sppd_request_id
 * @property int $user_id
 * @property string|null $sppd_path Path file SPPD Pengikut PDF
 * @property string|null $notes
 * @property string|null $travel_position Jabatan dalam perjalanan (khusus Inspektorat)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SppdRequest $sppdRequest
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower whereSppdPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower whereSppdRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower whereTravelPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdFollower whereUserId($value)
 */
	class SppdFollower extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sppd_request_id
 * @property string|null $report_text Isi laporan kegiatan
 * @property \Illuminate\Support\Carbon|null $report_date
 * @property string|null $receipt_file Path file bukti nota utama
 * @property string|null $documentation_file Path file foto dokumentasi
 * @property string|null $report_file
 * @property int $total_expense Total pengeluaran riil keseluruhan
 * @property \App\Enums\VerificationStatus $verification_status pending, verified, returned
 * @property int|null $verified_by
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SppdRequest $sppdRequest
 * @property-read \App\Models\User|null $verifier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereDocumentationFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereReceiptFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereReportDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereReportFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereReportText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereSppdRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereTotalExpense($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereVerificationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdReport whereVerifiedBy($value)
 */
	class SppdReport extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id Pelaksana perjalanan
 * @property int $creator_id Pembuat draft
 * @property int|null $pptk_id PPTK penanggungjawab
 * @property int $budget_id
 * @property int $category_id
 * @property string|null $recipient Kepada
 * @property string $purpose Maksud perjalanan
 * @property string|null $problem Persoalan
 * @property string|null $facts Fakta yang mempengaruhi
 * @property string|null $analysis Analisis
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string|null $transport_type Jenis Angkutan
 * @property string|null $transport_name Angkutan
 * @property string|null $departure_place Tempat Berangkat
 * @property \App\Enums\SppdDomain $domain dalam_daerah, lddp, ldlp
 * @property string|null $urgency Kecepatan Telaah
 * @property \Illuminate\Support\Carbon|null $sppd_date Tanggal SPPD
 * @property \Illuminate\Support\Carbon|null $spt_date Tanggal SPT
 * @property \App\Enums\SppdStatus $status in_progress, approved, rejected, completed
 * @property string|null $document_number Nomor surat
 * @property string|null $notes
 * @property string|null $revision_note
 * @property string|null $rejection_note
 * @property int|null $reviser_id
 * @property bool $is_secretariat Penanda telaah sekretariat
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $attachment Path file dokumen pendukung
 * @property string|null $spt_path Path file SPT PDF
 * @property string|null $sppd_path Path file SPPD Pelaksana Utama PDF
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdActualExpense> $actualExpenses
 * @property-read int|null $actual_expenses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdAdvanceReceipt> $advanceReceipts
 * @property-read int|null $advance_receipts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\Budget $budget
 * @property-read \App\Models\SppdCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdCostDetail> $costDetails
 * @property-read int|null $cost_details_count
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdDestination> $destinations
 * @property-read int|null $destinations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdDigitalSignature> $digitalSignatures
 * @property-read int|null $digital_signatures_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdFollower> $followers
 * @property-read int|null $followers_count
 * @property-read int $duration_days
 * @property-read \App\Models\User|null $pptk
 * @property-read \App\Models\SppdReport|null $report
 * @property-read \App\Models\User|null $reviser
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereAnalysis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereAttachment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereBudgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereDeparturePlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereDocumentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereFacts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereIsSecretariat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest wherePptkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereProblem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereRecipient($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereRejectionNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereReviserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereRevisionNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereSppdDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereSppdPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereSptDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereSptPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereTransportName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereTransportType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereUrgency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdRequest whereUserId($value)
 */
	class SppdRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property array<array-key, mixed>|null $department_type
 * @property array<array-key, mixed>|null $applicant_role
 * @property array<array-key, mixed>|null $destination
 * @property array<array-key, mixed> $steps
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereApplicantRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereDepartmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SppdWorkflow whereUpdatedAt($value)
 */
	class SppdWorkflow extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $department_id
 * @property string $name
 * @property string|null $username
 * @property string|null $nip
 * @property string|null $nik Nomor Induk Kependudukan
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phone
 * @property bool $phone_verified
 * @property \App\Enums\EmployeeType $employee_type
 * @property int|null $rank_id
 * @property int|null $position_id
 * @property string|null $dprd_jabatan
 * @property string|null $partai
 * @property string|null $photo
 * @property bool $is_active
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdRequest> $createdSppdRequests
 * @property-read int|null $created_sppd_requests_count
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdDigitalSignature> $digitalSignatures
 * @property-read int|null $digital_signatures_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \App\Models\Position|null $position
 * @property-read \App\Models\Rank|null $rank
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SppdRequest> $sppdRequests
 * @property-read int|null $sppd_requests_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDprdJabatan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmployeeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNik($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNip($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePartai($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePositionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRankId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, ?string $guard = null)
 */
	class User extends \Eloquent {}
}


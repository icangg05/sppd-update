<?php

namespace App\Http\Controllers;

use App\Enums\CostCategory;
use App\Http\Controllers\Concerns\AuthorizesSppdAccess;
use App\Models\SppdCostDetail;
use App\Models\SppdRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SppdCostDetailController extends Controller
{
    use AuthorizesSppdAccess;

    public function store(Request $request, SppdRequest $sppd)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
        $this->authorizeSppdAccess($sppd);
        abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

        if ($request->has('user_ids')) {
            $validated = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'cost_category' => 'required|in:'.implode(',', array_column(CostCategory::cases(), 'value')),
                'description' => 'required|string|max:500',
                'unit_cost' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:1',
            ]);

            foreach ($validated['user_ids'] as $userId) {
                $sppd->costDetails()->create([
                    'user_id' => $userId,
                    'cost_category' => $validated['cost_category'],
                    'description' => $validated['description'],
                    'unit_cost' => $validated['unit_cost'],
                    'quantity' => $validated['quantity'],
                ]);
            }

            return back()->with('success', 'Rincian biaya berhasil ditambahkan untuk pegawai terpilih.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'cost_category' => 'required|in:'.implode(',', array_column(CostCategory::cases(), 'value')),
            'description' => 'required|string|max:500',
            'airline_name' => 'nullable|string|max:255',
            'ticket_number' => 'nullable|string|max:255',
            'unit_cost' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'receipt_photo' => 'nullable|image|max:20480',
        ]);

        if ($request->hasFile('receipt_photo')) {
            $validated['receipt_photo'] = $request->file('receipt_photo')
                ->store(date('Y').'/nota_perjalanan', 'public');
        }

        $sppd->costDetails()->create($validated);

        return back()->with('success', 'Rincian biaya berhasil ditambahkan.');
    }

    public function update(Request $request, SppdRequest $sppd, SppdCostDetail $cost)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
        $this->authorizeSppdAccess($sppd);
        abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

        $validated = $request->validate([
            'cost_category' => 'required|in:'.implode(',', array_column(CostCategory::cases(), 'value')),
            'description' => 'required|string|max:500',
            'airline_name' => 'nullable|string|max:255',
            'ticket_number' => 'nullable|string|max:255',
            'unit_cost' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'receipt_photo' => 'nullable|image|max:20480',
        ]);

        if ($request->hasFile('receipt_photo')) {
            // Delete old photo
            if ($cost->receipt_photo) {
                Storage::disk('public')->delete($cost->receipt_photo);
            }
            $validated['receipt_photo'] = $request->file('receipt_photo')
                ->store(date('Y').'/nota_perjalanan', 'public');
        }

        $cost->update($validated);

        return back()->with('success', 'Rincian biaya berhasil diperbarui.');
    }

    public function destroy(SppdRequest $sppd, SppdCostDetail $cost)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin_opd', 'super_admin']), 403, 'Aksi ini tidak diizinkan.');
        $this->authorizeSppdAccess($sppd);
        abort_unless(in_array($sppd->status->value, ['approved', 'completed']), 403, 'Aksi ini tidak diizinkan karena pengajuan SPPD belum disetujui sepenuhnya.');

        if ($cost->receipt_photo) {
            Storage::disk('public')->delete($cost->receipt_photo);
        }

        $cost->delete();

        return back()->with('success', 'Rincian biaya berhasil dihapus.');
    }
}

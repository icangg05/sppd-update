<?php

namespace App\Http\Controllers;

use App\Enums\CostCategory;
use App\Models\SppdCostDetail;
use App\Models\SppdRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SppdCostDetailController extends Controller
{
  public function store(Request $request, SppdRequest $sppd)
  {
    $validated = $request->validate([
      'user_id'       => 'required|exists:users,id',
      'cost_category' => 'required|in:' . implode(',', array_column(CostCategory::cases(), 'value')),
      'description'   => 'required|string|max:500',
      'airline_name'  => 'nullable|string|max:255',
      'ticket_number' => 'nullable|string|max:255',
      'unit_cost'     => 'required|numeric|min:0',
      'quantity'      => 'required|integer|min:1',
      'receipt_photo' => 'nullable|image|max:20480',
    ]);

    if ($request->hasFile('receipt_photo')) {
      $validated['receipt_photo'] = $request->file('receipt_photo')
        ->store('sppd/cost-receipts', 'public');
    }

    $sppd->costDetails()->create($validated);

    return back()->with('success', 'Rincian biaya berhasil ditambahkan.');
  }

  public function update(Request $request, SppdRequest $sppd, SppdCostDetail $cost)
  {
    $validated = $request->validate([
      'cost_category' => 'required|in:' . implode(',', array_column(CostCategory::cases(), 'value')),
      'description'   => 'required|string|max:500',
      'airline_name'  => 'nullable|string|max:255',
      'ticket_number' => 'nullable|string|max:255',
      'unit_cost'     => 'required|numeric|min:0',
      'quantity'      => 'required|integer|min:1',
      'receipt_photo' => 'nullable|image|max:20480',
    ]);

    if ($request->hasFile('receipt_photo')) {
      // Delete old photo
      if ($cost->receipt_photo) {
        Storage::disk('public')->delete($cost->receipt_photo);
      }
      $validated['receipt_photo'] = $request->file('receipt_photo')
        ->store('sppd/cost-receipts', 'public');
    }

    $cost->update($validated);

    return back()->with('success', 'Rincian biaya berhasil diperbarui.');
  }

  public function destroy(SppdRequest $sppd, SppdCostDetail $cost)
  {
    if ($cost->receipt_photo) {
      Storage::disk('public')->delete($cost->receipt_photo);
    }

    $cost->delete();

    return back()->with('success', 'Rincian biaya berhasil dihapus.');
  }
}

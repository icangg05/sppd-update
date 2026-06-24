<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
  public function create()
  {
    $departments = Department::all();
    $user = Auth::user();
    return view('budgets.create', compact('departments', 'user'));
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'department_id' => 'required|exists:departments,id',
      'year'          => 'required|integer|min:2000|max:2100',
      'program'       => 'required|string|max:255',
      'activity'      => 'required|string|max:255',
      'account_code'  => 'required|string|max:255',
      'description'   => 'required|string',
      'type'          => 'required|string',
      'source'        => 'required|string|in:APBD,APBD-P,APBN',
      'total_amount'  => 'required|numeric|min:0',
    ]);

    Budget::create($validated);

    return redirect()->route('master.budgets.index')
      ->with('success', 'Data anggaran berhasil ditambahkan.');
  }

  public function show(Budget $budget)
  {
    return view('budgets.show', compact('budget'));
  }

  public function edit(Budget $budget)
  {
    $departments = Department::all();
    return view('budgets.edit', compact('budget', 'departments'));
  }

  public function update(Request $request, Budget $budget)
  {
    $validated = $request->validate([
      'department_id' => 'required|exists:departments,id',
      'year'          => 'required|integer|min:2000|max:2100',
      'program'       => 'required|string|max:255',
      'activity'      => 'required|string|max:255',
      'account_code'  => 'required|string|max:255',
      'description'   => 'required|string',
      'type'          => 'required|string',
      'source'        => 'required|string|in:APBD,APBD-P,APBN',
      'total_amount'  => 'required|numeric|min:0',
    ]);

    $budget->update($validated);

    return redirect()->route('master.budgets.index')
      ->with('success', 'Data anggaran berhasil diperbarui.');
  }

  public function destroy(Budget $budget)
  {
    $budget->delete();

    return redirect()->route('master.budgets.index')
      ->with('success', 'Data anggaran berhasil dihapus.');
  }
}

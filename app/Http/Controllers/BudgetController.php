<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
  public function index(Request $request)
  {
    /** @var \App\Models\User $user */
    $user  = Auth::user();
    $query = Budget::query();

    // Filter by department if not super_admin
    if (!$user->hasRole('super_admin')) {
      $query->where('department_id', $user->department_id);
    }

    // Search
    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('description', 'like', "%{$search}%")
          ->orWhere('program', 'like', "%{$search}%")
          ->orWhere('activity', 'like', "%{$search}%")
          ->orWhere('account_code', 'like', "%{$search}%")
          ->orWhere('type', 'like', "%{$search}%")
          ->orWhere('source', 'like', "%{$search}%");
      });
    }

    // Filter by Year
    $year = $request->get('year', date('Y'));
    $query->where('year', $year);

    $budgets = $query->with('department')->latest()->paginate(15)->withQueryString();

    return view('budgets.index', compact('budgets', 'year'));
  }

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

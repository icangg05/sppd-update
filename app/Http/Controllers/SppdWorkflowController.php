<?php

namespace App\Http\Controllers;

use App\Models\SppdWorkflow;
use Illuminate\Http\Request;
use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use Spatie\Permission\Models\Role;

class SppdWorkflowController extends Controller
{
  public function index()
  {
    $workflows = SppdWorkflow::orderBy('id')->get();
    $roleLabels = Role::pluck('label', 'name')->all();
    return view('master.workflows.index', compact('workflows', 'roleLabels'));
  }

  // public function preview(\App\Services\SppdWorkflowService $workflowService)
  // {
  //   $user = auth()->user();
  //   $workflows = SppdWorkflow::where('is_active', true)->orderBy('id')->get();

  //   // Resolve approver names for the current user's context
  //   $roleMapping = [];

  //   // Get all unique roles needed in the workflows
  //   $neededRoles = [];
  //   foreach ($workflows as $w) {
  //     foreach ($w->steps as $step) {
  //       $neededRoles[] = $step;
  //     }
  //   }
  //   $neededRoles = array_unique($neededRoles);

  //   foreach ($neededRoles as $role) {
  //     $approver = $workflowService->resolveApprover($role, $user);
  //     if ($approver) {
  //       $roleMapping[$role] = $approver->name;
  //     }
  //   }

  //   return view('workflows.preview', compact('workflows', 'roleMapping', 'user'));
  // }

  public function create()
  {
    $departmentTypes = DepartmentType::cases();
    $domains = SppdDomain::cases();
    $roles = Role::orderBy('name')->get();

    return view('master.workflows.create', compact('departmentTypes', 'domains', 'roles'));
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name'            => 'required|string|max:255',
      'department_type' => 'nullable|array',
      'department_type.*'=> 'required|string',
      'applicant_role'  => 'nullable|array',
      'applicant_role.*'=> 'required|string',
      'destination'     => 'nullable|array',
      'destination.*'   => 'required|string',
      'steps'           => 'required|array|min:1',
      'steps.*.role'    => 'required|string',
      'steps.*.signs_spt'=> 'nullable',
      'steps.*.signs_sppd'=> 'nullable',
      'is_active'       => 'nullable',
    ]);

    $validated['steps'] = array_map(function ($step) {
      return [
        'role' => $step['role'],
        'signs_spt' => filter_var($step['signs_spt'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'signs_sppd' => filter_var($step['signs_sppd'] ?? false, FILTER_VALIDATE_BOOLEAN),
      ];
    }, $request->steps);

    $sptCount = collect($validated['steps'])->where('signs_spt', true)->count();
    if ($sptCount !== 1) {
      return back()->withInput()->withErrors(['steps' => 'Harus ada tepat satu langkah (step) yang ditugaskan untuk menandatangani SPT.']);
    }

    $sppdCount = collect($validated['steps'])->where('signs_sppd', true)->count();
    if ($sppdCount !== 1) {
      return back()->withInput()->withErrors(['steps' => 'Harus ada tepat satu langkah (step) yang ditugaskan untuk menandatangani SPPD.']);
    }

    $validated['is_active'] = $request->has('is_active');

    SppdWorkflow::create($validated);

    return redirect()->route('master.workflows.index')
      ->with('success', 'Workflow SPPD berhasil ditambahkan.');
  }

  public function edit(SppdWorkflow $workflow)
  {
    $departmentTypes = DepartmentType::cases();
    $domains = SppdDomain::cases();
    $roles = Role::orderBy('name')->get();

    return view('master.workflows.edit', compact('workflow', 'departmentTypes', 'domains', 'roles'));
  }

  public function update(Request $request, SppdWorkflow $workflow)
  {
    $validated = $request->validate([
      'name'            => 'required|string|max:255',
      'department_type' => 'nullable|array',
      'department_type.*'=> 'required|string',
      'applicant_role'  => 'nullable|array',
      'applicant_role.*'=> 'required|string',
      'destination'     => 'nullable|array',
      'destination.*'   => 'required|string',
      'steps'           => 'required|array|min:1',
      'steps.*.role'    => 'required|string',
      'steps.*.signs_spt'=> 'nullable',
      'steps.*.signs_sppd'=> 'nullable',
      'is_active'       => 'nullable',
    ]);

    $validated['steps'] = array_map(function ($step) {
      return [
        'role' => $step['role'],
        'signs_spt' => filter_var($step['signs_spt'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'signs_sppd' => filter_var($step['signs_sppd'] ?? false, FILTER_VALIDATE_BOOLEAN),
      ];
    }, $request->steps);

    $sptCount = collect($validated['steps'])->where('signs_spt', true)->count();
    if ($sptCount !== 1) {
      return back()->withInput()->withErrors(['steps' => 'Harus ada tepat satu langkah (step) yang ditugaskan untuk menandatangani SPT.']);
    }

    $sppdCount = collect($validated['steps'])->where('signs_sppd', true)->count();
    if ($sppdCount !== 1) {
      return back()->withInput()->withErrors(['steps' => 'Harus ada tepat satu langkah (step) yang ditugaskan untuk menandatangani SPPD.']);
    }

    $validated['is_active'] = $request->has('is_active');

    $workflow->update($validated);

    return redirect()->route('master.workflows.index')
      ->with('success', 'Workflow SPPD berhasil diperbarui.');
  }

  public function destroy(SppdWorkflow $workflow)
  {
    $workflow->delete();
    return redirect()->route('master.workflows.index')
      ->with('success', 'Workflow SPPD berhasil dihapus.');
  }
}

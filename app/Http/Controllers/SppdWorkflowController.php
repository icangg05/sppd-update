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
        return view('master.workflows.index', compact('workflows'));
    }

    public function preview(\App\Services\SppdWorkflowService $workflowService)
    {
        $user = auth()->user();
        $workflows = SppdWorkflow::where('is_active', true)->orderBy('id')->get();
        
        // Resolve approver names for the current user's context
        $roleMapping = [];
        
        // Get all unique roles needed in the workflows
        $neededRoles = [];
        foreach ($workflows as $w) {
            foreach ($w->steps as $step) {
                $neededRoles[] = $step;
            }
        }
        $neededRoles = array_unique($neededRoles);

        foreach ($neededRoles as $role) {
            $approver = $workflowService->resolveApprover($role, $user);
            if ($approver) {
                $roleMapping[$role] = $approver->name;
            }
        }

        return view('workflows.preview', compact('workflows', 'roleMapping', 'user'));
    }

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
            'department_type' => 'nullable|string',
            'applicant_role'  => 'nullable|string',
            'destination'     => 'nullable|array',
            'destination.*'   => 'required|string',
            'steps'           => 'required|array|min:1',
            'steps.*'         => 'required|string',
            'is_active'       => 'nullable',
        ]);

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
            'department_type' => 'nullable|string',
            'applicant_role'  => 'nullable|string',
            'destination'     => 'nullable|array',
            'destination.*'   => 'required|string',
            'steps'           => 'required|array|min:1',
            'steps.*'         => 'required|string',
            'is_active'       => 'nullable',
        ]);

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

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateTeacherAction;
use App\Enums\RolesEnum;
use App\Http\Requests\CreateTeacherRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class TeacherController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function index(): InertiaResponse
    {
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', RolesEnum::TEACHER->value);
        })->with('roles')->orderBy('created_at', 'desc')->get();

        return Inertia::render('admin/Teachers', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(CreateTeacherRequest $request, CreateTeacherAction $action): RedirectResponse
    {
        $teacher = $action->handle($request->validated());

        return redirect()->back()
            ->with('success', "Teacher '{$teacher->name}' has been created successfully.");
    }

    /**
     * Remove the specified teacher from storage.
     */
    public function destroy(User $teacher): RedirectResponse
    {
        // Ensure the user is actually a teacher
        if (! $teacher->hasRole(RolesEnum::TEACHER->value)) {
            return redirect()->back()
                ->with('error', 'User is not a teacher.');
        }

        // Remove room assignments first
        $teacher->roomAssignments()->delete();

        // Delete the teacher
        $teacher->delete();

        return redirect()->back()
            ->with('success', "Teacher '{$teacher->name}' has been deleted successfully.");
    }
}
 
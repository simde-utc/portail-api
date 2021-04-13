<?php
/**
 * Manage User roles.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2019, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Management;

use App\Admin\Controllers\Controller;
use App\Pivots\UserRole;
use App\Models\{
	User, Semester, Role
};
use Encore\Admin\Layout\Content;
use App\Admin\GridGenerator;
use Encore\Admin\Grid\Displayers\Actions;
use Illuminate\Http\Request;
use App\Notifications\Admin\MemberAccessValidation;
use Encore\Admin\Form;
use App\Admin\FormGenerator;

class UserRoleController extends Controller
{
    protected $model = UserRole::class;

    /**
     * Give access only if user has the right permissions.
     */
    public function __construct()
    {
        $this->middleware('permission:handle-users-roles');
    }

    /**
     * Retrieve fields for admin.
     *
     * @param  boolean $withAll
     * @return array
     */
    public function getFields(bool $withAll=true)
    {
        $fields = [
            'id' => 'display',
            'user' => User::get(['id', 'firstname', 'lastname']),
            'role' => Role::where('owned_by_type', User::class)->get(['id', 'name']),
        ];

        if ($withAll) {
            $fields['validated_by'] = User::get(['id', 'firstname', 'lastname']);
            $fields['semester'] = Semester::get(['id', 'name']);
        }

        return array_merge(
            $fields,
            [
                'created_at' => 'date',
                'updated_at' => 'date',
            ]
        );
    }

    /**
     * Fields to display labels definition.
     *
     * @param  boolean $withAll Default:true.
     * @return array
     */
    public function getLabels(bool $withAll=true)
    {

        $labels = [
            'role' => 'Rôle',
            'user' => 'Utilisateur',
        ];

        if ($withAll) {
            $labels['validated_by'] = "Validé par";
            $labels['semester'] = 'Semestre';
        }

        return $labels;
    }

    /**
     * Global display interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $grid = new GridGenerator($this->model);
        $userRoles = Role::where('owned_by_type', User::class)->get();
        $grid::$simplePrint = true;

        $grid->addFields($this->getFields(), $this->getLabels());

        $grid->tools(function ($tools) {
            $tools->disableBatchActions();
        });

        // $grid->disableCreation();
        $grid->disableExport();

        $grid->actions(function (Actions $actions) use ($userRoles) {
            $actions->disableView();
            $actions->disableDelete();
            $actions->disableEdit();

            $row = $actions->row;

            $generateAction = function ($view) use ($actions, $row, $userRoles) {
                $actions->append(view($view, [
                    'data' => $row,
                    'roles' => $userRoles,
                    'ids' => [$row->user_id, $row->semester_id],
                ])->__toString());
            };

            $generateAction('admin.users.role.edit');
            $generateAction('admin.users.role.delete');
        });

        $grid->get()->model()
        ->whereNotNull('role_id')
        ->orderBy('created_at', 'DESC');

        return $content
            ->header('Gestion des rôles utilisateurs')
            ->description('Permet d\'ajouter, modifier, supprimer, un rôle utilisateur')
            ->body($grid->get());
    }

    /**
     * Creates a new instance.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        $form = new FormGenerator($this->model);

        $form->addFields($this->getFields(false));

        $form->disableViewCheck();
        $form->disableEditingCheck();

        return $content
            ->header('Gestion des rôles utilisateurs')
            ->description('Création d\'un rôle utilisateur')
            ->body($form->get());
    }

    /**
     * Creates a user role.
     *
     * @param  Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $user = User::find($request->input('user_id'));

        if ($user) {
            $user->assignRoles($request->input('role_id'), [
                'validated_by_id' => \Auth::guard('admin')->id(),
            ], true);
        }
    }

    /**
     * Modifies a user role.
     *
     * @param  Request $request
     * @param  string  $member_id
     * @param  string  $semester_id
     * @return mixed
     */
    public function update(Request $request, string $member_id, string $semester_id)
    {
        if ($request->input('role_id')) {
            $member = UserRole::where('user_id', $member_id)
                ->where('semester_id', $semester_id)
                ->update(['role_id' => $request->input('role_id'), 'validated_by_id' => \Auth::guard('admin')->id()]);
        }

        return back();
    }

    /**
     * Removes a user role.
     *
     * @param  Request $request
     * @param  string  $member_id
     * @param  string  $semester_id
     * @return mixed
     */
    public function delete(Request $request, string $member_id, string $semester_id)
    {
        $member = UserRole::where('user_id', $member_id)
        ->where('semester_id', $semester_id)
        ->delete();

        return back();
    }
}

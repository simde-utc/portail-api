<?php
/**
 * Manage User Permissions.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2019, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Management;

use App\Admin\Controllers\Controller;
use App\Pivots\UserPermission;
use App\Models\{
	User, Semester, Permission
};
use Encore\Admin\Layout\Content;
use App\Admin\GridGenerator;
use Encore\Admin\Grid\Displayers\Actions;
use Illuminate\Http\Request;
use App\Notifications\Admin\MemberAccessValidation;
use Encore\Admin\Form;
use App\Admin\FormGenerator;

class UserPermissionController extends Controller
{
    protected $model = UserPermission::class;

    /**
     * Give access only if user has the right permissions.
     */
    public function __construct()
    {
        $this->middleware('permission:handle-users-permissions');
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
            'permission' => Permission::where('owned_by_type', User::class)->get(['id', 'name']),
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
        $userPermissions = Permission::where('owned_by_type', User::class)->get();
        $grid::$simplePrint = true;

        $grid->addFields($this->getFields(), $this->getLabels());

        $grid->tools(function ($tools) {
            $tools->disableBatchActions();
        });

        // $grid->disableCreation();
        $grid->disableExport();

        $grid->actions(function (Actions $actions) use ($userPermissions) {
            $actions->disableView();
            $actions->disableDelete();
            $actions->disableEdit();

            $row = $actions->row;

            $generateAction = function ($view) use ($actions, $row, $userPermissions) {
                $actions->append(view($view, [
                    'data' => $row,
                    'permissions' => $userPermissions,
                    'ids' => [$row->user_id, $row->semester_id],
                ])->__toString());
            };

            $generateAction('admin.users.permission.edit');
            $generateAction('admin.users.permission.delete');
        });

        $grid->get()->model()
        ->whereNotNull('permission_id')
        ->orderBy('created_at', 'DESC');

        return $content
            ->header('Gestion des permissions utilisateurs')
            ->description('Permet d\'ajouter, modifier, supprimer, une permission utilisateur')
            ->body($grid->get());
    }

    /**
     * Create a new instance.
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
            ->header('Gestion des permissions utilisateurs')
            ->description('Création d\'une permission utilisateur')
            ->body($form->get());
    }

    /**
     * Create a user permission.
     *
     * @param  Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $user = User::find($request->input('user_id'));

        if ($user) {
            $user->assignPermissions($request->input('permission_id'), [
                'validated_by_id' => \Auth::guard('admin')->id(),
            ], true);
        }
    }

    /**
     * Modify a user permission.
     *
     * @param  Request $request
     * @param  string  $member_id
     * @param  string  $semester_id
     * @return mixed
     */
    public function update(Request $request, string $member_id, string $semester_id)
    {
        if ($request->input('permission_id')) {
            $member = UserPermission::where('user_id', $member_id)
                ->where('semester_id', $semester_id)
                ->update([
                    'permission_id' => $request->input('permission_id'),
                    'validated_by_id' => \Auth::guard('admin')->id(),
                ]);
        }

        return back();
    }

    /**
     * Remove a user permission.
     *
     * @param  Request $request
     * @param  string  $member_id
     * @param  string  $semester_id
     * @return mixed
     */
    public function delete(Request $request, string $member_id, string $semester_id)
    {
        $member = UserPermission::where('user_id', $member_id)
        ->where('semester_id', $semester_id)
        ->delete();

        return back();
    }
}

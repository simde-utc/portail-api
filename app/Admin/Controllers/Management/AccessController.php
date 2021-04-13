<?php
/**
 * Manage accesses.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Noé Amiot <noe.amiot@etu.utc.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Management;

use App\Admin\Controllers\Controller;
use App\Models\{
    AssoAccess, Asso, User, Access, Semester, Role
};
use Encore\Admin\Layout\Content;
use App\Admin\GridGenerator;
use Encore\Admin\Grid\Displayers\Actions;
use Illuminate\Http\Request;
use App\Notifications\Admin\MemberAccessValidation;

class AccessController extends Controller
{
    protected $model = AssoAccess::class;

    /**
     * Give access only if user has the right permissions.
     */
    public function __construct()
    {
        $this->middleware('permission:handle-access');
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
        $grid::$simplePrint = true;

        // Add all filters.
        $grid->filter(function($filter) {
            $filter->where(function ($query) {
                $query->whereHas('asso', function ($query) {
                    $query->where('id', '=', $this->input);
                });
            }, 'Asso')->select(Asso::get(['id', 'shortname'])->pluck('shortname', 'id'));

            // Get all poles (all assos with children).
            $poles = Asso::whereIn('id', function($query) {
                return $query->select('parent_id')->from('assos');
            })->pluck('name', 'id');

            $filter->where(function ($query) {
                $query->whereHas('asso', function ($query) {
                    $query->where('parent_id', '=', $this->input);
                });
            }, 'Pôle')->select($poles);

            $filter->where(function ($query) {
                $query->whereHas('member', function ($query) {
                    $query->whereRaw('lower(lastname) like ?', [strtolower("%{$this->input}%")]);
                });
            }, 'Nom du membre');
            $filter->where(function ($query) {
                $query->whereHas('member', function ($query) {
                    $query->whereRaw('lower(firstname) like ?', [strtolower("%{$this->input}%")]);
                });
            }, 'Prénom du membre');

            $filter->where(function ($query) {
                $query->whereHas('semester', function ($query) {
                    $query->where('id', '=', $this->input);
                });
            }, 'Semestre')->select(Semester::get(['id', 'name'])->pluck('name', 'id'));
        });

        // Add all columns.
        $grid->column('asso.shortname', 'Asso');

        $grid->column('Pôle')->display(function () {
            $asso = Asso::find($this->asso_id);

            if ($asso) {
                $parent = Asso::find($asso->parent_id);
                if ($parent) {
                    return $parent->name;
                }
            }
        });

        $grid->column('member.lastname', 'Membre')->display(function () {
            return $this->member->lastname." ".$this->member->firstname;
        });

        $grid->column('Rôles')->display(function () {
            $asso = Asso::find($this->asso_id);

            if ($asso) {
                $user = $asso->currentMembers()->wherePivot('user_id', $this->member['id'])->first();
                if ($user) {
                    $this->role = Role::find($user->pivot->role_id, $this->asso);
                    return GridGenerator::modelToTable($this->role->toArray());
                }
            }
        });
        $grid->column('access.name', 'Accès')->display(function () {
            return $this->access->name.' ('.$this->access->utc_access.')';
        });
        $grid->column('semester.name', 'Semestre');

        $grid->addFields([
            'description' => 'textarea',
            'comment' => 'text'
        ], [
            "comment" => "Commentaire",
        ], false);

        $grid->column('validated_by.lastname', 'Validé par')->display(function () {
            if ($this->validated_by) {
                return $this->validated_by->lastname." ".$this->validated_by->firstname;
            }
        });

        $grid->addFields([
            'validated' => 'switch',
            'validated_at' => 'datetime',
            'created_at' => 'datetime',
        ], [
            "validated" => "Validé",
            "validated_at" => "Validé le",
        ]);

        $grid->tools(function ($tools) {
            $tools->disableBatchActions();
        });

        $grid->disableCreation();
        $grid->disableExport();

        $grid->actions(function (Actions $actions) {
            $actions->disableView();
            $actions->disableDelete();
            $actions->disableEdit();

            $row = $actions->row;

            $generateAction = function ($view) use ($actions, $row) {
                $actions->append(view($view, ['access' => $row])->__toString());
            };

            if ($row->confirmed_by) {
                if ($row->validated_by) {
                    if ($row->validated) {
                        $generateAction('admin.access.validated');
                    } else {
                        $generateAction('admin.access.refused');
                    }
                } else {
                    $generateAction('admin.access.validate');
                }
            } else {
                $generateAction('admin.access.unconfirmed');
            }
        });

        $grid->get()->model()->orderBy('created_at', 'DESC');

        return $content
            ->header('Gestion des accès')
            ->description('Permet de savoir ce qui est validé')
            ->body($grid->get());
    }

    /**
     * Save access request change.
     *
     * @param  Request $request
     * @param  string  $accessId
     * @return mixed
     */
    public function store(Request $request, string $accessId)
    {
        if ($request->filled('validate') && $request->filled('comment')) {
            $access = AssoAccess::find($accessId);

            if ($access && $access->confirmed_by && is_null($access->validated_by)) {
                $admin = \Auth::guard('admin')->user();

                $access->comment = $request->input('comment');
                $access->validated_by_id = $admin->id;
                $access->validated_at = now();
                $access->validated = $request->input('validate');

                $notif = new MemberAccessValidation($access, $admin);
                $access->save();

                $access->member->notify($notif);
            }
        }

        return back();
    }
}

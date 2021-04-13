<?php
/**
 * Display Users as admin.
 *
 * @author Samy Nastuzzi <samy@nastuzzi.fr>
 * @author Corentin Mercier <corentin@cmercier.fr>
 *
 * @copyright Copyright (c) 2018, SiMDE-UTC
 * @license GNU GPL-3.0
 */

namespace App\Admin\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Traits\Controller\v1\HasUsers;
use Encore\Admin\Layout\{
    Content, Column, Row
};
use Encore\Admin\{
    Grid, Show
};
use Illuminate\Http\Request;
use App\Models\{
    User,
};
use App\Notifications\Admin\{
    UserImpersonation
};
use App\Notifications\User\UserContributionBde;
use App\Models\Semester;
use App\Admin\{
    GridGenerator, ShowGenerator
};

class UserController extends Controller
{
    use HasUsers;

    protected $fields = [
        'id' => 'display',
        'email' => 'email',
        'firstname' => 'text',
        'lastname' => 'text',
        'last_login_at' => 'display',
        'created_at' => 'date',
        'updated_at' => 'date'
    ];

    /**
     * Fields to display labels definition.
     *
     * @return array
     */
    protected function getLabels(): array
    {
        return [
            'firstname' => 'Prénom',
            'lastname' => 'Nom',
            'last_login_at' => 'Dernière connexion le',
        ];
    }

    /**
     * Give access only if user has the right permission.
     */
    public function __construct()
    {
        $this->middleware('permission:user', ['except' => ['contributeBde']]);
        $this->middleware('permission:user-contributeBde', ['only' => ['contributeBde']]);
    }

    /**
     * Return search form.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        $grid = new GridGenerator(User::class);

        $grid->addFields($this->fields, $this->getLabels());

        $grid->types()->display(function () {
            $badges = '';

            foreach ($this->getTypeDescriptions() as $type => $description) {
                if ($this->isType($type)) {
                    $badges .= '<span class="badge">'.$description.'</span>';
                }
            }

            return $badges;
        });

        $grid->model()->orderBy('created_at');

        $grid->tools(function ($tools) {
            $tools->disableBatchActions();
        });

        $grid->disableCreation();

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });

        return $content
            ->header('Liste des utilisateurs')
            ->description('Affiche les derniers utilisateurs par défaut')
            ->body($grid->get());
    }

    /**
     * Show a User.
     *
     * @param  Content $content
     * @param  string  $user_id
     * @return mixed
     */
    public function show(Content $content, string $user_id)
    {
        $user = User::findOrFail($user_id);
        $image = new Show($user);
        $image->image(' ')->image();
        $image->panel()
            ->title('Image')
            ->tools(function ($tools) {
                $tools->disableEdit();
                $tools->disableList();
                $tools->disableDelete();
            });

        $details = new ShowGenerator($user);

        $details->addFields($this->fields);

        $details->types()->unescape()->as(function () {
            $badges = '';

            foreach ($this->getTypeDescriptions() as $type => $description) {
                if ($this->isType($type)) {
                    $badges .= '<span class="badge">'.$description.'</span>';
                }
            }

            return $badges;
        });

        $generateLeft = function (Column $column) use ($user, $image) {
            return $this->generateLeft($column, $user, $image);
        };
        $generateRight = function (Column $column) use ($user, $details) {
            $this->generateRight($column, $user, $details->get());
        };

        return $content
            ->header($user->name)
            ->description('Détail de l\'utilisateur')
            ->row(function (Row $row) use ($generateLeft, $generateRight) {
                $row->column(3, $generateLeft);
                $row->column(9, $generateRight);
            });
    }

    /**
     * Generate the left side of the display a user.
     *
     * @param  Column $column
     * @param  User   $user
     * @param  mixed  $image
     * @return mixed
     */
    protected function generateLeft(Column $column, User $user, $image)
    {
        $column->row($image);

        if (\Auth::guard('admin')->user()->can('user-contributeBde')) {
            $ginger = \Ginger::userByEmail($user->email);
            $contributeBde = new Show($user);
            $contributeBde->login(' ')->unescape()->as(function () use ($ginger) {
                return 'Login: '.GridGenerator::adminValue($ginger->getLogin());
            });
            $contributeBde->contributerType(' ')->unescape()->as(function () use ($ginger) {
                return 'Type: '.GridGenerator::adminValue($ginger->getType());
            });
            $contributeBde->major(' ')->unescape()->as(function () use ($ginger) {
                return 'Majeur: '.GridGenerator::adminValue($ginger->isAdult());
            });
            $contributeBde->contributor(' ')->unescape()->as(function () use ($ginger) {
                return 'Cotisant: '.GridGenerator::adminValue($ginger->isContributor());
            });
            $contributeBde->divider();

            $contributions = [];

            foreach (array_reverse($ginger->getContributions()) as $contribution) {
                $semesters = Semester::whereDate('begin_at', '<', $contribution->end_at)
                    ->whereDate('end_at', '>', $contribution->begin_at)
                    ->orderBy('begin_at')->get(['name']);

                $name = implode($semesters->pluck('name')->toArray(), '-');

                if (!isset($contributions[$name])) {
                    $contributions[$name] = [];
                }

                $contributions[$name][] = [
                    'début' => $contribution->begin_at,
                    'fin' => $contribution->end_at,
                    'montant' => $contribution->money.'€',
                ];
            }

            foreach ($contributions as $key => $value) {
                $contributeBde->$key($key)->unescape()->as(function () use ($value) {
                    return GridGenerator::adminValue($value);
                });
            }

            $contributeBde->panel()
                ->title('Cotisation BDE')
                ->tools(function ($tools) use ($user, $ginger) {
                    $tools->disableEdit();
                    $tools->disableList();
                    $tools->disableDelete();

                    if ($ginger->isContributor()) {
                        $tools->prepend(view('admin.users.contributeBDE.delete', ['user' => $user]));
                    } else {
                        $tools->prepend(view('admin.users.contributeBDE.create', ['user' => $user]));
                    }
                });

            $column->row($contributeBde);
        }

        return $column;
    }

    /**
     * Generate right side of the display a user.
     *
     * @param  Column $column
     * @param  User   $user
     * @param  mixed  $details
     * @return mixed
     */
    protected function generateRight(Column $column, User $user, $details)
    {
        if (!\Auth::guard('admin')->user()->can('user')) {
            $column->row($details);
        } else {
            if (\Auth::guard('admin')->user()->can('user-impersonate')) {
                $details->panel()
                    ->tools(function ($tools) use ($user) {
                        $tools->prepend(view('admin.users.impersonate', ['user' => $user]));
                    });
            }

            $column->row($details);

            $info = new Show($user);
            $info->details()->unescape()->as(function () {
                return GridGenerator::arrayToTable($this->details()->allToArray());
            });
            $info->preferences()->unescape()->as(function () {
                return GridGenerator::arrayToTable($this->preferences()->allToArray());
            });

            $info->panel()
                ->title('Information')
                ->tools(function ($tools) {
                    $tools->disableEdit();
                    $tools->disableList();
                    $tools->disableDelete();
                });

            $column->row($info);
        }

        return $column;
    }

    /**
     * Allow to become someone else (In this system of course :p )
     *
     * @param Request $request
     * @param string  $user_id
     * @return mixed
     */
    public function impersonate(Request $request, string $user_id)
    {
        if (!$request->filled('description') || !\Auth::guard('admin')->user()->can('user-impersonate')) {
            return redirect()->action(
                '\App\Admin\Controllers\Resource\UserController@show', ['user_id' => $user_id]
            );
        }

        try {
            $user = $this->getUser($request, $user_id, true);
        } catch (\Exception $e) {
            return redirect()->action(
                '\App\Admin\Controllers\Resource\UserController@index'
            );
        }

        $admin = \Auth::guard('admin')->user();

        if ($admin->id === $user->id) {
            return redirect()->action(
                '\App\Admin\Controllers\Resource\UserController@show', ['user_id' => $user_id]
            );
        }

        // Notification creation to confirm changement.
        $userNotification = new UserImpersonation($admin, $request->input('description'), (bool) $request->input('admin'));

        if (config('app.debug') && $request->input('admin')) {
            \Auth::guard('admin')->login(\App\Admin\Models\Admin::find($user->id));
        }

        \Auth::guard('web')->login($user);

        $user->notify($userNotification);

        return redirect('/');
    }

    /**
     * Allow to make someone contribute.
     *
     * @param Request $request
     * @param string  $user_id
     * @return mixed
     */
    public function contributeBde(Request $request, string $user_id)
    {
        if ((!$request->filled('money') && !$request->filled('custom'))
            || !\Auth::guard('admin')->user()->can('user-contributeBde')) {
            return redirect()->action(
                '\App\Admin\Controllers\Resource\UserController@show', ['user_id' => $user_id]
            );
        }

        try {
            $user = $this->getUser($request, $user_id, true);
            $ginger = \Ginger::userByEmail($user->email);
        } catch (\Exception $e) {
            $ginger = \Ginger::user($user_id);

            if (!$ginger->exists()) {
                return redirect()->action(
                '\App\Admin\Controllers\Resource\UserController@index'
                );
            }

            $user = new User([
                'email' => $ginger->getEmail(),
                'lastname' => $ginger->getLastname(),
                'firstname' => $ginger->getFirstname(),
            ]);
        }

        if (!$ginger->exists() || ($user->id && $user->isContributorBde())) {
            return redirect()->action(
                '\App\Admin\Controllers\Resource\UserController@show', ['user_id' => $user_id]
            );
        }

        $semesters = Semester::getThisYear();
        $money = $request->input('money') ?: $request->input('custom');

        // Notification creation for contributing confirmation.
        $userNotification = new UserContributionBde($semesters, $money, \Auth::guard('admin')->user());
        $ginger->addContribution(now()->format('Y-m-d'), end($semesters)->end_at, $money);

        if ($user->id) {
            $user->notify($userNotification);
        }

        return back();
    }
}

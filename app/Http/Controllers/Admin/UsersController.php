<?php namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Http\Requests\UserEditRequest;
use App\Http\Requests\UserRequest;
use App\Repositories\GainRepository;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Input;
use Laracasts\Flash\Flash;
use Maatwebsite\Excel\Facades\Excel;

use App\Role;
use App\Repositories\UserRepository;
use User;
use Auth;


class UsersController extends Controller {


    protected $userRepository;
    /**
     * @var GainRepository
     */
    private $gainRepository;


    /**
     * @param UserRepository $userRepository
     * @param GainRepository $gainRepository
     * @internal param UserForm $userForm
     * @internal param UserEditForm $userEditForm
     * @internal param UserEditForm $
     */

    function __construct(UserRepository $userRepository, GainRepository $gainRepository)
    {

        $this->userRepository = $userRepository;
        $this->gainRepository = $gainRepository;


        View::share('roles', Role::lists('name', 'id')->all());
        $this->middleware('authByRoleAdmins');
    }

    /**
     * Display a listing of the resource.
     * GET /users
     *
     * @return Response
     */
    public function index()
    {
        $search = Request::all();
        if (! count($search) > 0)
        {
            $search['q'] = "";
        }
        $search['active'] = (isset($search['active'])) ? $search['active'] : '';

        $users = $this->userRepository->findAll($search);

        return View::make('admin.users.index')->with([
            'users'          => $users,
            'search'         => $search['q'],
            'selectedStatus' => $search['active']

        ]);
    }

    /**
     * Show the form for creating a new resource.
     * GET /users/create
     *
     * @return Response
     */
    public function create()
    {
        return View::make('admin.users.create');
    }

    /**
     * Store a newly created resource in storage.
     * POST /users
     *
     * @param UserRequest $request
     * @return Response
     */
    public function store(UserRequest $request)
    {
        $input = $request->only('username', 'email', 'password', 'password_confirmation', 'role', 'parent_id');

        $this->userRepository->store($input);

        Flash::message('User created');

        return Redirect::route('store.admin.users.index');
    }


    /**
     * Show the form for editing the specified resource.
     * GET /users/{id}/edit
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        $user = $this->userRepository->findById($id);
        $hits = $this->userRepository->getHits($user);
        $gains = $this->gainRepository->getGainsById($id);
        return View::make('admin.users.edit')->with(compact('user','hits','gains'));
    }

    /**
     * Update the specified resource in storage.
     * PUT /users/{id}
     *
     * @param UserEditRequest $request
     * @param $id
     * @return Response
     * @internal param int $id
     */
    public function update(UserEditRequest $request, $id)
    {
        $input = $request->only('username', 'email', 'password', 'password_confirmation', 'role', 'parent_id');

        $this->userRepository->update($id, $input);

        Flash::message('User updated');

        return Redirect::route('store.admin.users.index');
    }

    /**
     * published a Product.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function active($id)
    {
        $this->userRepository->update_active($id, 1);

        return Redirect::route('store.admin.users.index');
    }

    /**
     * Unpublished a Product.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function inactive($id)
    {
        $this->userRepository->update_active($id, 0);

        return Redirect::route('store.admin.users.index');
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /users/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $this->userRepository->destroy($id);

        Flash::message('User Deleted');


        return Redirect::route('store.admin.users.index');
    }

    /**
     * Function for exported gains for user list
     * @return mixed
     */
    public function exportGainsList()
    {
        $month = Request::get('month');
        $year = Request::get('year');

        Excel::create('Ganancias', function ($excel) use ($month, $year)
        {

            $excel->sheet('Ganancias', function ($sheet) use ($month, $year)
            {
                $sheet->fromArray($this->userRepository->reportPaymentsByMonth($month, $year), null, 'A1', true);

            });


        })->export('xls');



    }

    /**
     * Function for exported payments list for date
     * @return mixed
     */
    public function exportPaymentsList()
    {
        $payment_date = Request::get('payment_date_submit');

        Excel::create('Pagos', function ($excel) use ($payment_date)
        {

            $excel->sheet('Pagos diarios', function ($sheet) use ($payment_date)
            {
                $sheet->fromArray($this->userRepository->reportPaymentsByDay($payment_date), null, 'A1', true);

            });


        })->export('xls');

    }

    /**
     * @return mixed
     */
    public function list_patners()
    {

        return $this->userRepository->list_patners(Request::get('exc_id'), Request::get('key'));
    }

    public function callGenerateCharge($user_id)
    {
        $user = $this->userRepository->findById($user_id);
        if($user->annual_charge == 1)
        {
            Flash::warning('Este usuario ya tiene un cobro anual' );
            return redirect()->route('store.admin.users.index');
        }


        $this->userRepository->generateAnnualCharge($user);
        $user->annual_charge = 1;
        $user->save();

        Flash::message('Se genero el corte anual al usuario correctamente' );


        return redirect()->route('store.admin.users.index');
    }


}
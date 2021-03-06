<?php namespace App\Http\Controllers\Admin;

use App\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Shop;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Laracasts\Flash\Flash;
use App\Repositories\CategoryRepository;
use Baum\MoveNotPossibleException as moveExp;
use View;

class CategoriesController extends Controller {


    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var CategoryForm
     */
    private $categoryForm;

    function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;


        //$this->middleware('authByRoleAdmins');
    }


    /**
     * Display a listing of the resource.
     * GET /categories
     *
     * @return Response
     */
    public function index()
    {
        $search = Request::all();
        $search['q'] = (isset($search['q'])) ? trim($search['q']) : '';
        $search['published'] = (isset($search['published'])) ? $search['published'] : '';
        $categories = $this->categoryRepository->getAll($search);

        return View::make('admin.categories.index')->with([
            'categories'     => $categories,
            'search'         => $search['q'],
            'selectedStatus' => $search['published']

        ]);
    }

    /**
     * Show the form for creating a new resource.
     * GET /categories/create
     *
     * @return Response
     */
    public function create()
    {
        $options = $this->categoryRepository->getParents();

        $shops = $this->getShopsToSelect();

        return View::make('admin.categories.create')->withOptions($options)->withShops($shops);
    }

    /**
     * Store a newly created resource in storage.
     * POST /categories
     *
     * @param CategoryRequest $request
     * @return Response
     */
    public function store(CategoryRequest $request)
    {
        $input = $request->all();

        $this->categoryRepository->store($input);

        Flash::message('Category created');

        return Redirect::route('store.admin.categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     * GET /categories/{id}/edit
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        $category = $this->categoryRepository->findById($id);
        $options = $this->categoryRepository->getParents($category->shop_id);

        $shops = $this->getShopsToSelect();

        return View::make('admin.categories.edit')->withCategory($category)->withOptions($options)->withShops($shops);
    }

    /**
     * Update the specified resource in storage.
     * PUT /categories/{id}
     *
     * @param CategoryRequest $request
     * @param  int $id
     * @return Response
     */
    public function update(CategoryRequest $request, $id)
    {
        $input = $request->all();

        $this->categoryRepository->update($id, $input);

        Flash::message('Category updated');

        return Redirect::route('store.admin.categories.index');
    }


    /**
     * Remove the specified resource from storage.
     * DELETE /categories/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $this->categoryRepository->destroy($id);

        Flash::message('Category Deleted');

        return Redirect::route('store.admin.categories.index');
    }


    /**
     * Featured.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function feat($id)
    {
        $this->categoryRepository->update_feat($id, 1);

        return Redirect::route('store.admin.categories.index');
    }

    /**
     * un-featured.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function unfeat($id)
    {
        $this->categoryRepository->update_feat($id, 0);

        return Redirect::route('store.admin.categories.index');
    }


    /**
     * published.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function pub($id)
    {
        $this->categoryRepository->update_state($id, 1);

        return Redirect::route('store.admin.categories.index');
    }

    /**
     * Unpublished.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function unpub($id)
    {
        $this->categoryRepository->update_state($id, 0);

        return Redirect::route('store.admin.categories.index');
    }


    /**
     * Move the specified page up.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function up($id)
    {
        return $this->move($id, 'before');
    }

    /**
     * Move the specified page down.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function down($id)
    {
        return $this->move($id, 'after');
    }

    /**
     * Move the page.
     *
     * @param  int $id
     * @param  'before'|'after' $dir
     *
     * @return Response
     */
    protected function move($id, $dir)
    {
        $category = $this->categoryRepository->findById($id);
        $response = Redirect::route('store.admin.categories.index');

        if (! $category->isRoot())
        {
            try
            {
                ($dir === 'before') ? $category->moveLeft() : $category->moveRight();
                Flash::message('Category moved');

                return $response;

            } catch (moveExp $ex)
            {
                Flash::warning('The category did not move');

                return $response;
            }


        }
        Flash::warning('The category did not move');

        return $response;
    }

    /**
     * @return mixed
     */
    public function list_categories()
    {

        return $this->categoryRepository->getParents(Request::get('shop_id'),true);
    }

    /**
     * @return mixed
     */
    private function getShopsToSelect()
    {
        if (auth()->user()->hasrole('store')) {
            $shops = Shop::where(function ($q) {
                $q->where('published', '=', 1)
                    ->where('responsable_id', '=', auth()->user()->id);
            })->lists('name', 'id')->all();

        } else
            $shops = Shop::where('published', '=', 1)->lists('name', 'id')->all();

        return $shops;
    }

}
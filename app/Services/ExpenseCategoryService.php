<?php namespace App\Services;

use Utils;
use Auth;
use App\Ninja\Repositories\ExpenseCategoryRepository;
use App\Ninja\Datatables\ExpenseCategoryDatatable;

/**
 * Class ExpenseCategoryService
 */
class ExpenseCategoryService extends BaseService
{
    /**
     * @var ExpenseCategoryRepository
     */
    protected $categoryRepo;

    /**
     * @var DatatableService
     */
    protected $datatableService;

    /**
     * CreditService constructor.
     *
     * @param ExpenseCategoryRepository $creditRepo
     * @param DatatableService $datatableService
     */
    public function __construct(ExpenseCategoryRepository $categoryRepo, DatatableService $datatableService)
    {
        $this->categoryRepo = $categoryRepo;
        $this->datatableService = $datatableService;
    }

    /**
     * @return CreditRepository
     */
    protected function getRepo()
    {
        return $this->categoryRepo;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function save($data)
    {
        return $this->categoryRepo->save($data);
    }

    /**
     * @param $relationPublicId
     * @param $search
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatatable($search)
    {
        // we don't support bulk edit and hide the relation on the individual relation page
        $datatable = new ExpenseCategoryDatatable();

        $query = $this->categoryRepo->find($search);

        return $this->datatableService->createDatatable($datatable, $query);
    }
}

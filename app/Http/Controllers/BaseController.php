<?php

namespace App\Http\Controllers;

use App\Models\BaseModel;
use App\Traits\SetReponses;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Psy\Command\HistoryCommand;

use function PHPUnit\Framework\returnSelf;

abstract class BaseController extends Controller
{
    use SetReponses;
    protected $model;
    protected $relations = [];
    
    protected $filterableFields = [];

    public function get(Request $request){
        $query = $this->model::query();
        if (!empty($this->relations)) {
            $query->with($this->relations);
        }
        $search = $request->only(['find']);

        if (!empty($search)) {
            $query->search($search);
        }

        $filters = $request->only($this->filterableFields);
        if(!empty($filters)){
            $query->filter($filters);
        }

        $sortField = $request->input('sort_by', 'created_at');  
        $sortOrder = $request->input('sort_order', 'asc'); 
        $query->sort($sortField, $sortOrder);
        
        return $request->input('page') ?
            self::success($query->paginate(
                $request->input('per_page') ?? 10
            ), true):
            self::success($query->get(), false);
    }

    public function store(Request $request){

        $validationRules = $this->model::validatedFields();
        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return self::error($validator->errors()->getMessages(), 422);
        }

        
        $data = $request->all();
        try {
            $record = $this->model::create($data);
            return self::crudSuccess($record, 'inserted');
        } catch (Exception $e) {
            return self::error($e->getMessage(), 500);
        }

    }

    public function id($id){
        try {
            $data = $this->model::where('id', $id)
                ->with($this->relations)
                ->get();
            return self::success($data, false);
        } catch (ModelNotFoundException $e) {
            return self::error('Data not found', 404);
        } catch (Exception $e){
            return self::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $item = $this->model::findOrFail($id);
            $validationRules = $this->model::validatedFields($id);
            
            $validator = Validator::make($request->all(), $validationRules);
            
            if ($validator->fails()) {
                return self::error($validator->errors()->getMessages(), 422);
            }
            
            $data = $request->all();
            $item->update($data);
            return self::crudSuccess($item, 'updated');
        } catch (ModelNotFoundException $e) {
            return self::error('Data not found!', 404);
        } catch(Exception $e){
            return self::error($e->getMessage(), 500);
        }
    }

    public function destroy($id){
        try {
            $data= $this->model::findOrFail($id);
            $data->delete();
            return self::crudSuccess([], 'deleted');
        } catch (ModelNotFoundException $e) {
            return self::error('Data not found', 404);
        } catch (Exception $e){
            return self::error($e->getMessage(), 500);
        }
    }

    public function activate(Request $request, $id){
        try {
            $data= $this->model::findOrFail($id);
            $data->update([
                'status' => $request->status
            ]);
            return self::crudSuccess([], 'activated');
        } catch (ModelNotFoundException $e) {
            return self::error('Data not found', 404);
        } catch (Exception $e){
            return self::error($e->getMessage(), 500);
        }
    }

    public function deactivate(Request $request, $id){
        try {
            $data= $this->model::findOrFail($id);
            $data->update([
                'status' => $request->status
            ]);
            return self::crudSuccess([], 'deactivated');
        } catch (ModelNotFoundException $e) {
            return self::error('Data not found', 404);
        } catch (Exception $e){
            return self::error($e->getMessage(), 500);
        }
    }
}

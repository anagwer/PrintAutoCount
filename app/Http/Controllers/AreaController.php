<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Traits\SetReponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaController extends BaseController
{
    use SetReponses;

    protected $model = Area::class;
    protected $filterableFields = ['AreaCode', 'Description'];

    private function getTargetDbFromToken(Request $request): string
    {
        $tokenAbilities = $request->user()?->currentAccessToken()?->abilities ?? [];

        foreach ($tokenAbilities as $ability) {
            if (str_starts_with($ability, 'target_db:')) {
                return str_replace('target_db:', '', $ability);
            }
        }

        return 'mysql';
    }

    public function get(Request $request)
    {
        $targetDb = $this->getTargetDbFromToken($request);
        $allowedConnections = ['mysql', 'sqlsrv'];

        if (!in_array($targetDb, $allowedConnections)) {
            return self::error('Invalid database target', 403);
        }

        try {
            $query = DB::connection($targetDb)->table('AREA');

            // Search manual
            if ($request->filled('find')) {
                $find = $request->input('find');
                $query->where(function ($q) use ($find) {
                    $q->where('AreaCode', 'like', "%$find%")
                    ->orWhere('Description', 'like', "%$find%");
                });
            }

            $perPage = $request->input('per_page') ?? 10;

            return $request->has('page')
                ? self::success($query->paginate($perPage), true)
                : self::success($query->get(), false);
        } catch (\Exception $e) {
            return self::error($e->getMessage(), 500);
        }
    }

}

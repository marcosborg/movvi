<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\VehicleExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VehicleExpenseController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Gate::allows('vehicle_expense_access'), 403);
        $input = $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'after_id' => ['sometimes', 'integer', 'min:0'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'updated_since' => ['sometimes', 'date_format:Y-m-d H:i:s'],
        ]);
        $company = Company::findOrFail($input['company_id']);
        $user = $request->user();
        abort_unless($user->hasRole('Admin') || (int) $company->user_id === (int) $user->id
            || $user->driver()->where('company_id', $company->id)->exists(), 403);

        // Include deleted records so consumers can reconcile prior exports.
        $query = VehicleExpense::withTrashed()
            ->with(['vehicle_item' => fn ($q) => $q->withTrashed()])
            ->whereHas('vehicle_item', fn ($q) => $q->withTrashed()->where('company_id', $company->id))
            ->where('id', '>', $input['after_id'] ?? 0)
            ->orderBy('id');
        if (isset($input['updated_since'])) {
            $query->where(function ($q) use ($input) {
                $q->where('updated_at', '>=', $input['updated_since'])
                    ->orWhere('deleted_at', '>=', $input['updated_since']);
            });
        }
        $limit = $input['per_page'] ?? 100;
        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return response()->json([
            'data' => $rows->map(fn ($expense) => [
                'id' => $expense->id,
                'source_id' => 'movvi:vehicle-expense:' . $expense->id,
                'company_id' => $company->id,
                'vehicle_id' => $expense->vehicle_item_id,
                'license_plate' => $expense->vehicle_item->license_plate,
                'expense_type' => $expense->expense_type_label,
                'date' => $expense->getRawOriginal('date'),
                'description' => html_entity_decode(strip_tags((string) $expense->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'value' => (float) $expense->value,
                'vat' => (float) $expense->vat,
                'currency' => 'EUR',
                'updated_at' => $expense->getRawOriginal('updated_at'),
                'deleted_at' => $expense->getRawOriginal('deleted_at'),
            ])->values(),
            'meta' => [
                'has_more' => $hasMore,
                'next_after_id' => $hasMore ? $rows->last()->id : null,
                'timezone' => config('app.timezone'),
            ],
        ]);
    }
}

<?php

namespace App\Http\Requests;

use Gate;
use App\Services\AdminDriverImpersonationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreReceiptRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('receipt_create') || $this->originalAdminCanForceDriverReceiptSubmission();
    }

    public function rules()
    {
        return [
            'driver_id' => [
                'required',
                'integer',
                'exists:drivers,id',
            ],
            'tvde_week_id' => [
                'required',
                'integer',
                'exists:tvde_weeks,id',
                Rule::unique('receipts', 'tvde_week_id')
                    ->where(fn ($query) => $query
                        ->where('driver_id', $this->input('driver_id'))
                        ->whereNull('deleted_at')),
            ],
            'value' => [
                'required',
            ],
            'file' => [
                'required',
            ],
            'balance' => [
                'nullable',
                'numeric',
            ],
            'verified_value' => [
                'nullable',
                'numeric',
            ],
            'amount_transferred' => [
                'nullable',
                'numeric',
            ],
            'paid' => [
                'nullable',
                'boolean',
            ],
            'verified' => [
                'nullable',
                'boolean',
            ],
            'force_driver_receipt_submission' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    protected function originalAdminCanForceDriverReceiptSubmission(): bool
    {
        $impersonationService = app(AdminDriverImpersonationService::class);
        if (!$impersonationService->isImpersonating()) {
            return false;
        }

        $admin = $impersonationService->resolveOriginalAdmin($this->user());
        if (!$admin) {
            return false;
        }

        if ($admin->is_admin || $admin->hasRole('Admin') || $admin->hasRole('Administrador')) {
            return true;
        }

        return DB::table('permissions')
            ->join('permission_role', 'permissions.id', '=', 'permission_role.permission_id')
            ->join('role_user', 'permission_role.role_id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $admin->id)
            ->where('permissions.title', 'force_driver_receipt_submission')
            ->exists();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use App\Services\Contracts\ContractGenerationService;
use App\Services\Contracts\ContractTemplateRenderer;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ContractTemplateController extends Controller
{
    public function index(ContractGenerationService $generationService)
    {
        abort_if(Gate::denies('admin_contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $generationService->activeTemplate();
        $templates = ContractTemplate::latest('version')->get();

        return view('admin.contractTemplates.index', compact('templates'));
    }

    public function edit(ContractTemplate $contractTemplate, ContractTemplateRenderer $renderer)
    {
        abort_if(Gate::denies('admin_contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.contractTemplates.edit', [
            'contractTemplate' => $contractTemplate,
            'tags' => $renderer->tags(),
        ]);
    }

    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        abort_if(Gate::denies('admin_contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $newTemplate = DB::transaction(function () use ($contractTemplate, $validated) {
            ContractTemplate::where('is_active', true)->update(['is_active' => false]);

            return ContractTemplate::create([
                'name' => $validated['name'],
                'version' => ContractTemplate::withTrashed()
                    ->where('name', $validated['name'])
                    ->max('version') + 1,
                'content' => $validated['content'],
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.contract-templates.edit', $newTemplate)
            ->with('message', 'Nova versão do modelo guardada. Os contratos anteriores não foram alterados.');
    }
}

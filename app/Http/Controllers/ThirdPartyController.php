<?php

namespace App\Http\Controllers;

use App\Enums\ThirdPartyType;
use App\Http\Requests\StoreThirdPartyRequest;
use App\Models\ThirdParty;
use App\Services\Accounting\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ThirdPartyController extends Controller
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function index(): View
    {
        return view('third-parties.index', [
            'thirdParties' => ThirdParty::query()->whereBelongsTo($this->currentCompany->get())->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('third-parties.form', ['thirdParty' => new ThirdParty, 'types' => ThirdPartyType::cases()]);
    }

    public function store(StoreThirdPartyRequest $request): RedirectResponse
    {
        $this->currentCompany->get()->thirdParties()->create($request->validated());

        return redirect()->route('third-parties.index')->with('status', 'Tercero creado.');
    }

    public function show(ThirdParty $thirdParty): View
    {
        return view('third-parties.show', ['thirdParty' => $thirdParty]);
    }

    public function edit(ThirdParty $thirdParty): View
    {
        return view('third-parties.form', ['thirdParty' => $thirdParty, 'types' => ThirdPartyType::cases()]);
    }

    public function update(StoreThirdPartyRequest $request, ThirdParty $thirdParty): RedirectResponse
    {
        $thirdParty->update($request->validated());

        return redirect()->route('third-parties.index')->with('status', 'Tercero actualizado.');
    }

    public function destroy(ThirdParty $thirdParty): RedirectResponse
    {
        $thirdParty->delete();

        return redirect()->route('third-parties.index')->with('status', 'Tercero eliminado.');
    }
}

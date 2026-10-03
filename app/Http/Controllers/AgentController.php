<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentRequest;
use App\Models\Agent;
use App\Models\Site;
use App\Models\Superviseur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    private const TRIS = ['code', 'nom', 'telephone', 'taux_commission', 'statut', 'date_embauche'];

    public function index(Request $request): View
    {
        $tri = in_array($request->query('tri'), self::TRIS, true) ? $request->query('tri') : 'nom';
        $sens = $request->query('sens') === 'desc' ? 'desc' : 'asc';

        $agents = Agent::query()
            ->with('site.superviseur')
            ->recherche($request->query('q'))
            ->when(
                array_key_exists((string) $request->query('statut'), Agent::statuts()),
                fn ($q) => $q->where('statut', $request->query('statut'))
            )
            ->when($request->query('site') === 'aucun', fn ($q) => $q->whereNull('site_id'))
            ->when(ctype_digit((string) $request->query('site')), fn ($q) => $q->where('site_id', $request->query('site')))
            ->when(ctype_digit((string) $request->query('superviseur')), fn ($q) => $q->whereHas(
                'site', fn ($s) => $s->where('superviseur_id', $request->query('superviseur'))
            ))
            ->orderBy($tri, $sens)
            ->when($tri === 'nom', fn ($q) => $q->orderBy('prenom', $sens))
            ->paginate(15)
            ->withQueryString();

        $compteurs = Agent::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return view('agents.index', [
            'agents' => $agents,
            'compteurs' => $compteurs,
            'sansSite' => Agent::whereNull('site_id')->count(),
            'sites' => Site::orderBy('nom')->get(['id', 'nom']),
            'superviseurs' => Superviseur::orderBy('nom')->orderBy('prenom')->get(['id', 'nom', 'prenom']),
            'tri' => $tri,
            'sens' => $sens,
        ]);
    }

    public function create(Request $request): View
    {
        return view('agents.create', [
            // Permet de pré-remplir le site depuis la fiche d'un site : /agents/create?site=3
            'agent' => new Agent(['statut' => 'actif', 'site_id' => $request->integer('site') ?: null]),
            'codeSuggere' => Agent::prochainCode(),
            'sites' => Site::with('superviseur')->orderBy('nom')->get(),
        ]);
    }

    public function store(AgentRequest $request): RedirectResponse
    {
        $agent = Agent::create($request->validated());

        return redirect()
            ->route('agents.show', $agent)
            ->with('success', "L'agent « {$agent->nom_complet} » a été créé.");
    }

    public function show(Agent $agent, \App\Services\Commissions $commissions): View
    {
        $agent->load('site.superviseur');
        $lots = $agent->lots()->with('agent')->latest('date_remise')->latest('id')->take(8)->get();

        $commission = $commissions->pour($agent);

        return view('agents.show', compact('agent', 'lots', 'commission'));
    }

    public function edit(Agent $agent): View
    {
        return view('agents.edit', [
            'agent' => $agent,
            'sites' => Site::with('superviseur')->orderBy('nom')->get(),
        ]);
    }

    public function update(AgentRequest $request, Agent $agent): RedirectResponse
    {
        $agent->update($request->validated());

        return redirect()
            ->route('agents.show', $agent)
            ->with('success', "L'agent « {$agent->nom_complet} » a été mis à jour.");
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        if ($agent->lots()->enCours()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$agent->nom_complet} » : il a des lots de tickets en cours.");
        }

        $agent->delete();

        return redirect()
            ->route('agents.index')
            ->with('success', "L'agent « {$agent->nom_complet} » a été supprimé.");
    }
}

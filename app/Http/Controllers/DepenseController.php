<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepenseRequest;
use App\Models\Depense;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepenseController extends Controller
{
    private const TRIS = ['code', 'date_depense', 'categorie', 'montant'];

    /** Les justificatifs sont stockés en privé (storage/app/private/justificatifs). */
    private const DOSSIER = 'justificatifs';

    public function index(Request $request): View
    {
        $tri = in_array($request->query('tri'), self::TRIS, true) ? $request->query('tri') : 'date_depense';
        $sens = $request->query('sens') === 'asc' ? 'asc' : 'desc';

        $requete = Depense::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($s) => $s
                ->where('libelle', 'like', '%' . $request->query('q') . '%')
                ->orWhere('code', 'like', '%' . $request->query('q') . '%')
                ->orWhere('fournisseur', 'like', '%' . $request->query('q') . '%')
                ->orWhere('reference', 'like', '%' . $request->query('q') . '%')))
            ->when($request->query('site') === 'general', fn ($q) => $q->whereNull('site_id'))
            ->when(ctype_digit((string) $request->query('site')), fn ($q) => $q->where('site_id', $request->query('site')))
            ->when(array_key_exists((string) $request->query('categorie'), Depense::CATEGORIES), fn ($q) => $q->where('categorie', $request->query('categorie')))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('date_depense', '>=', $request->query('du')))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('date_depense', '<=', $request->query('au')));

        $total = (int) (clone $requete)->sum('montant');
        $parCategorie = (clone $requete)
            ->selectRaw('categorie, SUM(montant) as total')
            ->groupBy('categorie')
            ->orderByDesc('total')
            ->pluck('total', 'categorie');

        return view('depenses.index', [
            'depenses' => $requete->with('site')->orderBy($tri, $sens)->orderByDesc('id')->paginate(20)->withQueryString(),
            'total' => $total,
            'parCategorie' => $parCategorie,
            'totalMois' => (int) Depense::whereBetween('date_depense', [now()->startOfMonth(), now()->endOfMonth()])->sum('montant'),
            'sites' => Site::orderBy('nom')->get(['id', 'nom']),
            'tri' => $tri,
            'sens' => $sens,
        ]);
    }

    public function create(Request $request): View
    {
        return view('depenses.create', [
            'depense' => new Depense([
                'date_depense' => now(),
                'mode' => 'especes',
                'site_id' => $request->integer('site') ?: null,
                'categorie' => $request->query('categorie'),
            ]),
            'sites' => $this->sites(),
            'codeSuggere' => Depense::prochainCode(),
        ]);
    }

    public function store(DepenseRequest $request): RedirectResponse
    {
        $depense = new Depense($request->donnees());
        if ($request->hasFile('fichier')) {
            $depense->justificatif = $request->file('fichier')->store(self::DOSSIER);
        }
        $depense->save();

        return redirect()
            ->route($request->boolean('encore') ? 'depenses.create' : 'depenses.show', $request->boolean('encore') ? ['site' => $depense->site_id] : $depense)
            ->with('success', "Dépense de {$depense->montant_affiche} enregistrée ({$depense->site_label}).");
    }

    public function show(Depense $depense): View
    {
        $depense->load('site');

        return view('depenses.show', compact('depense'));
    }

    public function edit(Depense $depense): View
    {
        return view('depenses.edit', [
            'depense' => $depense,
            'sites' => $this->sites($depense),
        ]);
    }

    public function update(DepenseRequest $request, Depense $depense): RedirectResponse
    {
        $depense->fill($request->donnees());

        if ($request->hasFile('fichier') || $request->boolean('supprimer_justificatif')) {
            $this->supprimerFichier($depense);
            $depense->justificatif = $request->hasFile('fichier') ? $request->file('fichier')->store(self::DOSSIER) : null;
        }
        $depense->save();

        return redirect()
            ->route('depenses.show', $depense)
            ->with('success', "La dépense {$depense->code} a été mise à jour.");
    }

    public function destroy(Depense $depense): RedirectResponse
    {
        $depense->delete(); // le justificatif est conservé (corbeille)

        return redirect()
            ->route('depenses.index')
            ->with('success', "La dépense {$depense->code} a été supprimée.");
    }

    /** Affiche ou télécharge le justificatif. */
    public function justificatif(Depense $depense): StreamedResponse
    {
        abort_unless($depense->justificatif && Storage::exists($depense->justificatif), 404);

        return Storage::response($depense->justificatif, $depense->code . '.' . pathinfo($depense->justificatif, PATHINFO_EXTENSION));
    }

    /* ---------------------------------------------------------------- */

    private function sites(?Depense $depense = null)
    {
        return Site::where(fn ($q) => $q->where('statut', '!=', 'inactif')->orWhere('id', $depense?->site_id))
            ->orderBy('nom')->get(['id', 'nom']);
    }

    private function supprimerFichier(Depense $depense): void
    {
        if ($depense->justificatif) {
            Storage::delete($depense->justificatif);
        }
    }
}

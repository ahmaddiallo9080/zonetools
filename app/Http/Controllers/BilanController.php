<?php

namespace App\Http\Controllers;

use App\Models\Bilan;
use App\Models\Site;
use App\Services\BilanMensuel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Comptes rendus / bilans mensuels par site et pour tout le réseau.
 */
class BilanController extends Controller
{
    public function __construct(private BilanMensuel $service) {}

    /** Vue d'ensemble du mois : tous les sites. */
    public function index(Request $request): View
    {
        $mois = BilanMensuel::mois($request->query('mois'));

        return view('bilans.index', [
            'mois' => $mois,
            'moisTermine' => $mois->copy()->endOfMonth()->isPast(),
            'reseau' => $this->service->reseau($mois),
            'precedent' => $this->service->reseau($mois->copy()->subMonthNoOverflow())['total'],
        ]);
    }

    /** Compte rendu détaillé d'un site pour un mois. */
    public function show(string $mois, Site $site): View
    {
        $mois = BilanMensuel::mois($mois);
        $bilan = $this->service->pour($site, $mois);

        // Si le bilan est clôturé : les données ont-elles changé depuis ?
        $ecart = null;
        if ($bilan['bilan']) {
            $direct = $this->service->calculer($site, $mois);
            $ecart = collect(Bilan::CHIFFRES)->contains(fn ($c) => (int) $direct[$c] !== (int) $bilan[$c]);
        }

        return view('bilans.show', [
            'site' => $site->load('superviseur'),
            'mois' => $mois,
            'moisTermine' => $mois->copy()->endOfMonth()->isPast(),
            'b' => $bilan,
            'precedent' => $this->service->pour($site, $mois->copy()->subMonthNoOverflow()),
            'donneesModifiees' => $ecart,
        ]);
    }

    /** Clôture : fige les chiffres du mois avec les observations du gérant. */
    public function cloturer(Request $request, string $mois, Site $site): RedirectResponse
    {
        $mois = BilanMensuel::mois($mois);
        $donnees = $request->validate(['observations' => ['nullable', 'string', 'max:5000']]);

        if (! $mois->copy()->endOfMonth()->isPast()) {
            return back()->with('error', 'Le mois n\'est pas encore terminé : vous pourrez le clôturer à partir du 1er du mois suivant.');
        }

        $chiffres = $this->service->calculer($site, $mois);

        $bilan = Bilan::where('site_id', $site->id)->whereDate('mois', $mois->toDateString())->first()
            ?? new Bilan(['site_id' => $site->id, 'mois' => $mois->toDateString()]);
        $bilan->fill($chiffres + ['observations' => $donnees['observations'] ?? null, 'cloture_le' => now()])->save();

        return redirect()
            ->route('bilans.show', [$mois->format('Y-m'), $site])
            ->with('success', "Le bilan de {$mois->translatedFormat('F Y')} pour « {$site->nom} » est clôturé.");
    }

    /** Modifier seulement les observations d'un bilan clôturé. */
    public function observations(Request $request, string $mois, Site $site): RedirectResponse
    {
        $mois = BilanMensuel::mois($mois);
        $donnees = $request->validate(['observations' => ['nullable', 'string', 'max:5000']]);

        Bilan::where('site_id', $site->id)->whereDate('mois', $mois->toDateString())->firstOrFail()
            ->update(['observations' => $donnees['observations'] ?? null]);

        return back()->with('success', 'Observations enregistrées.');
    }

    /** Rouvrir : supprime le bilan figé (les chiffres redeviennent calculés en direct). */
    public function rouvrir(string $mois, Site $site): RedirectResponse
    {
        $mois = BilanMensuel::mois($mois);
        Bilan::where('site_id', $site->id)->whereDate('mois', $mois->toDateString())->delete();

        return back()->with('success', 'Le bilan a été rouvert : les chiffres sont de nouveau calculés en direct.');
    }

    /** Version imprimable du bilan global du réseau. */
    public function imprimer(string $mois): View
    {
        $mois = BilanMensuel::mois($mois);

        return view('bilans.global', [
            'mois' => $mois,
            'reseau' => $this->service->reseau($mois),
        ]);
    }

    /** Export CSV (compatible Excel) des chiffres de tous les sites. */
    public function export(string $mois): StreamedResponse
    {
        $mois = BilanMensuel::mois($mois);
        $reseau = $this->service->reseau($mois);
        $nomFichier = 'bilan-zonetools-' . $mois->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($reseau) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF"); // BOM : accents corrects dans Excel
            $entetes = ['Site', 'Statut', 'Rapports', 'Lots remis', 'Tickets remis', 'Vendus', 'Défectueux', 'Rendus',
                'Ventes (GNF)', 'Commissions agents', 'Commission superviseur', 'Dépenses', 'Bénéfice', 'Argent versé', 'Manquants', 'Observations'];
            fputcsv($f, $entetes, ';');

            foreach ($reseau['sites'] as $l) {
                fputcsv($f, [
                    $l['site']->nom, $l['bilan'] ? 'Clôturé' : 'Non clôturé',
                    $l['rapports'], $l['lots_remis'], $l['tickets_remis'], $l['vendus'], $l['defectueux'], $l['rendus'],
                    $l['ventes'], $l['commission_agents'], $l['commission_superviseur'], $l['depenses'], $l['benefice'],
                    $l['montant_verse'], $l['manquants'], $l['bilan']?->observations,
                ], ';');
            }
            fputcsv($f, ['Dépenses générales', '', '', '', '', '', '', '', '', '', '', $reseau['generales']['depenses'], -$reseau['generales']['depenses'], '', '', ''], ';');
            $t = $reseau['total'];
            fputcsv($f, ['TOTAL RÉSEAU', '', $t['rapports'], $t['lots_remis'], $t['tickets_remis'], $t['vendus'], $t['defectueux'], $t['rendus'],
                $t['ventes'], $t['commission_agents'], $t['commission_superviseur'], $t['depenses'], $t['benefice'], $t['montant_verse'], $t['manquants'], ''], ';');
            fclose($f);
        }, $nomFichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

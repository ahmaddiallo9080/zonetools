{{-- Formulaire du rapport de fin de lot. Variables : $lot, $rapport, $config --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="rapportForm(@js($config))">
    <div class="space-y-6 lg:col-span-2">
        {{-- Rappel du lot --}}
        <div class="card p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Lot</p>
                    <a href="{{ route('lots.show', $lot) }}" class="font-mono text-lg font-semibold text-gray-900 hover:text-primary-600">{{ $lot->code }}</a>
                </div>
                <div class="text-sm"><p class="text-gray-500">Site</p><p class="font-medium">{{ $lot->site?->nom }}</p></div>
                <div class="text-sm"><p class="text-gray-500">Agent</p><p class="font-medium">{{ $lot->agent?->nom_complet }}</p></div>
                <div class="text-sm"><p class="text-gray-500">Superviseur</p><p class="font-medium">{{ $lot->superviseur?->nom_complet ?? '—' }}</p></div>
                <div class="text-sm"><p class="text-gray-500">Remis le</p><p class="font-medium">{{ $lot->date_remise->format('d/m/Y') }}</p></div>
            </div>
        </div>

        {{-- Saisie par forfait --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Résultat des ventes</h2>
            <p class="mt-1 text-sm text-gray-500">Pour chaque forfait, saisissez les tickets vendus et ceux qui n'ont pas fonctionné. Le reste est compté comme <strong>rendu</strong>.</p>

            @if ($errors->has('lignes') || $errors->has('lignes.*'))
                <ul class="mt-3 space-y-1 text-sm text-red-600">
                    @foreach (collect($errors->get('lignes'))->merge(collect($errors->get('lignes.*'))->flatten())->unique() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="pb-2 pe-3">Forfait</th>
                            <th class="pb-2 pe-3 text-right">Remis</th>
                            <th class="w-28 pb-2 pe-3">Vendus</th>
                            <th class="w-28 pb-2 pe-3">Défectueux</th>
                            <th class="pb-2 pe-3 text-right">Rendus</th>
                            <th class="pb-2 text-right">Montant vendu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="ligne in lignes" :key="ligne.id">
                            <tr :class="rendus(ligne) < 0 ? 'bg-red-50' : ''">
                                <td class="py-2 pe-3">
                                    <span class="font-medium text-gray-900" x-text="ligne.forfait"></span>
                                    <span class="block text-xs text-gray-500" x-text="gnf(ligne.prix) + ' / ticket'"></span>
                                </td>
                                <td class="py-2 pe-3 text-right font-medium" x-text="ligne.quantite"></td>
                                <td class="py-2 pe-3">
                                    <input type="number" min="0" :max="ligne.quantite" x-model="ligne.vendus" :name="`lignes[${ligne.id}][vendus]`" required placeholder="0"
                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </td>
                                <td class="py-2 pe-3">
                                    <input type="number" min="0" :max="ligne.quantite" x-model="ligne.defectueux" :name="`lignes[${ligne.id}][defectueux]`"
                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                                </td>
                                <td class="py-2 pe-3 text-right font-medium" :class="rendus(ligne) < 0 ? 'text-red-600' : 'text-gray-700'" x-text="rendus(ligne)"></td>
                                <td class="whitespace-nowrap py-2 text-right font-semibold" x-text="gnf(montantLigne(ligne))"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="border-t border-gray-200 font-semibold">
                        <tr>
                            <td class="pt-3">Total</td>
                            <td class="pt-3 text-right" x-text="total('quantite')"></td>
                            <td class="pt-3 ps-3 text-green-700" x-text="total('vendus')"></td>
                            <td class="pt-3 ps-3 text-red-600" x-text="total('defectueux')"></td>
                            <td class="pt-3 pe-3 text-right" x-text="lignes.reduce((t, l) => t + rendus(l), 0)"></td>
                            <td class="whitespace-nowrap pt-3 text-right text-base text-primary-700" x-text="gnf(montantVendu())"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p x-show="lignes.some(l => rendus(l) < 0)" class="mt-3 text-sm text-red-600">Vendus + défectueux dépasse la quantité remise sur une ligne.</p>
        </div>

        <div class="card p-6">
            <x-input-label for="observations" value="Observations" />
            <x-textarea id="observations" name="observations" class="mt-1 block w-full" placeholder="Ex : coupure de courant 2 jours, tickets défectueux signalés...">{{ old('observations', $rapport->observations) }}</x-textarea>
            <x-input-error :messages="$errors->get('observations')" class="mt-2" />
        </div>
    </div>

    <div class="space-y-6">
        {{-- Commissions --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Commissions</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Montant vendu</dt><dd class="font-semibold" x-text="gnf(montantVendu())"></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Agent ({{ \App\Support\Format::pourcent($lot->taux_agent) }})</dt><dd class="font-medium" x-text="gnf(commissionAgent())"></dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Superviseur ({{ \App\Support\Format::pourcent($lot->taux_superviseur) }})</dt><dd class="font-medium" x-text="gnf(commissionSuperviseur())"></dd></div>
                <div class="flex justify-between border-t border-gray-100 pt-2"><dt class="text-gray-500">Net gérant</dt><dd class="font-semibold" x-text="gnf(montantVendu() - commissionAgent() - commissionSuperviseur())"></dd></div>
            </dl>
        </div>

        {{-- Argent versé --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Argent versé par l'agent</h2>
            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Montant attendu</span><span class="font-semibold" x-text="gnf(attendu())"></span></div>
                <p class="mt-1 text-xs text-gray-500" x-show="config.agentDeduit">Montant vendu moins la commission de l'agent (il la garde).</p>
                <p class="mt-1 text-xs text-gray-500" x-show="!config.agentDeduit">L'agent verse la totalité des ventes.</p>
            </div>

            <div class="mt-4">
                <x-input-label for="montant_verse">Montant versé <span class="text-red-500">*</span></x-input-label>
                <div class="relative mt-1">
                    <input id="montant_verse" name="montant_verse" type="text" inputmode="numeric" x-model="montantVerse" required placeholder="0"
                           class="block w-full rounded-lg border-gray-300 pe-14 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-sm text-gray-500">GNF</span>
                </div>
                <button type="button" class="mt-1 text-xs font-medium text-primary-600 hover:text-primary-700" @click="montantVerse = attendu()">Il a versé le montant attendu</button>
                <x-input-error :messages="$errors->get('montant_verse')" class="mt-2" />
            </div>

            <div class="mt-4 rounded-lg px-3 py-2 text-sm font-semibold"
                 :class="ecart() < 0 ? 'bg-red-50 text-red-700' : (ecart() > 0 ? 'bg-amber-50 text-amber-800' : 'bg-green-50 text-green-700')">
                <span x-text="ecart() === 0 ? 'Compte juste' : (ecart() < 0 ? 'Manque ' + gnf(-ecart()) : 'Surplus ' + gnf(ecart()))"></span>
            </div>
        </div>

        <div class="card p-6 space-y-4">
            <div>
                <x-input-label for="date_rapport">Date du rapport <span class="text-red-500">*</span></x-input-label>
                <x-text-input id="date_rapport" name="date_rapport" type="date" class="mt-1 block w-full" required
                              :value="old('date_rapport', $rapport->date_rapport?->format('Y-m-d'))"
                              :min="$lot->date_remise->format('Y-m-d')" :max="now()->format('Y-m-d')" />
                <x-input-error :messages="$errors->get('date_rapport')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="code" value="Numéro du rapport" />
                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code', $rapport->code)" :placeholder="$codeSuggere ?? ''" />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ $rapport->exists ? route('rapports.show', $rapport) : route('lots.show', $lot) }}">
                <x-secondary-button>Annuler</x-secondary-button>
            </a>
            <x-primary-button>{{ $rapport->exists ? 'Enregistrer' : 'Valider le rapport' }}</x-primary-button>
        </div>
    </div>
</div>

@once
    <script>
        function rapportForm(config) {
            return {
                config,
                lignes: config.lignes,
                montantVerse: config.montantVerse,
                n(v) { return parseInt(String(v ?? '').replace(/[^\d]/g, '')) || 0; },
                rendus(l) { return l.quantite - this.n(l.vendus) - this.n(l.defectueux); },
                montantLigne(l) { return this.n(l.vendus) * l.prix; },
                total(champ) { return this.lignes.reduce((t, l) => t + this.n(l[champ]), 0); },
                montantVendu() { return this.lignes.reduce((t, l) => t + this.montantLigne(l), 0); },
                commissionAgent() { return Math.round(this.montantVendu() * config.tauxAgent / 100); },
                commissionSuperviseur() { return Math.round(this.montantVendu() * config.tauxSuperviseur / 100); },
                attendu() { return this.montantVendu() - (config.agentDeduit ? this.commissionAgent() : 0); },
                ecart() { return this.n(this.montantVerse) - this.attendu(); },
                gnf(v) { return new Intl.NumberFormat('fr-FR').format(v || 0).replace(/ /g, ' ') + ' GNF'; },
            };
        }
    </script>
@endonce

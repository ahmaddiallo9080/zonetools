@php
    /** @var \App\Models\Lot $lot */
    $etatInitial = [
        'site_id' => (string) old('site_id', $lot->site_id ?? ''),
        'agent_id' => (string) old('agent_id', $lot->agent_id ?? ''),
        'taux_agent' => old('taux_agent', $lot->exists ? (float) $lot->taux_agent : null),
        'taux_superviseur' => old('taux_superviseur', $lot->exists ? (float) $lot->taux_superviseur : null),
        'lignes' => array_values($lignes),
    ];
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3"
     x-data="lotForm(@js($config), @js($etatInitial))">

    <div class="space-y-6 lg:col-span-2">
        {{-- Remise --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Remise du lot</h2>
            <p class="mt-1 text-sm text-gray-500">Les champs marqués d'un <span class="text-red-500">*</span> sont obligatoires.</p>

            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="site_id">Site <span class="text-red-500">*</span></x-input-label>
                    <x-select id="site_id" name="site_id" class="mt-1 block w-full" x-model="site_id" @change="siteChange()" required>
                        <option value="">— Choisir un site —</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->nom }}</option>
                        @endforeach
                    </x-select>
                    <p class="mt-1 text-xs text-gray-500" x-show="site_id">
                        Superviseur : <span class="font-medium text-gray-700" x-text="superviseur() ?? 'aucun'"></span>
                    </p>
                    <x-input-error :messages="$errors->get('site_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="agent_id">Agent <span class="text-red-500">*</span></x-input-label>
                    <select id="agent_id" name="agent_id" x-model="agent_id" @change="agentChange()" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-50"
                            :disabled="!site_id">
                        <option value="" x-text="site_id ? (agentsDuSite().length ? '— Choisir un agent —' : 'Aucun agent sur ce site') : 'Choisissez d\'abord un site'"></option>
                        <template x-for="agent in agentsDuSite()" :key="agent.id">
                            <option :value="agent.id" x-text="agent.nom" :selected="String(agent.id) === agent_id"></option>
                        </template>
                    </select>
                    <x-input-error :messages="$errors->get('agent_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="date_remise">Date de remise <span class="text-red-500">*</span></x-input-label>
                    <x-text-input id="date_remise" name="date_remise" type="date" class="mt-1 block w-full"
                                  :value="old('date_remise', $lot->date_remise?->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('date_remise')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="date_fin_prevue" value="Date de fin prévue" />
                    <x-text-input id="date_fin_prevue" name="date_fin_prevue" type="date" class="mt-1 block w-full"
                                  :value="old('date_fin_prevue', $lot->date_fin_prevue?->format('Y-m-d'))" />
                    <x-input-error :messages="$errors->get('date_fin_prevue')" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- Tickets --}}
        <div class="card p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Tickets du lot <span class="text-red-500">*</span></h2>
                    <p class="mt-1 text-sm text-gray-500">Un lot peut contenir plusieurs forfaits. Le prix est celui du site choisi.</p>
                </div>
                <x-secondary-button @click="ajouterLigne()">+ Ajouter un forfait</x-secondary-button>
            </div>

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
                            <th class="w-32 pb-2 pe-3">Quantité</th>
                            <th class="w-32 pb-2 pe-3 text-right">Prix unitaire</th>
                            <th class="w-36 pb-2 pe-3 text-right">Montant</th>
                            <th class="w-10 pb-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(ligne, index) in lignes" :key="index">
                            <tr class="align-middle">
                                <td class="py-1.5 pe-3">
                                    <select x-bind:name="`lignes[${index}][forfait_id]`" x-model="ligne.forfait_id"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="">— Choisir —</option>
                                        @foreach ($forfaits as $forfait)
                                            <option value="{{ $forfait->id }}" :selected="ligne.forfait_id == '{{ $forfait->id }}'">{{ $forfait->nom }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-1.5 pe-3">
                                    <input type="number" min="1" x-bind:name="`lignes[${index}][quantite]`" x-model="ligne.quantite" placeholder="0"
                                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </td>
                                <td class="whitespace-nowrap py-1.5 pe-3 text-right text-gray-600">
                                    <span x-text="ligne.forfait_id ? gnf(prix(ligne.forfait_id)) : '—'"></span>
                                    <span x-show="ligne.forfait_id && estPrixParticulier(ligne.forfait_id)" class="block text-[11px] text-amber-700">prix du site</span>
                                </td>
                                <td class="whitespace-nowrap py-1.5 pe-3 text-right font-semibold text-gray-900" x-text="gnf(montantLigne(ligne))"></td>
                                <td class="py-1.5 text-right">
                                    <button type="button" @click="lignes.splice(index, 1)" title="Retirer" class="rounded-lg p-1.5 text-red-600 hover:bg-red-50">
                                        <x-icon name="trash" class="h-5 w-5" />
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="border-t border-gray-200">
                        <tr>
                            <td class="pt-3 font-semibold text-gray-900">Total</td>
                            <td class="pt-3 font-semibold text-gray-900"><span x-text="totalTickets()"></span> tickets</td>
                            <td></td>
                            <td class="whitespace-nowrap pt-3 text-right text-base font-bold text-primary-700" x-text="gnf(totalMontant())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p x-show="lignes.length === 0" class="mt-4 rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500">
                Aucun forfait : cliquez sur « + Ajouter un forfait ».
            </p>
        </div>

        <div class="card p-6">
            <x-input-label for="notes" value="Notes" />
            <x-textarea id="notes" name="notes" class="mt-1 block w-full">{{ old('notes', $lot->notes) }}</x-textarea>
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>
    </div>

    <div class="space-y-6">
        {{-- Commissions --}}
        <div class="card p-6">
            <h2 class="text-base font-semibold text-gray-900">Commissions</h2>
            <p class="mt-1 text-xs text-gray-500">Pourcentage du montant vendu. Pré-rempli avec le taux par défaut de l'agent et du superviseur.</p>

            <div class="mt-4 space-y-4">
                <div>
                    <x-input-label for="taux_agent" value="Commission agent" />
                    <div class="relative mt-1">
                        <input id="taux_agent" name="taux_agent" type="number" step="0.01" min="0" max="100" x-model="taux_agent"
                               class="block w-full rounded-lg border-gray-300 pe-10 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-sm text-gray-500">%</span>
                    </div>
                    <x-input-error :messages="$errors->get('taux_agent')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="taux_superviseur" value="Commission superviseur" />
                    <div class="relative mt-1">
                        <input id="taux_superviseur" name="taux_superviseur" type="number" step="0.01" min="0" max="100" x-model="taux_superviseur"
                               class="block w-full rounded-lg border-gray-300 pe-10 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pe-3 text-sm text-gray-500">%</span>
                    </div>
                    <x-input-error :messages="$errors->get('taux_superviseur')" class="mt-2" />
                </div>
            </div>
        </div>

        {{-- Récapitulatif --}}
        <div class="card bg-primary-600 p-6 text-white">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-primary-100">Si tout est vendu</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-primary-100">Montant total</dt><dd class="font-semibold" x-text="gnf(totalMontant())"></dd></div>
                <div class="flex justify-between"><dt class="text-primary-100">Commission agent</dt><dd x-text="'− ' + gnf(commission(taux_agent))"></dd></div>
                <div class="flex justify-between"><dt class="text-primary-100">Commission superviseur</dt><dd x-text="'− ' + gnf(commission(taux_superviseur))"></dd></div>
                <div class="flex justify-between border-t border-white/20 pt-2 text-base"><dt class="font-semibold">Net gérant</dt><dd class="font-bold" x-text="gnf(totalMontant() - commission(taux_agent) - commission(taux_superviseur))"></dd></div>
            </dl>
        </div>

        <div class="card p-6">
            <x-input-label for="code" value="Numéro du lot" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code', $lot->code)" :placeholder="$codeSuggere ?? ''" />
            <p class="mt-1 text-xs text-gray-500">Laissez vide pour générer automatiquement.</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ $lot->exists ? route('lots.show', $lot) : route('lots.index') }}">
                <x-secondary-button>Annuler</x-secondary-button>
            </a>
            <x-primary-button>{{ $lot->exists ? 'Enregistrer' : 'Créer le lot' }}</x-primary-button>
        </div>
    </div>
</div>

@once
    <script>
        function lotForm(config, etat) {
            return {
                ...etat,
                init() {
                    // Valeurs par défaut des taux à la création
                    if (this.taux_agent === null) this.taux_agent = this.agentCourant()?.taux ?? 0;
                    if (this.taux_superviseur === null) this.taux_superviseur = config.sites[this.site_id]?.taux ?? 0;
                    if (this.lignes.length === 0) this.ajouterLigne();
                },
                agentsDuSite() {
                    return config.agents.filter(a => String(a.site_id) === String(this.site_id));
                },
                agentCourant() {
                    return config.agents.find(a => String(a.id) === String(this.agent_id));
                },
                superviseur() {
                    return config.sites[this.site_id]?.superviseur ?? null;
                },
                siteChange() {
                    if (!this.agentsDuSite().some(a => String(a.id) === String(this.agent_id))) this.agent_id = '';
                    this.taux_superviseur = config.sites[this.site_id]?.taux ?? 0;
                },
                agentChange() {
                    this.taux_agent = this.agentCourant()?.taux ?? 0;
                },
                ajouterLigne() {
                    this.lignes.push({ forfait_id: '', quantite: '' });
                },
                prix(forfaitId) {
                    // Prix déjà figé (modification sans changement de site)
                    if (config.prixFiges[forfaitId] !== undefined && String(this.site_id) === String(config.siteInitial)) {
                        return config.prixFiges[forfaitId];
                    }
                    const tarif = config.tarifs[forfaitId];
                    if (!tarif) return 0;
                    return tarif.sites[this.site_id] ?? tarif.base;
                },
                estPrixParticulier(forfaitId) {
                    const tarif = config.tarifs[forfaitId];
                    return tarif && tarif.sites[this.site_id] !== undefined;
                },
                montantLigne(ligne) {
                    return ligne.forfait_id ? this.prix(ligne.forfait_id) * (parseInt(ligne.quantite) || 0) : 0;
                },
                totalTickets() {
                    return this.lignes.reduce((t, l) => t + (parseInt(l.quantite) || 0), 0);
                },
                totalMontant() {
                    return this.lignes.reduce((t, l) => t + this.montantLigne(l), 0);
                },
                commission(taux) {
                    return Math.round(this.totalMontant() * (parseFloat(String(taux).replace(',', '.')) || 0) / 100);
                },
                gnf(n) {
                    return new Intl.NumberFormat('fr-FR').format(n || 0).replace(/ /g, ' ') + ' GNF';
                },
            };
        }
    </script>
@endonce

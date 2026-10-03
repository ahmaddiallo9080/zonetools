{{-- Contenu de la sidebar (couleur primaire #3D63DD) --}}
<div class="flex h-full flex-col">
    <div class="flex h-16 items-center justify-between px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 text-white" aria-label="ZoneTools – tableau de bord">
            <x-application-logo class="h-8 w-auto" />
            <x-logo-texte class="h-[18px] w-auto" />
        </a>
        <button type="button" class="text-primary-100 lg:hidden" @click="sidebarOpen = false">
            <span class="sr-only">Fermer le menu</span>
            <x-icon name="x" class="h-6 w-6" />
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        <div class="space-y-1">
            <x-sidebar-link route="dashboard" icon="home" :active="request()->routeIs('dashboard')">Tableau de bord</x-sidebar-link>
        </div>

        <div>
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-primary-200">Réseau</p>
            <div class="space-y-1">
                <x-sidebar-link route="sites.index" icon="wifi">Sites / Hotspots</x-sidebar-link>
                <x-sidebar-link route="superviseurs.index" icon="user-tie">Superviseurs</x-sidebar-link>
                <x-sidebar-link route="agents.index" icon="users">Agents</x-sidebar-link>
            </div>
        </div>

        <div>
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-primary-200">Tickets</p>
            <div class="space-y-1">
                <x-sidebar-link route="forfaits.index" icon="tag">Forfaits</x-sidebar-link>
                <x-sidebar-link route="lots.index" icon="ticket">Lots de tickets</x-sidebar-link>
                <x-sidebar-link route="rapports.index" icon="clipboard">Rapports de lot</x-sidebar-link>
            </div>
        </div>

        <div>
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-primary-200">Finances</p>
            <div class="space-y-1">
                <x-sidebar-link route="versements.index" icon="banknotes" :active="request()->routeIs('versements.*', 'paiements.*')">Versements</x-sidebar-link>
                <x-sidebar-link route="depenses.index" icon="receipt">Dépenses</x-sidebar-link>
                <x-sidebar-link route="bilans.index" icon="calendar">Bilans mensuels</x-sidebar-link>
                <x-sidebar-link route="statistiques.index" icon="chart">Statistiques</x-sidebar-link>
            </div>
        </div>
    </nav>

    <div class="border-t border-white/10 p-3 space-y-1">
        <x-sidebar-link route="profile.edit" icon="cog" :active="request()->routeIs('profile.*')">Paramètres</x-sidebar-link>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-primary-100 hover:bg-white/10 hover:text-white">
                <x-icon name="logout" class="h-5 w-5" />
                Déconnexion
            </button>
        </form>
    </div>
</div>

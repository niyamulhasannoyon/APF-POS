<nav x-data="{ open: false }" class="bg-slate-900 border-b border-slate-800/80 sticky top-0 z-40 backdrop-blur-md bg-opacity-90">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <span class="text-lg font-black tracking-wider bg-gradient-to-r from-indigo-400 to-emerald-400 bg-clip-text text-transparent">APF POS</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-4 lg:space-x-6 sm:-my-px sm:ms-8 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-slate-300 hover:text-white">
                        {{ __('messages.dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.branches.index')" :active="request()->routeIs('admin.branches.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.branches') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.inventory') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.transfers.index')" :active="request()->routeIs('admin.transfers.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.transfers') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.suppliers.index')" :active="request()->routeIs('admin.suppliers.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.suppliers') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.purchases.index')" :active="request()->routeIs('admin.purchases.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.purchases') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.staff.index')" :active="request()->routeIs('admin.staff.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.staff') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.reports') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.customers.index')" :active="request()->routeIs('admin.customers.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.customers') }}
                    </x-nav-link>
                    <x-nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')" class="text-slate-300 hover:text-white">
                        {{ __('messages.sales_reports') }}
                    </x-nav-link>
                    <x-nav-link :href="route('pos.index')" :active="request()->routeIs('pos.index')" class="text-emerald-400 hover:text-emerald-300 font-semibold bg-emerald-500/10 px-3.5 rounded-lg h-9 mt-3.5 border border-emerald-500/20 shadow-lg shadow-emerald-500/5 flex items-center transition-all duration-200">
                        {{ __('messages.open_pos') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                <!-- Language Switcher -->
                <div class="flex gap-2 text-xs font-semibold mr-1 border border-slate-800 px-2 py-1.5 rounded-lg bg-slate-950/60 select-none">
                    <a href="{{ route('lang.swap', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'text-indigo-400 font-extrabold shadow-[0_0_8px_rgba(99,102,241,0.2)]' : 'text-slate-500 hover:text-slate-300' }}">EN</a>
                    <span class="text-slate-800">|</span>
                    <a href="{{ route('lang.swap', 'bn') }}" class="{{ app()->getLocale() === 'bn' ? 'text-indigo-400 font-extrabold shadow-[0_0_8px_rgba(99,102,241,0.2)]' : 'text-slate-500 hover:text-slate-300' }}">বাং</a>
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-slate-800/85 text-sm leading-4 font-medium rounded-lg text-slate-300 bg-slate-950/40 hover:text-slate-100 hover:bg-slate-800/50 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')" class="text-slate-300 hover:bg-slate-800 hover:text-white">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" class="text-slate-300 hover:bg-slate-800 hover:text-white"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:text-slate-300 hover:bg-slate-800/60 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-slate-900 border-b border-slate-800">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.branches.index')" :active="request()->routeIs('admin.branches.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Branches') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Inventory') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.transfers.index')" :active="request()->routeIs('admin.transfers.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Transfers') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.suppliers.index')" :active="request()->routeIs('admin.suppliers.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Suppliers') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.purchases.index')" :active="request()->routeIs('admin.purchases.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Purchases') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.staff.index')" :active="request()->routeIs('admin.staff.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Staff') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Reports') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.customers.index')" :active="request()->routeIs('admin.customers.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Customers') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                {{ __('Sales Reports') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pos.index')" :active="request()->routeIs('pos.index')" class="text-emerald-400 font-bold bg-emerald-500/10 border-l-4 border-emerald-500">
                {{ __('Cashier POS Screen ➔') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-slate-800">
            <div class="px-4 flex items-center justify-between">
                <div>
                    <div class="font-medium text-base text-slate-200">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
                </div>
                <!-- Mobile Language Swapper -->
                <div class="flex gap-2 text-xs font-semibold mr-1 border border-slate-800 px-2 py-1.5 rounded-lg bg-slate-950/60 select-none">
                    <a href="{{ route('lang.swap', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'text-indigo-400 font-extrabold' : 'text-slate-500' }}">EN</a>
                    <span class="text-slate-800">|</span>
                    <a href="{{ route('lang.swap', 'bn') }}" class="{{ app()->getLocale() === 'bn' ? 'text-indigo-400 font-extrabold' : 'text-slate-500' }}">বাং</a>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" class="text-slate-300 hover:bg-slate-850 hover:text-white">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')" class="text-slate-300 hover:bg-slate-850 hover:text-white"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 

$wallet_balance = (float) get_user_meta($current_user->ID, 'i360_wallet_balance', true);
?>

<div id="i360-app-root" class="flex flex-col md:flex-row w-full bg-slate-50 relative border border-slate-200 shadow-sm mt-8 mb-20 h-auto min-h-[calc(100vh-160px)] items-stretch rounded-xl overflow-hidden font-sans">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/locale/es.js"></script>
    <script async src="https://js.stripe.com/v3/buy-button.js"></script>
    
    <script>
        tailwind.config = { corePlugins: { preflight: false } };
        <?php if (strpos(get_locale(), 'en') === 0) : ?>
            dayjs.locale('en');
        <?php else : ?>
            dayjs.locale('es');
        <?php endif; ?>
        
        let currentWalletBalance = <?php echo json_encode($wallet_balance); ?>;
    </script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        #i360-app-root form label,
        #i360-app-root #createModal label,
        #i360-app-root #editModal label {
            display: block !important;
            color: #475569 !important; 
            font-size: 12px !important;
            font-weight: 700 !important;
            font-family: 'Inter', sans-serif !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: static !important;
            width: auto !important;
            height: auto !important;
            clip: auto !important;
            clip-path: none !important;
            overflow: visible !important;
            margin-bottom: 5px !important;
            text-transform: none !important;
            text-indent: 0 !important;
            line-height: 1.5 !important;
        }

        #i360-app-root input, 
        #i360-app-root select,
        #createModal input,
        #createModal select {
            color: #1e293b !important;
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            -webkit-text-fill-color: #1e293b !important; 
            padding: 10px !important;
        }

        #i360-app-root h2, 
        #i360-app-root h3 {
            color: #0f172a !important;
        }
    </style>

    <aside class="w-full md:w-[220px] lg:w-[260px] bg-slate-900 text-slate-300 flex flex-col shrink-0 z-20 font-sans md:rounded-l-xl">
        <div class="p-5 flex items-center gap-3 text-white border-b border-slate-800 bg-slate-900 h-16 shrink-0 sticky top-0 z-30">
            <div class="w-8 h-8 rounded-lg bg-white p-1 flex items-center justify-center flex-shrink-0">
                <img src="https://botky.chat/wp-content/uploads/2025/03/bimi-svg-tiny-12-ps.svg" alt="Botky" class="w-full h-full object-contain">
            </div>
            <span class="font-bold text-base tracking-tight truncate"><?php echo $textos['partner_admin']; ?></span>
        </div>
        
        <div class="md:sticky md:top-16 z-20 bg-slate-900 w-full flex-1 flex flex-col h-full">
            <div class="px-5 py-4 bg-slate-800/50 border-b border-slate-800 mb-2 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-white border border-slate-600 shrink-0 shadow-sm">
                        <?php echo esc_html(strtoupper(substr($current_user->user_login, 0, 2))); ?>
                    </div>
                    <div class="overflow-hidden min-w-0">
                        <p class="text-xs font-bold text-white truncate"><?php echo esc_html($current_user->user_login); ?></p>
                        <p class="text-[10px] text-slate-500 truncate mt-0.5">
                            <?php echo $has_api_key ? '<span class="text-green-400 flex items-center gap-1">● ' . $textos['connected'] . '</span>' : '<span class="text-red-400 flex items-center gap-1">● ' . $textos['no_api_key'] . '</span>'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <nav class="p-3 flex-1 flex flex-col">
                <div class="space-y-1.5">
                    <div class="text-[10px] font-bold text-slate-500 uppercase px-3 mb-2 tracking-wider"><?php echo $textos['management']; ?></div>
                    <a href="#" onclick="switchView('workspaces'); return false;" id="nav_workspaces" class="nav-item flex items-center gap-3 px-3 py-2.5 bg-indigo-600/10 text-indigo-400 rounded-lg font-medium border border-indigo-600/20 transition-all no-underline text-sm"><i data-lucide="server" class="w-4 h-4"></i><span><?php echo $textos['workspaces']; ?></span></a>
                    <a href="#" onclick="switchView('clients'); return false;" id="nav_clients" class="nav-item flex items-center gap-3 px-3 py-2.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition-all border border-transparent no-underline text-sm"><i data-lucide="users" class="w-4 h-4"></i><span><?php echo $textos['clients']; ?></span></a>
                    
                    <div class="mt-4 text-[10px] font-bold text-slate-500 uppercase px-3 mb-2 tracking-wider"><?php echo $textos['billing']; ?></div>
                    <a href="#" onclick="switchView('recargas'); return false;" id="nav_recargas" class="nav-item flex items-center gap-3 px-3 py-2.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition-all border border-transparent no-underline text-sm"><i data-lucide="credit-card" class="w-4 h-4"></i><span><?php echo $textos['topups']; ?></span></a>
                </div>
                
                <div class="mt-auto pt-4 space-y-1.5 border-t border-slate-800">
                    <a href="#" onclick="switchView('settings'); return false;" id="nav_settings" class="nav-item flex items-center gap-3 px-3 py-2.5 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg transition-all border border-transparent no-underline text-sm"><i data-lucide="settings" class="w-4 h-4"></i><span><?php echo $textos['settings']; ?></span></a>
                    
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 hover:bg-red-500/10 text-slate-400 hover:text-red-400 rounded-lg transition-all border border-transparent no-underline text-sm">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span><?php echo $textos['logout']; ?></span>
                    </a>
                </div>
            </nav>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 relative z-10 bg-slate-50 font-sans md:rounded-r-xl">
        
        <div id="view_workspaces" class="flex flex-col w-full h-full">
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-sm gap-4 z-10 md:rounded-tr-xl sticky top-0 md:static">
                <div class="flex-1 flex items-center gap-4 min-w-0">
                    <h1 class="text-lg font-bold text-slate-800 m-0 leading-none truncate hidden sm:block"><?php echo $textos['spaces']; ?></h1>
                    
                    <div class="flex items-center gap-3 w-full max-w-lg">
                        <div class="relative w-full">
                            <i data-lucide="search" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 z-10"></i>
                            <input type="text" id="searchInput" placeholder="<?php echo $textos['search_id']; ?>" class="w-full pl-4 pr-10 py-2 bg-slate-100 border border-transparent focus:bg-white focus:border-indigo-300 rounded-lg text-sm outline-none transition-all placeholder-slate-400 relative z-0" onkeyup="if(event.key === 'Enter') fetchWorkspaces(1)">
                        </div>

                        <div class="flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-100 px-3 py-1.5 rounded-lg shrink-0 whitespace-nowrap shadow-sm" title="Tu saldo disponible">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                            <div class="flex flex-col leading-none">
                                <span class="text-[9px] font-bold text-emerald-600/70 uppercase"><?php echo $textos['balance']; ?></span>
                                <span class="text-sm font-bold">$<span id="wallet-display"><?php echo number_format($wallet_balance, 2); ?></span></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 shrink-0">
                    <button onclick="fetchWorkspaces(1)" class="px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 flex items-center gap-2 transition-all cursor-pointer"><i data-lucide="refresh-cw" class="w-3.5 h-3.5" id="refreshIcon"></i></button>
                    <button onclick="openCreateModal()" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all cursor-pointer"><i data-lucide="plus" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline"><?php echo $textos['new']; ?></span></button>
                </div>
            </header>
            
            <div class="flex-1 p-4 md:p-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col relative min-h-[300px]">
                    <div class="flex-1">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-bold tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5 w-16 bg-slate-50 whitespace-nowrap"><?php echo $textos['id']; ?></th>
                                    <th class="px-6 py-3.5 bg-slate-50"><?php echo $textos['workspace']; ?></th>
                                    <th class="px-6 py-3.5 bg-slate-50"><?php echo $textos['plan']; ?></th>
                                    <th class="px-6 py-3.5 w-32 bg-slate-50"><?php echo $textos['usage']; ?></th>
                                    <th class="px-6 py-3.5 text-center bg-slate-50"><?php echo $textos['pts']; ?></th>
                                    <th class="px-6 py-3.5 text-center bg-slate-50"><?php echo $textos['addons']; ?></th>
                                    <th class="px-6 py-3.5 text-right bg-slate-50"></th>
                                </tr>
                            </thead>
                            <tbody id="tableBody" class="divide-y divide-slate-100 text-sm"></tbody>
                        </table>
                        <div id="emptyState" class="hidden flex flex-col items-center justify-center p-12 text-center bg-white">
                            <div class="bg-slate-100 p-4 rounded-full mb-4"><i data-lucide="inbox" class="w-8 h-8 text-slate-400"></i></div>
                            <h3 class="text-lg font-bold text-slate-700 m-0"><?php echo $textos['no_data']; ?></h3>
                            <p class="text-slate-500 text-sm max-w-sm mt-2 mb-6"><?php echo $textos['no_workspaces']; ?></p>
                        </div>
                    </div>
                    <div id="paginationContainer" class="px-6 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between hidden shrink-0 rounded-b-xl">
                        <span class="text-xs text-slate-500 font-medium" id="paginationInfo"><?php echo $textos['loading']; ?></span>
                        <div class="flex items-center gap-1" id="paginationControls"></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="view_clients" class="hidden flex flex-col w-full h-full">
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-sm gap-4 z-10 md:rounded-tr-xl sticky top-0 md:static">
                <div class="flex-1 flex items-center gap-4 min-w-0">
                    <h1 class="text-lg font-bold text-slate-800 m-0 leading-none truncate hidden sm:block"><?php echo $textos['clients']; ?></h1>
                    <div class="flex items-center gap-3 w-full max-w-lg">
                        <div class="relative w-full">
                            <i data-lucide="search" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 z-10"></i>
                            <input type="text" id="searchClientInput" placeholder="<?php echo $textos['search_clients']; ?>" class="w-full pl-4 pr-10 py-2 bg-slate-100 border border-transparent focus:bg-white focus:border-indigo-300 rounded-lg text-sm outline-none transition-all placeholder-slate-400 relative z-0" onkeyup="if(event.key === 'Enter') fetchClients(1)">
                        </div>
                        <div class="flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-100 px-3 py-1.5 rounded-lg shrink-0 whitespace-nowrap shadow-sm">
                            <i data-lucide="wallet" class="w-4 h-4"></i>
                            <div class="flex flex-col leading-none">
                                <span class="text-[9px] font-bold text-emerald-600/70 uppercase"><?php echo $textos['balance']; ?></span>
                                <span class="text-sm font-bold">$<?php echo number_format($wallet_balance, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2 shrink-0">
                    <button onclick="fetchClients(1)" class="px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 flex items-center gap-2 transition-all cursor-pointer"><i data-lucide="refresh-cw" class="w-3.5 h-3.5" id="refreshClientIcon"></i></button>
                </div>
            </header>
            
            <div class="flex-1 p-4 md:p-6">
                  <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col relative min-h-[300px]">
                    <div class="flex-1">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-bold tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5 w-16 bg-slate-50 whitespace-nowrap"><?php echo $textos['id']; ?></th>
                                    <th class="px-6 py-3.5 bg-slate-50"><?php echo $textos['client']; ?></th>
                                    <th class="px-6 py-3.5 bg-slate-50"><?php echo $textos['contact']; ?></th>
                                    <th class="px-6 py-3.5 bg-slate-50"><?php echo $textos['teams']; ?></th>
                                    <th class="px-6 py-3.5 text-center bg-slate-50"><?php echo $textos['total']; ?></th>
                                </tr>
                            </thead>
                            <tbody id="tableBodyClients" class="divide-y divide-slate-100 text-sm"></tbody>
                        </table>
                        <div id="emptyStateClients" class="hidden flex flex-col items-center justify-center p-12 text-center bg-white">
                            <div class="bg-slate-100 p-4 rounded-full mb-4"><i data-lucide="users" class="w-8 h-8 text-slate-400"></i></div>
                            <h3 class="text-lg font-bold text-slate-700 m-0"><?php echo $textos['no_data']; ?></h3>
                            <p class="text-slate-500 text-sm max-w-sm mt-2 mb-6"><?php echo $textos['no_clients']; ?></p>
                        </div>
                    </div>
                      <div id="paginationContainerClients" class="px-6 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between hidden shrink-0 rounded-b-xl">
                        <span class="text-xs text-slate-500 font-medium" id="paginationInfoClients"><?php echo $textos['loading']; ?></span>
                        <div class="flex items-center gap-1" id="paginationControlsClients"></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="view_recargas" class="hidden flex flex-col w-full h-full">
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-sm z-10 md:rounded-tr-xl">
                <div><h1 class="text-lg font-bold text-slate-800 m-0 leading-none"><?php echo $textos['topups']; ?></h1><p class="text-xs text-slate-500 mt-1 m-0"><?php echo $textos['manage_balance']; ?></p></div>
                <div class="flex items-center gap-2 bg-emerald-50 text-emerald-700 border border-emerald-100 px-3 py-1.5 rounded-lg shrink-0 whitespace-nowrap shadow-sm">
                    <i data-lucide="wallet" class="w-4 h-4"></i>
                    <div class="flex flex-col leading-none">
                        <span class="text-[9px] font-bold text-emerald-600/70 uppercase"><?php echo $textos['current_balance']; ?></span>
                        <span class="text-sm font-bold">$<?php echo number_format($wallet_balance, 2); ?></span>
                    </div>
                </div>
            </header>
            <div class="flex-1 p-8">
                <div class="max-w-2xl mx-auto space-y-6">
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 flex gap-4">
                        <div class="p-3 bg-white rounded-lg shadow-sm text-indigo-600 h-fit"><i data-lucide="info" class="w-6 h-6"></i></div>
                        <div>
                            <h3 class="font-bold text-indigo-900 m-0 text-base"><?php echo $textos['how_topups_work']; ?></h3>
                            <p class="text-indigo-800/80 text-sm mt-1 mb-0"><?php echo $textos['topups_desc']; ?></p>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm text-center">
                        <h2 class="text-lg font-bold text-slate-800 mb-6"><?php echo $textos['make_topup']; ?></h2>
                        
                        <stripe-buy-button
                          buy-button-id="buy_btn_1SrUaLJ54QwQualSkYN8mhue"
                          publishable-key="pk_live_51QoT2uJ54QwQualSNqrZKR7calzycODnIhLc0PUawPhNvi8GqB2yf7Lh8wm9tc74GVPzPSPqrANe8Fznww4CTiIv00Ijx1SGGj"
                          client-reference-id="<?php echo get_current_user_id(); ?>"
                        >
                        </stripe-buy-button>

                        <p class="text-xs text-slate-400 mt-6">
                            <i data-lucide="lock" class="w-3 h-3 inline-block align-middle mr-1"></i> 
                            <?php echo $textos['secure_payment']; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div id="view_settings" class="hidden flex flex-col w-full h-full">
            <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-sm z-10 md:rounded-tr-xl">
                <div><h1 class="text-lg font-bold text-slate-800 m-0 leading-none"><?php echo $textos['settings']; ?></h1><p class="text-xs text-slate-500 mt-1 m-0"><?php echo $textos['connection']; ?></p></div>
            </header>
            <div class="flex-1 p-8">
                <div class="max-w-xl mx-auto bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
                    <div class="mb-6 pb-6 border-b border-slate-100"><h2 class="text-lg font-bold text-slate-800 m-0"><?php echo $textos['api_credentials']; ?></h2><p class="text-sm text-slate-500 mt-1 m-0"><?php echo $textos['enter_token']; ?></p></div>
                    <div class="space-y-4">
                        <div>
                            <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;">API Key</label>
                            <input type="password" id="settings_api_key" value="<?php echo esc_attr($saved_api_key); ?>" placeholder="ej: pk_live_..." class="w-full p-3 border border-slate-300 rounded-lg text-sm font-mono outline-none focus:border-indigo-500 transition-colors" style="color:#1e293b !important; background:#ffffff !important;">
                        </div>
                        <div class="flex justify-end pt-2"><button onclick="saveWPSettings()" id="btnSaveSettings" class="bg-slate-900 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-slate-800 shadow-lg flex items-center gap-2 cursor-pointer"><i data-lucide="save" class="w-4 h-4"></i> <?php echo $textos['save']; ?></button></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="editModal" class="fixed inset-0 z-[9999] hidden font-sans" style="position:fixed; top:0; left:0; width:100%; height:100%;">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div class="absolute right-0 top-0 h-full w-[600px] bg-white shadow-2xl flex flex-col transform transition-transform duration-300 translate-x-full" id="modalPanel">
            
            <div id="modalLoading" class="hidden absolute inset-0 bg-white/90 z-50 flex flex-col items-center justify-center">
                <div class="loading-spin mb-3"><i data-lucide="loader" class="w-8 h-8 text-indigo-600"></i></div>
                <span class="text-sm font-bold text-slate-500"><?php echo $textos['loading_data']; ?></span>
            </div>

            <div class="px-8 pt-12 pb-6 border-b border-slate-100 bg-white z-10 flex justify-between items-start mb-2 shrink-0">
                <div><h3 class="font-bold text-xl text-slate-800 m-0" id="modalTitle"><?php echo $textos['configure']; ?></h3><div class="flex items-center gap-2 mt-1"><span class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded" id="modalId">ID</span><span class="text-xs font-bold text-slate-500 flex items-center gap-1"><i data-lucide="coins" class="w-3 h-3 text-amber-500"></i><span id="modalPoints">0 pts</span></span></div></div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 cursor-pointer"><i data-lucide="x" class="w-6 h-6"></i></button>
            </div>
            
            <div class="flex-1 overflow-y-auto px-8 py-6 space-y-8 custom-scrollbar bg-slate-50/50 relative">
                <section class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#94a3b8 !important; font-size:12px !important; font-weight:bold !important; text-transform:uppercase;"><?php echo $textos['status']; ?></label>
                        <div class="flex items-center gap-2"><span class="text-xs text-slate-500 font-medium" id="statusLabel"><?php echo $textos['active']; ?></span><label class="relative inline-flex items-center cursor-pointer" style="display:inline-block !important; visibility:visible !important; width:auto !important; margin:0 !important;"><input type="checkbox" id="statusToggle" class="sr-only peer"><div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div></label></div>
                    </div>
                    <div class="space-y-2">
                        <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important;"><?php echo $textos['change_plan']; ?></label>
                        <select id="planSelector" class="w-full p-3 border border-slate-300 rounded-lg text-sm bg-slate-50 font-semibold text-slate-700 outline-none" style="color:#1e293b !important; background:#f8fafc !important;"><option value="" disabled selected><?php echo $textos['loading']; ?></option></select>
                    </div>
                </section>
                <section class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#94a3b8 !important; font-size:12px !important; font-weight:bold !important; text-transform:uppercase;"><?php echo $textos['points_management']; ?></label>
                        <span class="text-xs font-bold text-amber-500 flex items-center gap-1"><i data-lucide="coins" class="w-4 h-4"></i> <?php echo $textos['available']; ?>: <span id="currentPointsDisplay" class="text-base text-slate-800">0</span></span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-xl">
                        <div class="flex items-center gap-4"><div class="p-2.5 bg-amber-50 text-amber-600 rounded-lg"><i data-lucide="wallet" class="w-5 h-5"></i></div><div><p class="text-sm font-bold text-slate-800 m-0"><?php echo $textos['adjust_balance']; ?></p><p class="text-xs text-slate-500 m-0"><?php echo $textos['adjust_desc']; ?></p></div></div>
                        <div class="flex items-center gap-2"><input type="number" id="pointsInput" class="w-32 p-2 border border-slate-300 rounded-lg text-sm text-center font-bold outline-none" placeholder="0" onchange="updatePointsDelta()" style="color:#1e293b !important; background:#ffffff !important;"></div>
                    </div>
                </section>
                <section>
                    <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#94a3b8 !important; font-size:12px !important; font-weight:bold !important; text-transform:uppercase; margin-bottom:12px !important;"><?php echo $textos['available_addons']; ?></label>
                    <div class="space-y-3" id="addonsContainer"><div class="text-center py-4 text-slate-400 text-xs"><?php echo $textos['loading_addons']; ?></div></div>
                </section>
            </div>
            <div class="px-8 py-5 border-t border-slate-200 bg-slate-50 z-10 flex gap-4 items-center justify-end"><button onclick="closeModal()" class="px-4 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg hover:bg-slate-100 text-sm cursor-pointer"><?php echo $textos['cancel']; ?></button><button onclick="confirmSaveChanges()" class="px-4 py-2.5 bg-slate-900 text-white font-bold rounded-lg hover:bg-slate-800 text-sm flex justify-center items-center gap-2 cursor-pointer"><i data-lucide="save" class="w-4 h-4"></i> <?php echo $textos['save_changes']; ?></button></div>
        </div>
    </div>

    <div id="confirmModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center font-sans">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative w-[400px] bg-white rounded-xl shadow-2xl p-6 overflow-hidden animate-fade-in-up">
            <div class="mb-4"><div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-3 text-amber-600"><i data-lucide="alert-triangle" class="w-6 h-6"></i></div><h3 class="text-lg font-bold text-slate-800 m-0"><?php echo $textos['confirm_changes']; ?></h3><p class="text-sm text-slate-500 mt-1 m-0"><?php echo $textos['confirm_desc']; ?></p></div>
            <div id="confirmChangesList" class="bg-slate-50 border border-slate-100 rounded-lg p-3 text-xs text-slate-600 space-y-2 mb-6 max-h-40 overflow-y-auto"></div>
            <div class="flex gap-3"><button onclick="document.getElementById('confirmModal').classList.add('hidden')" class="flex-1 py-2 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg text-sm cursor-pointer"><?php echo $textos['cancel']; ?></button><button onclick="executeSaveChanges()" class="flex-1 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 text-sm cursor-pointer"><?php echo $textos['confirm_and_save']; ?></button></div>
        </div>
    </div>
    
    <div id="noBalanceModal" class="fixed inset-0 z-[10000] hidden flex items-center justify-center font-sans">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('noBalanceModal').classList.add('hidden')"></div>
        <div class="relative w-[400px] bg-white rounded-xl shadow-2xl p-6 overflow-hidden animate-fade-in-up text-center">
            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4 mx-auto text-red-500"><i data-lucide="ban" class="w-8 h-8"></i></div>
            <h3 class="text-xl font-bold text-slate-800 mb-2"><?php echo $textos['insufficient_balance']; ?></h3>
            <p class="text-slate-500 text-sm mb-6"><?php echo $textos['insufficient_desc_1']; ?> <b class="text-slate-800" id="noBalanceCost">$0.00</b><?php echo $textos['insufficient_desc_2']; ?> <b class="text-red-500" id="noBalanceCurrent">$0.00</b>.</p>
            <div class="flex gap-3"><button onclick="document.getElementById('noBalanceModal').classList.add('hidden')" class="flex-1 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg text-sm cursor-pointer hover:bg-slate-50"><?php echo $textos['cancel']; ?></button><button onclick="goToRecargas()" class="flex-1 py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 text-sm cursor-pointer shadow-lg shadow-indigo-200"><?php echo $textos['topup_now']; ?></button></div>
        </div>
    </div>

    <div id="createModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center font-sans">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeCreateModal()"></div>
        <div class="relative w-[700px] max-h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-fade-in-up">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50"><h3 class="font-bold text-lg text-slate-800 m-0"><?php echo $textos['new_workspace']; ?></h3><button onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600 cursor-pointer"><i data-lucide="x" class="w-5 h-5"></i></button></div>
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form id="createForm" class="space-y-5">
                     <div>
                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded uppercase tracking-wider mb-2 inline-block"><?php echo $textos['admin_user']; ?></span>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;"><?php echo $textos['name']; ?></label>
                                <input type="text" id="new_name" placeholder="<?php echo $textos['name']; ?>" class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                            </div>
                            <div class="space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;"><?php echo $textos['email']; ?></label>
                                <input type="email" id="new_email" placeholder="<?php echo $textos['email']; ?>" class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                            </div>
                            <div class="space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;"><?php echo $textos['password']; ?></label>
                                <input type="password" id="new_password" placeholder="********" class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                            </div>
                            <div class="space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;"><?php echo $textos['phone']; ?></label>
                                <input type="text" id="new_phone" placeholder="+123456789" class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                            </div>
                        </div>
                     </div>
                     <div class="pt-2 border-t border-slate-100">
                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded uppercase tracking-wider mb-2 inline-block"><?php echo $textos['settings']; ?></span>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;"><?php echo $textos['company_name']; ?></label>
                                <input type="text" id="new_team_name" placeholder="<?php echo $textos['company_name']; ?>" class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                            </div>
                            <div class="space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;"><?php echo $textos['language']; ?></label>
                                <select id="new_locale" class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none bg-white focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                                    <option value="es"><?php echo $textos['spanish']; ?></option>
                                    <option value="en"><?php echo $textos['english']; ?></option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;"><?php echo $textos['trial_days']; ?></label>
                                <input type="number" id="new_trial_days" value="14" placeholder="14" class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                            </div>
                            <div class="col-span-2 space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;">Template ID</label>
                                <input type="text" id="new_template_ns" placeholder="<?php echo $textos['optional']; ?>" class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                            </div>
                            <div class="col-span-2 space-y-1">
                                <label style="display:block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:bold !important; margin-bottom:4px !important;">OpenAI Key</label>
                                <input type="password" id="new_openai_key" placeholder="sk-..." class="w-full p-2.5 border border-slate-200 rounded-lg text-sm outline-none focus:border-indigo-500" style="color:#1e293b !important; background:#ffffff !important;">
                            </div>
                        </div>
                     </div>
                     <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="new_require_verification" checked class="w-4 h-4 text-indigo-600 rounded cursor-pointer">
                        <label style="display:inline-block !important; visibility:visible !important; opacity:1 !important; color:#475569 !important; font-size:12px !important; font-weight:normal !important; margin-bottom:0 !important; cursor:pointer;"><?php echo $textos['verify_email']; ?></label>
                     </div>
                </form>
            </div>
            <div class="p-5 border-t border-slate-200 bg-slate-50 flex justify-between gap-3"><div class="flex items-center gap-2 text-xs text-slate-400 font-mono bg-slate-50 px-2 rounded"></div><div class="flex gap-3"><button onclick="closeCreateModal()" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg text-sm cursor-pointer"><?php echo $textos['cancel']; ?></button><button type="button" onclick="submitCreateWorkspace()" id="btnCreateConfirm" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-lg text-sm flex items-center gap-2 cursor-pointer"><i data-lucide="plus-circle" class="w-4 h-4"></i> <?php echo $textos['create']; ?></button></div></div>
        </div>
    </div>
</div>
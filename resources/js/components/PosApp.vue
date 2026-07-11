<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import Dexie from 'dexie';

// 1. Initialize Dexie DB
const db = new Dexie("apf_pos_db");
db.version(1).stores({
    products: "id, name, sku, barcode, price, cost, category_id, status, stock_quantity",
    categories: "id, name, slug",
    customers: "id, name, phone, email, loyalty_points",
    pending_orders: "offline_id, branch_id, total_amount, synced_at"
});

// 2. Localization Dictionaries
const locales = {
    en: {
        search_placeholder: "Search product name, barcode scan, or SKU...",
        walkin_customer: "Walk-in Customer",
        cart_empty: "Cart is Empty",
        cart_instructions: "Click catalog products or scan barcodes to sell",
        subtotal: "Subtotal",
        discount: "Apply Discount",
        tax: "Tax (15%)",
        total_due: "Total Due",
        proceed_payment: "Proceed to Payment",
        payment_settlement: "Payment Settlement",
        select_payment: "Select Payment Method",
        cash_received: "Cash Received ($)",
        change_due: "Change Due",
        complete_sale: "Complete Transaction & Print Receipt",
        transaction_complete: "Transaction Complete",
        receipt_id: "Receipt ID",
        date: "Date",
        cashier: "Cashier",
        payment_method: "Paid Via",
        thank_you: "Thank You For Shopping!",
        print: "Print",
        done: "Done",
        no_discount: "No Discount (0%)",
        staff_discount: "Staff Discount (5%)",
        special_promo: "Special Promo (10%)",
        loyalty_member: "Loyalty Member (15%)",
        clearance_sale: "Clearance Sale (20%)",
        sync_queue: "Sync Offline Queue",
        syncing: "Syncing...",
        unsynced: "unsynced",
        online: "Online",
        offline: "Offline Mode",
        c_cash: "💵 Cash",
        c_card: "💳 Credit Card",
        c_mobile: "📱 Mobile Pay",
        c_loyalty: "🌟 Loyalty Points",
        back_office: "Back-office ➔"
    },
    bn: {
        search_placeholder: "পণ্য, বারকোড বা এসকেইউ (SKU) দিয়ে খুঁজুন...",
        walkin_customer: "সাধারণ ক্রেতা",
        cart_empty: "কার্ট খালি আছে",
        cart_instructions: "পণ্য নির্বাচন করুন বা বারকোড স্ক্যান করুন",
        subtotal: "উপ-মোট",
        discount: "ডিসকাউন্ট প্রয়োগ করুন",
        tax: "ভ্যাট (১৫%)",
        total_due: "মোট প্রদেয়",
        proceed_payment: "পেমেন্টে এগিয়ে যান",
        payment_settlement: "পেমেন্ট নিষ্পত্তি",
        select_payment: "পেমেন্ট পদ্ধতি নির্বাচন করুন",
        cash_received: "নগদ গ্রহণ ($)",
        change_due: "ফেরতযোগ্য টাকা",
        complete_sale: "লেনদেন সম্পন্ন করুন এবং রসিদ প্রিন্ট করুন",
        transaction_complete: "লেনদেন সফল হয়েছে",
        receipt_id: "রসিদ নম্বর",
        date: "তারিখ",
        cashier: "ক্যাশিয়ার",
        payment_method: "পেমেন্ট মাধ্যম",
        thank_you: "কেনাকাটার জন্য ধন্যবাদ!",
        print: "প্রিন্ট",
        done: "সম্পন্ন",
        no_discount: "কোন ছাড় নেই (0%)",
        staff_discount: "কর্মী ছাড় (5%)",
        special_promo: "বিশেষ প্রোমো (10%)",
        loyalty_member: "লয়্যালটি সদস্য (15%)",
        clearance_sale: "ক্লিয়ারেন্স সেল (20%)",
        sync_queue: "অফলাইন কিউ সিঙ্ক করুন",
        syncing: "সিঙ্ক হচ্ছে...",
        unsynced: "সিঙ্কহীন",
        online: "অনলাইন",
        offline: "অফলাইন মোড",
        c_cash: "💵 নগদ",
        c_card: "💳 ক্রেডিট কার্ড",
        c_mobile: "📱 মোবাইল পে",
        c_loyalty: "🌟 লয়্যালটি পয়েন্ট",
        back_office: "ব্যাক-অফিস ➔"
    }
};

// 3. App State Refs
const branchId = ref(null);
const cashierId = ref(null);
const cashierName = ref('');
const branchName = ref('');

const allProducts = ref([]);
const categories = ref([]);
const customers = ref([]);
const cart = ref([]);

const searchQuery = ref('');
const selectedCategory = ref('all');
const selectedCustomerId = ref('');

const taxRate = ref(0.15); // 15% VAT
const discountRate = ref(0.00);

const onlineStatus = ref(navigator.onLine);
const syncQueueCount = ref(0);
const isSyncing = ref(false);

const isCheckingOut = ref(false);
const checkoutPaymentMethod = ref('cash');
const checkoutCashReceived = ref('');
const showReceipt = ref(false);
const receiptOrder = ref(null);
const currentLocale = ref(localStorage.getItem('pos_language') || 'en');

// 4. Helper Translator
const t = (key) => {
    return locales[currentLocale.value][key] || key;
};

const toggleLanguage = () => {
    currentLocale.value = currentLocale.value === 'en' ? 'bn' : 'bn'; // wait, toggles between en/bn
    // Correct toggle logic:
    currentLocale.value = currentLocale.value === 'en' ? 'bn' : 'en';
    localStorage.setItem('pos_language', currentLocale.value);
};

// 5. Computed Properties
const filteredProducts = computed(() => {
    let result = allProducts.value;

    // Category filter
    if (selectedCategory.value !== 'all') {
        result = result.filter(p => p.category_id === parseInt(selectedCategory.value));
    }

    // Search filter
    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase().trim();
        result = result.filter(p => 
            p.name.toLowerCase().includes(query) || 
            p.sku.toLowerCase().includes(query) || 
            (p.barcode && p.barcode.includes(query))
        );
    }

    return result;
});

const selectedCustomer = computed(() => {
    if (!selectedCustomerId.value) return null;
    return customers.value.find(c => c.id === parseInt(selectedCustomerId.value)) || null;
});

const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const discountAmount = computed(() => {
    return subtotal.value * parseFloat(discountRate.value);
});

const taxAmount = computed(() => {
    return (subtotal.value - discountAmount.value) * taxRate.value;
});

const total = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value + taxAmount.value);
});

const changeDue = computed(() => {
    if (checkoutPaymentMethod.value !== 'cash') return 0;
    const cash = parseFloat(checkoutCashReceived.value) || 0;
    return Math.max(0, cash - total.value);
});

// 6. Methods
const loadLocalCache = async () => {
    categories.value = await db.categories.toArray();
    customers.value = await db.customers.toArray();
    allProducts.value = await db.products.toArray();
};

const updateSyncQueueCount = async () => {
    syncQueueCount.value = await db.pending_orders.count();
};

const addToCart = (product) => {
    const existing = cart.value.find(item => item.product_id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            price: product.price,
            cost: product.cost,
            quantity: 1,
            discount: 0
        });
    }
};

const updateQty = (productId, delta) => {
    const item = cart.value.find(i => i.product_id === productId);
    if (item) {
        item.quantity = Math.max(0.01, item.quantity + delta);
    }
};

const removeItem = (productId) => {
    cart.value = cart.value.filter(item => item.product_id !== productId);
};

const clearCart = () => {
    cart.value = [];
    selectedCustomerId.value = '';
    discountRate.value = 0.00;
};

const openCheckout = () => {
    if (cart.value.length === 0) return;
    checkoutPaymentMethod.value = 'cash';
    checkoutCashReceived.value = '';
    isCheckingOut.value = true;
};

const closeCheckout = () => {
    isCheckingOut.value = false;
};

const generateUUID = () => {
    return 'pos-sale-' + self.crypto.randomUUID();
};

const submitCheckout = async () => {
    if (checkoutPaymentMethod.value === 'cash') {
        const cash = parseFloat(checkoutCashReceived.value) || 0;
        if (cash < total.value) {
            alert('Cash received is less than the total amount.');
            return;
        }
    }

    const orderId = generateUUID();
    const orderTimestamp = new Date().toISOString();

    const order = {
        offline_id: orderId,
        branch_id: parseInt(branchId.value),
        user_id: parseInt(cashierId.value),
        customer_id: selectedCustomerId.value ? parseInt(selectedCustomerId.value) : null,
        subtotal: parseFloat(subtotal.value.toFixed(2)),
        tax_amount: parseFloat(taxAmount.value.toFixed(2)),
        discount_amount: parseFloat(discountAmount.value.toFixed(2)),
        total_amount: parseFloat(total.value.toFixed(2)),
        payment_method: checkoutPaymentMethod.value,
        payment_status: 'paid',
        status: 'completed',
        notes: '',
        created_at: orderTimestamp,
        items: cart.value.map(item => ({
            product_id: item.product_id,
            quantity: parseFloat(item.quantity.toFixed(2)),
            price: parseFloat(item.price.toFixed(2)),
            cost: parseFloat(item.cost.toFixed(2)),
            discount: parseFloat(item.discount.toFixed(2)),
            subtotal: parseFloat((item.price * item.quantity).toFixed(2))
        }))
    };

    // Save locally
    await db.pending_orders.add(order);

    // Deduct stock levels in local cache
    for (const item of order.items) {
        const localProd = await db.products.get(item.product_id);
        if (localProd) {
            localProd.stock_quantity = Math.max(0, localProd.stock_quantity - item.quantity);
            await db.products.put(localProd);
        }
    }

    clearCart();
    closeCheckout();

    await loadLocalCache();
    await updateSyncQueueCount();

    receiptOrder.value = order;
    showReceipt.value = true;

    if (onlineStatus.value) {
        syncData();
    }
};

const syncData = async () => {
    if (isSyncing.value) return;
    isSyncing.value = true;

    try {
        // Push pending orders
        const pending = await db.pending_orders.toArray();
        if (pending.length > 0) {
            console.log(`[Sync] Pushing ${pending.length} orders...`);
            const response = await fetch('/api/pos/sync-push', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ orders: pending })
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success && result.synced_offline_ids) {
                    for (const syncedId of result.synced_offline_ids) {
                        await db.pending_orders.delete(syncedId);
                    }
                }
            }
        }

        // Pull updates
        const lastSyncTime = localStorage.getItem('pos_last_sync_timestamp') || '';
        const pullUrl = `/api/pos/sync-pull?branch_id=${branchId.value}&last_sync=${encodeURIComponent(lastSyncTime)}`;
        const pullResponse = await fetch(pullUrl);

        if (pullResponse.ok) {
            const data = await pullResponse.json();
            if (data.categories.length > 0) {
                for (const cat of data.categories) await db.categories.put(cat);
            }
            if (data.customers.length > 0) {
                for (const cust of data.customers) await db.customers.put(cust);
            }
            if (data.products.length > 0) {
                for (const prod of data.products) await db.products.put(prod);
            }

            localStorage.setItem('pos_last_sync_timestamp', data.server_timestamp);
        }

        await loadLocalCache();
        await updateSyncQueueCount();

    } catch (err) {
        console.error('[Sync] Error: ', err);
    } finally {
        isSyncing.value = false;
    }
};

const printReceipt = () => {
    const printContent = document.getElementById('receiptPrintSection').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print Receipt</title>');
    printWindow.document.write('<style>body{font-family:monospace;padding:20px;color:black;} .receipt-divider{border-top:1px dashed black;margin:10px 0;} .receipt-row{display:flex;justify-content:space-between;margin-bottom:4px;} .receipt-header{text-align:center;} .receipt-footer{text-align:center;margin-top:20px;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
};

const handleBarcodeScan = async (barcode) => {
    const product = await db.products.where('barcode').equals(barcode).first();
    if (product) {
        addToCart(product);
    } else {
        alert(`Barcode [${barcode}] not found in local catalog.`);
    }
};

// 7. USB Keyboard Barcode Emulator hooks
const barcodeScannedListener = async (e) => {
    await handleBarcodeScan(e.detail);
};

onMounted(async () => {
    // Read properties from DOM element attributes
    const rootEl = document.getElementById('app');
    branchId.value = rootEl.getAttribute('data-branch-id');
    cashierId.value = rootEl.getAttribute('data-cashier-id');
    cashierName.value = rootEl.getAttribute('data-cashier-name');
    branchName.value = rootEl.getAttribute('data-branch-name');

    window.addEventListener('online', () => { onlineStatus.value = true; syncData(); });
    window.addEventListener('offline', () => { onlineStatus.value = false; });
    window.addEventListener('barcode-scanned', barcodeScannedListener);

    await loadLocalCache();
    await updateSyncQueueCount();

    if (onlineStatus.value) {
        await syncData();
    }
});

onUnmounted(() => {
    window.removeEventListener('barcode-scanned', barcodeScannedListener);
});
</script>

<template>
    <div class="flex flex-col h-screen text-slate-50 font-sans overflow-hidden bg-slate-900">
        <!-- Header Section -->
        <header class="h-[60px] bg-slate-800 border-b border-slate-700 flex justify-between items-center px-5 z-10">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-400 to-emerald-400 bg-clip-text text-transparent">APF POS</h1>
                <span class="bg-indigo-500/10 text-indigo-300 text-xs px-2 py-1 rounded-md font-semibold border border-indigo-500/30" x-text="branchName">{{ branchName }}</span>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Language Toggler -->
                <button @click="toggleLanguage" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded text-xs font-semibold border border-slate-600 transition">
                    {{ currentLocale === 'en' ? 'বাংলা' : 'English' }}
                </button>

                <!-- Sync Button -->
                <button @click="syncData" class="px-3 py-1.5 bg-transparent border border-slate-700 hover:border-slate-500 text-slate-100 rounded-md text-xs font-semibold cursor-pointer flex items-center gap-1.5 transition" :disabled="isSyncing || !onlineStatus">
                    <span>{{ isSyncing ? t('syncing') : t('sync_queue') }}</span>
                    <svg class="w-3.5 h-3.5" :class="isSyncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 15.89M9.582 9l-.582-.5M15 11l.5.5"></path>
                    </svg>
                </button>
                
                <!-- Status Display -->
                <div class="flex items-center gap-2 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full inline-block" :class="onlineStatus ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : 'bg-rose-500 shadow-[0_0_8px_#ef4444]'"></span>
                    <span>{{ onlineStatus ? t('online') : t('offline') }}</span>
                    <span v-if="syncQueueCount > 0" class="bg-amber-500 text-black text-xs px-2 py-0.5 rounded font-bold">{{ syncQueueCount }} {{ t('unsynced') }}</span>
                </div>
                
                <div class="text-xs text-slate-400">
                    <span>{{ t('cashier') }}: <strong class="text-slate-100">{{ cashierName }}</strong></span>
                    <a href="/dashboard" class="text-indigo-400 hover:text-indigo-300 font-semibold ml-4.5 no-underline">{{ t('back_office') }}</a>
                </div>
            </div>
        </header>

        <!-- Main Layout Area -->
        <div class="flex-1 flex overflow-hidden h-[calc(100vh-60px)]">
            
            <!-- Left panel: Checkout & Cart -->
            <div class="w-[420px] bg-slate-950 border-r border-slate-700 flex flex-col h-full">
                <!-- Customer Selection -->
                <div class="p-4 border-b border-slate-700">
                    <select v-model="selectedCustomerId" class="w-full bg-slate-800 border border-slate-700 text-slate-100 p-2.5 rounded-lg text-sm outline-none cursor-pointer focus:border-indigo-500">
                        <option value="">{{ t('walkin_customer') }}</option>
                        <option v-for="cust in customers" :key="cust.id" :value="cust.id">
                            {{ cust.name }} (🌟 {{ cust.loyalty_points }} pts)
                        </option>
                    </select>
                </div>
                
                <!-- Cart Scroll List -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div v-if="cart.length === 0" class="flex flex-col items-center justify-center h-full text-slate-400 gap-3 text-center">
                        <svg class="w-12 h-12 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <p class="text-sm font-semibold">
                            {{ t('cart_empty') }}<br>
                            <span class="text-xs font-normal text-slate-500">{{ t('cart_instructions') }}</span>
                        </p>
                    </div>
                    
                    <div v-for="item in cart" :key="item.product_id" class="bg-slate-800 border border-slate-700 rounded-lg p-3 flex justify-between items-center gap-3">
                        <div class="flex-1 pr-3">
                            <div class="text-sm font-semibold text-slate-100">{{ item.name }}</div>
                            <div class="text-xs text-slate-400 mt-0.5 flex gap-2">
                                <span>${{ item.price.toFixed(2) }}</span>
                                <span class="font-mono">[{{ item.sku }}]</span>
                            </div>
                        </div>
                        
                        <!-- Quantities -->
                        <div class="flex items-center gap-2">
                            <button @click="updateQty(item.product_id, -1)" class="w-7 h-7 rounded bg-slate-700 hover:bg-slate-600 border border-slate-600 flex items-center justify-center font-bold text-sm select-none cursor-pointer">-</button>
                            <span class="w-8 text-center text-sm font-semibold">{{ item.quantity }}</span>
                            <button @click="updateQty(item.product_id, 1)" class="w-7 h-7 rounded bg-slate-700 hover:bg-slate-600 border border-slate-600 flex items-center justify-center font-bold text-sm select-none cursor-pointer">+</button>
                        </div>
                        
                        <div class="text-sm font-bold w-16 text-right">${{ (item.price * item.quantity).toFixed(2) }}</div>
                        
                        <button @click="removeItem(item.product_id)" class="text-rose-500 hover:text-rose-400 font-bold text-lg cursor-pointer select-none">&times;</button>
                    </div>
                </div>
                
                <!-- Pricing Summary Panel -->
                <div class="bg-slate-800 border-t border-slate-700 p-5 flex flex-col gap-3">
                    <div class="flex justify-between text-sm text-slate-400">
                        <span>{{ t('subtotal') }}:</span>
                        <span>${{ subtotal.toFixed(2) }}</span>
                    </div>
                    
                    <!-- Discount Rate select -->
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-sm text-slate-400">{{ t('discount') }}:</span>
                        <select v-model="discountRate" class="bg-slate-900 border border-slate-700 text-slate-100 text-xs p-1.5 rounded outline-none focus:border-indigo-500">
                            <option value="0.00">{{ t('no_discount') }}</option>
                            <option value="0.05">{{ t('staff_discount') }}</option>
                            <option value="0.10">{{ t('special_promo') }}</option>
                            <option value="0.15">{{ t('loyalty_member') }}</option>
                            <option value="0.20">{{ t('clearance_sale') }}</option>
                        </select>
                    </div>
                    
                    <div v-if="discountAmount > 0" class="flex justify-between text-sm text-rose-400 font-medium">
                        <span>Discount Applied:</span>
                        <span>-${{ discountAmount.toFixed(2) }}</span>
                    </div>
                    
                    <div class="flex justify-between text-sm text-slate-400">
                        <span>{{ t('tax') }}:</span>
                        <span>${{ taxAmount.toFixed(2) }}</span>
                    </div>
                    
                    <div class="flex justify-between text-base font-extrabold border-t border-slate-700 pt-3 text-slate-100">
                        <span>{{ t('total_due') }}:</span>
                        <span>${{ total.toFixed(2) }}</span>
                    </div>
                    
                    <button @click="openCheckout" class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-700 disabled:text-slate-500 disabled:cursor-not-allowed text-white py-3.5 rounded-lg font-bold text-sm cursor-pointer select-none transition mt-2 shadow-[0_4px_12px_rgba(16,185,129,0.2)]" :disabled="cart.length === 0">
                        {{ t('proceed_payment') }} (${{ total.toFixed(2) }})
                    </button>
                </div>
            </div>
            
            <!-- Right panel: Catalog -->
            <div class="flex-1 flex flex-col h-full bg-slate-900">
                <!-- Search bar & Categories -->
                <div class="bg-slate-800 border-b border-slate-700 p-4 flex flex-col gap-3">
                    <div class="relative w-full">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" v-model="searchQuery" :placeholder="t('search_placeholder')" class="w-full bg-slate-950 border border-slate-700 text-slate-100 py-3 pl-10 pr-4 rounded-lg text-sm outline-none focus:border-indigo-500 transition" />
                    </div>
                    
                    <!-- Categories -->
                    <div class="flex gap-2 overflow-x-auto pb-1 select-none">
                        <button @click="selectedCategory = 'all'" class="px-4 py-2 rounded-md text-xs font-bold border whitespace-nowrap cursor-pointer transition" :class="selectedCategory === 'all' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-slate-700/50 border-slate-700 text-slate-400 hover:bg-slate-700 hover:text-slate-100'">
                            All Products
                        </button>
                        <button v-for="cat in categories" :key="cat.id" @click="selectedCategory = cat.id" class="px-4 py-2 rounded-md text-xs font-bold border whitespace-nowrap cursor-pointer transition" :class="selectedCategory === cat.id ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-slate-700/50 border-slate-700 text-slate-400 hover:bg-slate-700 hover:text-slate-100'">
                            {{ cat.name }}
                        </button>
                    </div>
                </div>
                
                <!-- Catalog Grid -->
                <div class="flex-1 overflow-y-auto p-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <div v-for="prod in filteredProducts" :key="prod.id" @click="addToCart(prod)" class="bg-slate-800 border border-slate-700 hover:border-indigo-500 rounded-lg p-3.5 flex flex-col justify-between cursor-pointer select-none transition hover:-translate-y-0.5 shadow-md">
                            <div>
                                <div class="text-sm font-bold text-slate-100 line-clamp-2 h-10 leading-snug">{{ prod.name }}</div>
                                <div class="text-[10px] font-mono text-slate-500 mt-1">{{ prod.sku }}</div>
                            </div>
                            
                            <div class="flex justify-between items-end mt-4">
                                <div class="text-sm font-extrabold text-slate-200">${{ prod.price.toFixed(2) }}</div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded" :class="prod.stock_quantity <= 0 ? 'bg-rose-500/10 text-rose-400' : (prod.stock_quantity < 20 ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400')">
                                    {{ prod.stock_quantity <= 0 ? 'Out of stock' : prod.stock_quantity.toFixed(0) + ' left' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout Dialog Overlay -->
        <div v-if="isCheckingOut" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50 animate-[fadeIn_0.12s_ease-out]">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[480px] overflow-hidden shadow-2xl animate-[scaleUp_0.15s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">{{ t('payment_settlement') }}</h3>
                    <button @click="closeCheckout" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                
                <div class="p-5 flex flex-col gap-4">
                    <div class="flex justify-between items-center bg-slate-900/50 p-3 rounded-lg text-base font-bold text-slate-100">
                        <span>{{ t('total_due') }}:</span>
                        <span>${{ total.toFixed(2) }}</span>
                    </div>
                    
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs font-semibold text-slate-400">{{ t('select_payment') }}</span>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button @click="checkoutPaymentMethod = 'cash'" class="bg-slate-900 border-2 py-3.5 rounded-lg text-xs font-bold cursor-pointer text-center transition" :class="checkoutPaymentMethod === 'cash' ? 'border-indigo-500 text-indigo-300 bg-indigo-500/10' : 'border-slate-700 text-slate-300 hover:border-slate-600'">{{ t('c_cash') }}</button>
                            <button @click="checkoutPaymentMethod = 'card'" class="bg-slate-900 border-2 py-3.5 rounded-lg text-xs font-bold cursor-pointer text-center transition" :class="checkoutPaymentMethod === 'card' ? 'border-indigo-500 text-indigo-300 bg-indigo-500/10' : 'border-slate-700 text-slate-300 hover:border-slate-600'">{{ t('c_card') }}</button>
                            <button @click="checkoutPaymentMethod = 'mobile'" class="bg-slate-900 border-2 py-3.5 rounded-lg text-xs font-bold cursor-pointer text-center transition" :class="checkoutPaymentMethod === 'mobile' ? 'border-indigo-500 text-indigo-300 bg-indigo-500/10' : 'border-slate-700 text-slate-300 hover:border-slate-600'">{{ t('c_mobile') }}</button>
                            <button @click="checkoutPaymentMethod = 'loyalty'" class="bg-slate-900 border-2 py-3.5 rounded-lg text-xs font-bold cursor-pointer text-center transition" :class="checkoutPaymentMethod === 'loyalty' ? 'border-indigo-500 text-indigo-300 bg-indigo-500/10' : 'border-slate-700 text-slate-300 hover:border-slate-600'">{{ t('c_loyalty') }}</button>
                        </div>
                    </div>
                    
                    <div v-if="checkoutPaymentMethod === 'cash'" class="flex flex-col gap-1.5">
                        <label for="cashInput" class="text-xs font-semibold text-slate-400">{{ t('cash_received') }}</label>
                        <input type="number" id="cashInput" v-model="checkoutCashReceived" placeholder="Enter amount" class="w-full bg-slate-900 border border-slate-700 text-slate-100 p-3 rounded-lg text-lg font-bold outline-none text-right font-mono focus:border-indigo-500" step="0.01" min="0">
                    </div>
                    
                    <div v-if="checkoutPaymentMethod === 'cash' && changeDue > 0" class="p-3 rounded-lg bg-emerald-500/10 border border-dashed border-emerald-500 text-emerald-400 font-bold flex justify-between items-center text-base">
                        <span>{{ t('change_due') }}:</span>
                        <span>${{ changeDue.toFixed(2) }}</span>
                    </div>
                    
                    <button @click="submitCheckout" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3.5 rounded-lg text-sm transition mt-2 cursor-pointer shadow-md">
                        {{ t('complete_sale') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Receipt Print Dialog Overlay -->
        <div v-if="showReceipt" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50 animate-[fadeIn_0.12s_ease-out]">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[360px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">{{ t('transaction_complete') }}</h3>
                    <button @click="showReceipt = false" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                
                <div class="bg-white text-black p-5 max-h-[70vh] overflow-y-auto" id="receiptPrintSection">
                    <div class="font-mono text-xs max-w-[300px] mx-auto text-black leading-relaxed">
                        <div class="text-center font-bold text-sm mb-1">APF POS SYSTEM</div>
                        <div class="text-center">{{ branchName }}</div>
                        <div class="text-center">Transaction Receipt</div>
                        
                        <div class="border-t border-dashed border-black my-2"></div>
                        
                        <div class="flex justify-between">
                            <span>{{ t('receipt_id') }}:</span>
                            <span class="font-bold">{{ receiptOrder.offline_id.slice(-12).toUpperCase() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ t('date') }}:</span>
                            <span>{{ new Date(receiptOrder.created_at).toLocaleDateString() }} {{ new Date(receiptOrder.created_at).toLocaleTimeString() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ t('cashier') }}:</span>
                            <span>{{ cashierName }}</span>
                        </div>
                        
                        <div class="border-t border-dashed border-black my-2"></div>
                        
                        <div v-for="item in receiptOrder.items" :key="item.product_id" class="mb-2">
                            <div class="flex justify-between font-bold">
                                <span>{{ item.name }}</span>
                                <span>${{ item.subtotal.toFixed(2) }}</span>
                            </div>
                            <div class="text-[10px] text-gray-700">{{ item.quantity }} x ${{ item.price.toFixed(2) }}</div>
                        </div>
                        
                        <div class="border-t border-dashed border-black my-2"></div>
                        
                        <div class="flex justify-between">
                            <span>{{ t('subtotal') }}:</span>
                            <span>${{ receiptOrder.subtotal.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-rose-700" v-if="receiptOrder.discount_amount > 0">
                            <span>Discount:</span>
                            <span>-${{ receiptOrder.discount_amount.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ t('tax') }}:</span>
                            <span>${{ receiptOrder.tax_amount.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-sm mt-1">
                            <span>{{ t('total_due') }}:</span>
                            <span>${{ receiptOrder.total_amount.toFixed(2) }}</span>
                        </div>
                        
                        <div class="border-t border-dashed border-black my-2"></div>
                        
                        <div class="flex justify-between capitalize">
                            <span>{{ t('payment_method') }}:</span>
                            <span>{{ receiptOrder.payment_method }}</span>
                        </div>
                        
                        <div class="text-center mt-5 text-[10px]">
                            <p>{{ t('thank_you') }}</p>
                            <p>Self-Hosted Offline POS</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-5 border-t border-slate-700 flex gap-3 bg-slate-800">
                    <button @click="printReceipt" class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2.5 rounded-md text-xs cursor-pointer">{{ t('print') }}</button>
                    <button @click="showReceipt = false" class="flex-1 bg-slate-700 hover:bg-slate-600 text-slate-100 font-semibold py-2.5 rounded-md text-xs border border-slate-600 cursor-pointer">{{ t('done') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Local animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleUp {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

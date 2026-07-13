<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import Dexie from 'dexie';
import { Html5QrcodeScanner } from 'html5-qrcode';

// 1. Initialize Dexie DB with expanded version 2
const db = new Dexie("apf_pos_db");
db.version(2).stores({
    products: "id, name, sku, barcode, price, cost, category_id, status, stock_quantity",
    categories: "id, name, slug",
    customers: "id, name, phone, email, loyalty_points",
    pending_orders: "offline_id, branch_id, total_amount, synced_at",
    parked_orders: "offline_id, name, customer_id, total_amount, created_at",
    returns: "offline_id, original_order_id, refund_amount, created_at"
});

// 2. Broadcast Channel for Customer Facing Display
const customerDisplayChannel = new BroadcastChannel('apf_pos_customer_display');

// 3. Localization Dictionaries
const locales = {
    en: {
        search_placeholder: "Search product name, barcode scan, or SKU... [F2]",
        walkin_customer: "Walk-in Customer",
        cart_empty: "Cart is Empty",
        cart_instructions: "Click catalog products or scan barcodes to sell",
        subtotal: "Subtotal",
        discount: "Apply Cart Discount",
        tax: "Tax (15%)",
        total_due: "Total Due",
        proceed_payment: "Proceed to Payment [F4]",
        payment_settlement: "Payment Settlement",
        select_payment: "Enter Payment Breakdown (Supports Split Pay)",
        cash_received: "Cash Paid ($)",
        card_received: "Credit Card Paid ($)",
        mobile_received: "Mobile banking Paid ($)",
        loyalty_received: "Loyalty points Value Paid ($)",
        change_due: "Change Due (Cash)",
        amount_remaining: "Remaining Amount Due",
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
        back_office: "Back-office ➔",
        hold_sale: "Hold Sale [F7]",
        recall_sale: "Recall Sale [F8]",
        returns: "Returns",
        line_adjust_title: "Line Item Adjustments",
        unit_price: "Unit Price ($)",
        quantity: "Quantity",
        line_discount: "Line Discount ($)",
        camera_scanner: "Camera Scan",
        customer_display_btn: "Open Customer Display 🔗",
        email_receipt: "Email Receipt",
        sms_receipt: "SMS Receipt",
        email_placeholder: "customer@email.com",
        sms_placeholder: "017XXXXXXXX",
        send: "Send",
        return_invoice_placeholder: "Enter Invoice UUID / Receipt ID...",
        search_invoice: "Search Invoice",
        original_total: "Original Total",
        refund_calculated: "Refund Calculated",
        process_refund: "Confirm Return & Restock Items"
    },
    bn: {
        search_placeholder: "পণ্য, বারকোড বা এসকেইউ (SKU) দিয়ে খুঁজুন... [F2]",
        walkin_customer: "সাধারণ ক্রেতা",
        cart_empty: "কার্ট খালি আছে",
        cart_instructions: "পণ্য নির্বাচন করুন বা বারকোড স্ক্যান করুন",
        subtotal: "উপ-মোট",
        discount: "কার্ট ডিসকাউন্ট প্রয়োগ করুন",
        tax: "ভ্যাট (১৫%)",
        total_due: "মোট প্রদেয়",
        proceed_payment: "পেমেন্টে এগিয়ে যান [F4]",
        payment_settlement: "পেমেন্ট নিষ্পত্তি",
        select_payment: "পেমেন্ট বিবরণ প্রদান করুন (স্প্লিট পে সমর্থিত)",
        cash_received: "নগদ পেমেন্ট ($)",
        card_received: "কার্ড পেমেন্ট ($)",
        mobile_received: "মোবাইল ব্যাংকিং ($)",
        loyalty_received: "লয়্যালটি পয়েন্ট পেমেন্ট ($)",
        change_due: "ফেরতযোগ্য টাকা (নগদ)",
        amount_remaining: "বাকি প্রদেয় টাকা",
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
        back_office: "ব্যাক-অফিস ➔",
        hold_sale: "হোল্ড করুন [F7]",
        recall_sale: "পুনরুদ্ধার করুন [F8]",
        returns: "ফেরত / রিটার্নস",
        line_adjust_title: "আইটেম সমন্বয়",
        unit_price: "একক মূল্য ($)",
        quantity: "পরিমাণ",
        line_discount: "আইটেম ডিসকাউন্ট ($)",
        camera_scanner: "ক্যামেরা স্ক্যান",
        customer_display_btn: "গ্রাহক ডিসপ্লে খুলুন 🔗",
        email_receipt: "ইমেইল রসিদ",
        sms_receipt: "এসএমএস রসিদ",
        email_placeholder: "customer@email.com",
        sms_placeholder: "017XXXXXXXX",
        send: "পাঠান",
        return_invoice_placeholder: "ইনভয়েস UUID / রসিদ আইডি দিন...",
        search_invoice: "ইনভয়েস খুঁজুন",
        original_total: "মূল মোট মূল্য",
        refund_calculated: "ফেরতযোগ্য মোট মূল্য",
        process_refund: "রিটার্ন নিশ্চিত করুন এবং আইটেম স্টকে যুক্ত করুন"
    }
};

// 4. State Refs
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

const taxRate = ref(0.15);
const discountRate = ref(0.00);

const onlineStatus = ref(navigator.onLine);
const syncQueueCount = ref(0);
const parkedSalesCount = ref(0);
const isSyncing = ref(false);

// Modals toggling
const isCheckingOut = ref(false);
const showReceipt = ref(false);
const showRecallModal = ref(false);
const showLineAdjustModal = ref(false);
const showReturnsModal = ref(false);
const showCameraScanner = ref(false);

// Split Payments inputs
const splitCashAmount = ref('');
const splitCardAmount = ref('');
const splitMobileAmount = ref('');
const splitLoyaltyAmount = ref('');

// Payment Verification states
const cardVerified = ref(false);
const mobileVerified = ref(false);
const stripeTrxId = ref('');
const bkashWallet = ref('');
const bkashTrxId = ref('');
const processingStripe = ref(false);
const stripeProgress = ref('');
const processingBkash = ref(false);

// Line Item adjustments refs
const adjustingCartItem = ref(null);

// Parked Orders refs
const parkedOrders = ref([]);

// Returns refs
const returnInvoiceQuery = ref('');
const returnOrder = ref(null);
const returnedQuantities = ref({}); // product_id => quantity to return

// Messaging receipt inputs
const emailAddress = ref('');
const smsPhone = ref('');
const emailSentStatus = ref('');
const smsSentStatus = ref('');

const receiptOrder = ref(null);
const currentLocale = ref(localStorage.getItem('pos_language') || 'en');

// Camera scanner instances
let html5QrcodeScanner = null;

// 5. Helpers & Computed
const t = (key) => locales[currentLocale.value][key] || key;

const toggleLanguage = () => {
    currentLocale.value = currentLocale.value === 'en' ? 'bn' : 'en';
    localStorage.setItem('pos_language', currentLocale.value);
};

const filteredProducts = computed(() => {
    let result = allProducts.value;
    if (selectedCategory.value !== 'all') {
        result = result.filter(p => p.category_id === parseInt(selectedCategory.value));
    }
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

const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + ((item.price - item.discount) * item.quantity), 0);
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

// Split payments computed
const totalPaid = computed(() => {
    const cash = parseFloat(splitCashAmount.value) || 0;
    const card = parseFloat(splitCardAmount.value) || 0;
    const mobile = parseFloat(splitMobileAmount.value) || 0;
    const loyalty = parseFloat(splitLoyaltyAmount.value) || 0;
    return cash + card + mobile + loyalty;
});

const amountRemaining = computed(() => {
    return Math.max(0, total.value - totalPaid.value);
});

const changeDue = computed(() => {
    if (totalPaid.value <= total.value) return 0;
    return totalPaid.value - total.value;
});

const returnRefundTotal = computed(() => {
    if (!returnOrder.value) return 0;
    let sum = 0;
    returnOrder.value.items.forEach(item => {
        const qty = parseFloat(returnedQuantities.value[item.product_id]) || 0;
        const itemUnitPrice = item.price;
        sum += qty * itemUnitPrice;
    });
    // Add tax back proportional to return
    const originalSubtotal = returnOrder.value.subtotal || 1;
    const taxRatio = returnOrder.value.tax_amount / originalSubtotal;
    return sum + (sum * taxRatio);
});

// Watch cart to update second screen customer display
watch([cart, subtotal, discountAmount, taxAmount, total], () => {
    customerDisplayChannel.postMessage({
        type: 'cart-update',
        cart: cart.value.map(item => ({
            name: item.name,
            price: item.price - item.discount,
            quantity: item.quantity
        })),
        subtotal: subtotal.value,
        discount: discountAmount.value,
        tax: taxAmount.value,
        total: total.value
    });
}, { deep: true });

// 6. Lifecycle & Setup
const loadLocalCache = async () => {
    categories.value = await db.categories.toArray();
    customers.value = await db.customers.toArray();
    allProducts.value = await db.products.toArray();
    await updateCounts();
};

const updateCounts = async () => {
    syncQueueCount.value = (await db.pending_orders.count()) + (await db.returns.count());
    parkedSalesCount.value = await db.parked_orders.count();
};

// 7. Cart operations
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
            discount: 0 // Line discount
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
    customerDisplayChannel.postMessage({ type: 'clear' });
};

// 8. Line adjustments
const openLineAdjustment = (item) => {
    adjustingCartItem.value = { ...item };
    showLineAdjustModal.value = true;
};

const saveLineAdjustment = () => {
    cart.value = cart.value.map(item => 
        item.product_id === adjustingCartItem.value.product_id ? { ...adjustingCartItem.value } : item
    );
    showLineAdjustModal.value = false;
};

// 9. Hold & Recall (Park) Sales
const parkSale = async () => {
    if (cart.value.length === 0) return;
    const name = prompt("Enter reference for this held sale:") || `Held Sale #${Date.now().toString().slice(-4)}`;
    
    await db.parked_orders.add({
        offline_id: 'park-' + self.crypto.randomUUID(),
        name,
        customer_id: selectedCustomerId.value ? parseInt(selectedCustomerId.value) : null,
        cart: JSON.parse(JSON.stringify(cart.value)),
        discount_rate: discountRate.value,
        tax_rate: taxRate.value,
        total_amount: total.value,
        created_at: new Date().toISOString()
    });
    
    clearCart();
    await updateCounts();
};

const openRecallModal = async () => {
    parkedOrders.value = await db.parked_orders.toArray();
    showRecallModal.value = true;
};

const recallSale = async (order) => {
    cart.value = order.cart;
    selectedCustomerId.value = order.customer_id || '';
    discountRate.value = order.discount_rate;
    taxRate.value = order.tax_rate;
    
    await db.parked_orders.delete(order.offline_id);
    showRecallModal.value = false;
    await updateCounts();
};

// 10. Split Checkout payments
const openCheckout = () => {
    if (cart.value.length === 0) return;
    splitCashAmount.value = total.value.toFixed(2);
    splitCardAmount.value = '';
    splitMobileAmount.value = '';
    splitLoyaltyAmount.value = '';

    // Reset payment verification states
    cardVerified.value = false;
    mobileVerified.value = false;
    stripeTrxId.value = '';
    bkashWallet.value = '';
    bkashTrxId.value = '';
    stripeProgress.value = '';

    isCheckingOut.value = true;
};

const closeCheckout = () => {
    isCheckingOut.value = false;
};

const processStripeTerminal = async () => {
    const cardAmt = parseFloat(splitCardAmount.value) || 0;
    if (cardAmt <= 0) return;

    processingStripe.value = true;
    stripeProgress.value = 'Connecting to Stripe Terminal...';
    
    try {
        const res = await fetch('/api/payment/stripe-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ amount: cardAmt, currency: 'usd' })
        });
        
        if (res.ok) {
            const data = await res.json();
            
            setTimeout(() => {
                stripeProgress.value = 'Terminal Ready. Please Tap, Swipe, or Insert Card...';
                
                setTimeout(() => {
                    stripeProgress.value = 'Authorizing Transaction...';
                    
                    setTimeout(() => {
                        stripeProgress.value = '';
                        stripeTrxId.value = data.client_secret.slice(0, 18).toUpperCase();
                        cardVerified.value = true;
                        processingStripe.value = false;
                    }, 1200);
                }, 1505);
            }, 1000);
        }
    } catch (e) {
        stripeProgress.value = 'Error initializing terminal connection.';
        processingStripe.value = false;
    }
};

const processBkashVerification = async () => {
    const mobileAmt = parseFloat(splitMobileAmount.value) || 0;
    if (mobileAmt <= 0 || !bkashTrxId.value.trim()) {
        alert("Please enter a valid Transaction ID.");
        return;
    }

    processingBkash.value = true;
    try {
        const res = await fetch('/api/payment/bkash-verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ trx_id: bkashTrxId.value, amount: mobileAmt })
        });

        if (res.ok) {
            const data = await res.json();
            mobileVerified.value = true;
            bkashWallet.value = data.sender;
        } else {
            alert("Verification failed. Check the Transaction ID.");
        }
    } catch (e) {
        alert("Error verifying mobile payment.");
    } finally {
        processingBkash.value = false;
    }
};

const checkoutDisabled = computed(() => {
    if (amountRemaining.value > 0.01) return true;
    if (parseFloat(splitCardAmount.value) > 0 && !cardVerified.value) return true;
    if (parseFloat(splitMobileAmount.value) > 0 && !mobileVerified.value) return true;
    return false;
});

const submitCheckout = async () => {
    if (amountRemaining.value > 0.01) {
        alert("The split payment amounts entered do not cover the total due.");
        return;
    }

    const orderId = 'pos-sale-' + self.crypto.randomUUID();
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
        payment_method: 'split',
        payment_details: {
            cash: parseFloat(splitCashAmount.value) || 0,
            card: parseFloat(splitCardAmount.value) || 0,
            card_trx_id: stripeTrxId.value,
            mobile: parseFloat(splitMobileAmount.value) || 0,
            mobile_wallet: bkashWallet.value,
            mobile_trx_id: bkashTrxId.value,
            loyalty: parseFloat(splitLoyaltyAmount.value) || 0
        },
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
            subtotal: parseFloat(((item.price - item.discount) * item.quantity).toFixed(2))
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

    // Deduct loyalty points if used
    if (order.payment_details.loyalty > 0 && order.customer_id) {
        const customer = await db.customers.get(order.customer_id);
        if (customer) {
            // Assume 1 point = $0.10, so deduct $Paid * 10 points
            const pointsDeducted = Math.round(order.payment_details.loyalty * 10);
            customer.loyalty_points = Math.max(0, customer.loyalty_points - pointsDeducted);
            await db.customers.put(customer);
        }
    }

    clearCart();
    closeCheckout();

    await loadLocalCache();

    receiptOrder.value = order;
    emailAddress.value = '';
    smsPhone.value = '';
    emailSentStatus.value = '';
    smsSentStatus.value = '';
    showReceipt.value = true;

    // Notify customer display
    customerDisplayChannel.postMessage({
        type: 'checkout-complete',
        receiptId: orderId,
        total: order.total_amount
    });

    if (onlineStatus.value) {
        syncData();
    }
};

// 11. Sync pulling and pushing
const syncData = async () => {
    if (isSyncing.value) return;
    isSyncing.value = true;
    try {
        const pending = await db.pending_orders.toArray();
        const pendingReturns = await db.returns.toArray();
        
        if (pending.length > 0 || pendingReturns.length > 0) {
            const response = await fetch('/api/pos/sync-push', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ orders: pending, returns: pendingReturns })
            });
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    if (result.synced_offline_ids) {
                        for (const id of result.synced_offline_ids) {
                            await db.pending_orders.delete(id);
                        }
                    }
                    if (result.synced_offline_return_ids) {
                        for (const id of result.synced_offline_return_ids) {
                            await db.returns.delete(id);
                        }
                    }
                }
            }
        }

        const lastSync = localStorage.getItem('pos_last_sync_timestamp') || '';
        const pullUrl = `/api/pos/sync-pull?branch_id=${branchId.value}&last_sync=${encodeURIComponent(lastSync)}`;
        const pullResponse = await fetch(pullUrl);
        if (pullResponse.ok) {
            const data = await pullResponse.json();
            for (const cat of data.categories) await db.categories.put(cat);
            for (const cust of data.customers) await db.customers.put(cust);
            for (const prod of data.products) await db.products.put(prod);
            localStorage.setItem('pos_last_sync_timestamp', data.server_timestamp);
        }
        await loadLocalCache();
    } catch (err) {
        console.error('[Sync] Error: ', err);
    } finally {
        isSyncing.value = false;
    }
};

// 12. Camera scan setup
const toggleCameraScanner = () => {
    showCameraScanner.value = !showCameraScanner.value;
    if (showCameraScanner.value) {
        nextTick(() => {
            html5QrcodeScanner = new Html5QrcodeScanner("cameraScannerReader", { fps: 10, qrbox: 250 });
            html5QrcodeScanner.render((decodedText) => {
                handleBarcodeScan(decodedText);
                toggleCameraScanner();
            }, () => {});
        });
    } else {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().catch(err => console.error("Error clearing scanner: ", err));
            html5QrcodeScanner = null;
        }
    }
};

const handleBarcodeScan = async (barcode) => {
    const product = await db.products.where('barcode').equals(barcode).first();
    if (product) {
        addToCart(product);
    } else {
        alert(`Barcode [${barcode}] not found in local catalog.`);
    }
};

// 13. Returns & Exchanges
const openReturnsModal = () => {
    returnInvoiceQuery.value = '';
    returnOrder.value = null;
    returnedQuantities.value = {};
    showReturnsModal.value = true;
};

const searchReturnOrder = async () => {
    if (!returnInvoiceQuery.value.trim()) return;
    const invoiceId = returnInvoiceQuery.value.trim();
    
    // Look locally
    let order = await db.pending_orders.get(invoiceId);
    if (!order) {
        // Fetch from server
        try {
            const res = await fetch(`/api/pos/orders/lookup?offline_id=${encodeURIComponent(invoiceId)}`);
            if (res.ok) {
                const data = await res.json();
                if (data.success) {
                    order = data.order;
                }
            }
        } catch (err) {
            console.error("Lookup failed: ", err);
        }
    }

    if (order) {
        returnOrder.value = order;
        returnedQuantities.value = {};
        order.items.forEach(item => {
            returnedQuantities.value[item.product_id] = 0;
        });
    } else {
        alert("Invoice ID not found.");
    }
};

const submitReturn = async () => {
    if (returnRefundTotal.value <= 0) {
        alert("Please select at least 1 item to return.");
        return;
    }

    const returnId = 'pos-return-' + self.crypto.randomUUID();
    const returnRecord = {
        offline_id: returnId,
        original_order_id: returnOrder.value.offline_id,
        refund_amount: parseFloat(returnRefundTotal.value.toFixed(2)),
        created_at: new Date().toISOString(),
        items: Object.keys(returnedQuantities.value).map(prodId => ({
            product_id: parseInt(prodId),
            quantity: parseFloat(returnedQuantities.value[prodId])
        })).filter(item => item.quantity > 0)
    };

    // Save locally
    await db.returns.add(returnRecord);

    // Restock returned items
    for (const item of returnRecord.items) {
        const prod = await db.products.get(item.product_id);
        if (prod) {
            prod.stock_quantity = prod.stock_quantity + item.quantity;
            await db.products.put(prod);
        }
    }

    alert(`Refund processed successfully: $${returnRefundTotal.value.toFixed(2)}`);
    showReturnsModal.value = false;
    await loadLocalCache();
};

// 14. Email & SMS triggers
const sendReceiptEmail = async () => {
    if (!emailAddress.value.trim()) return;
    emailSentStatus.value = 'sending...';
    try {
        const res = await fetch('/api/receipt/email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ email: emailAddress.value, offline_id: receiptOrder.value.offline_id })
        });
        if (res.ok) {
            emailSentStatus.value = 'Sent! ✅';
        }
    } catch (e) {
        emailSentStatus.value = 'Failed ❌';
    }
};

const sendReceiptSMS = async () => {
    if (!smsPhone.value.trim()) return;
    smsSentStatus.value = 'sending...';
    try {
        const res = await fetch('/api/receipt/sms', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ phone: smsPhone.value, offline_id: receiptOrder.value.offline_id })
        });
        if (res.ok) {
            smsSentStatus.value = 'Sent! ✅';
        }
    } catch (e) {
        smsSentStatus.value = 'Failed ❌';
    }
};

// 15. Receipt Printing & Second display launch
const printReceipt = () => {
    const printContent = document.getElementById('receiptPrintSection').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print Receipt</title>');
    printWindow.document.write('<style>body{font-family:monospace;padding:20px;color:black;} .receipt-divider{border-top:1px dashed black;margin:10px 0;} .receipt-row{display:flex;justify-content:space-between;margin-bottom:4px;} .receipt-header{text-align:center;} .receipt-footer{text-align:center;margin-top:20px;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
};

const openCustomerDisplay = () => {
    window.open('/pos/customer-display', 'apf_customer_facing_display', 'width=1000,height=700');
};

// Keyboard listener
const barcodeScannedListener = async (e) => {
    await handleBarcodeScan(e.detail);
};

const handleKeyboard = (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        document.querySelector('input[type="text"]')?.focus();
    } else if (e.key === 'F4') {
        e.preventDefault();
        openCheckout();
    } else if (e.key === 'F7') {
        e.preventDefault();
        parkSale();
    } else if (e.key === 'F8') {
        e.preventDefault();
        openRecallModal();
    } else if (e.key === 'Escape') {
        closeCheckout();
        showRecallModal.value = false;
        showLineAdjustModal.value = false;
        showReturnsModal.value = false;
        showCameraScanner.value = false;
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().catch(() => {});
            html5QrcodeScanner = null;
        }
    }
};

onMounted(async () => {
    const rootEl = document.getElementById('app');
    branchId.value = rootEl.getAttribute('data-branch-id');
    cashierId.value = rootEl.getAttribute('data-cashier-id');
    cashierName.value = rootEl.getAttribute('data-cashier-name');
    branchName.value = rootEl.getAttribute('data-branch-name');

    window.addEventListener('online', () => { onlineStatus.value = true; syncData(); });
    window.addEventListener('offline', () => { onlineStatus.value = false; });
    window.addEventListener('barcode-scanned', barcodeScannedListener);
    window.addEventListener('keydown', handleKeyboard);

    await loadLocalCache();
});

onUnmounted(() => {
    window.removeEventListener('barcode-scanned', barcodeScannedListener);
    window.removeEventListener('keydown', handleKeyboard);
});
</script>

<template>
    <div class="flex flex-col h-screen text-slate-50 font-sans overflow-hidden bg-slate-900">
        <!-- Header Section -->
        <header class="h-[60px] bg-slate-800 border-b border-slate-700 flex justify-between items-center px-5 z-10 select-none">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-400 to-emerald-400 bg-clip-text text-transparent">APF POS</h1>
                <span class="bg-indigo-500/10 text-indigo-300 text-xs px-2 py-1 rounded-md font-semibold border border-indigo-500/30">{{ branchName }}</span>
            </div>
            
            <div class="flex items-center gap-4">
                <button @click="openCustomerDisplay" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                    {{ t('customer_display_btn') }}
                </button>

                <button @click="openReturnsModal" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 rounded text-xs font-semibold border border-slate-600 transition">
                    {{ t('returns') }}
                </button>

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
                <div class="p-4 border-b border-slate-700 flex justify-between items-center gap-3">
                    <select v-model="selectedCustomerId" class="flex-1 bg-slate-800 border border-slate-700 text-slate-100 p-2.5 rounded-lg text-sm outline-none cursor-pointer focus:border-indigo-500">
                        <option value="">{{ t('walkin_customer') }}</option>
                        <option v-for="cust in customers" :key="cust.id" :value="cust.id">
                            {{ cust.name }} (🌟 {{ cust.loyalty_points }} pts)
                        </option>
                    </select>

                    <button @click="openRecallModal" class="px-3 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-slate-100 border border-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                        <span>{{ t('recall_sale') }}</span>
                        <span v-if="parkedSalesCount > 0" class="bg-indigo-600 text-white text-[10px] w-4.5 h-4.5 rounded-full flex items-center justify-center font-bold">{{ parkedSalesCount }}</span>
                    </button>
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
                        <div class="flex-1 pr-1">
                            <div class="text-sm font-semibold text-slate-100 leading-snug">{{ item.name }}</div>
                            <div class="text-xs text-slate-400 mt-1 flex flex-wrap gap-x-2.5 gap-y-0.5">
                                <span>${{ (item.price - item.discount).toFixed(2) }}</span>
                                <span class="font-mono text-slate-500">[{{ item.sku }}]</span>
                                <span v-if="item.discount > 0" class="text-rose-400 font-medium">Disc: -${{ item.discount }}</span>
                            </div>
                        </div>
                        
                        <!-- Quantities & Adjustments -->
                        <div class="flex items-center gap-1.5 select-none">
                            <button @click="updateQty(item.product_id, -1)" class="w-6.5 h-6.5 rounded bg-slate-700 hover:bg-slate-600 border border-slate-600 flex items-center justify-center font-bold text-xs cursor-pointer">-</button>
                            <span class="w-6 text-center text-xs font-semibold">{{ item.quantity }}</span>
                            <button @click="updateQty(item.product_id, 1)" class="w-6.5 h-6.5 rounded bg-slate-700 hover:bg-slate-600 border border-slate-600 flex items-center justify-center font-bold text-xs cursor-pointer">+</button>
                        </div>
                        
                        <div class="flex flex-col items-end gap-1.5 w-20">
                            <div class="text-xs font-bold text-slate-100">${{ ((item.price - item.discount) * item.quantity).toFixed(2) }}</div>
                            <button @click="openLineAdjustment(item)" class="text-[10px] text-indigo-400 hover:text-indigo-300 font-semibold underline cursor-pointer select-none">Edit</button>
                        </div>
                        
                        <button @click="removeItem(item.product_id)" class="text-rose-500 hover:text-rose-400 font-bold text-lg cursor-pointer select-none">&times;</button>
                    </div>
                </div>
                
                <!-- Pricing Summary Panel -->
                <div class="bg-slate-800 border-t border-slate-700 p-4.5 flex flex-col gap-2.5">
                    <div class="flex justify-between text-xs text-slate-400">
                        <span>{{ t('subtotal') }}:</span>
                        <span>${{ subtotal.toFixed(2) }}</span>
                    </div>
                    
                    <!-- Discount Rate select -->
                    <div class="flex justify-between items-center gap-3">
                        <span class="text-xs text-slate-400">{{ t('discount') }}:</span>
                        <select v-model="discountRate" class="bg-slate-900 border border-slate-700 text-slate-100 text-xs p-1.5 rounded outline-none focus:border-indigo-500 cursor-pointer">
                            <option value="0.00">{{ t('no_discount') }}</option>
                            <option value="0.05">{{ t('staff_discount') }}</option>
                            <option value="0.10">{{ t('special_promo') }}</option>
                            <option value="0.15">{{ t('loyalty_member') }}</option>
                            <option value="0.20">{{ t('clearance_sale') }}</option>
                        </select>
                    </div>
                    
                    <div v-if="discountAmount > 0" class="flex justify-between text-xs text-rose-400 font-medium">
                        <span>Discount Applied:</span>
                        <span>-${{ discountAmount.toFixed(2) }}</span>
                    </div>
                    
                    <div class="flex justify-between text-xs text-slate-400">
                        <span>{{ t('tax') }}:</span>
                        <span>${{ taxAmount.toFixed(2) }}</span>
                    </div>
                    
                    <div class="flex justify-between text-base font-extrabold border-t border-slate-700 pt-2.5 text-slate-100">
                        <span>{{ t('total_due') }}:</span>
                        <span>${{ total.toFixed(2) }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mt-2 select-none">
                        <button @click="parkSale" class="bg-slate-700 hover:bg-slate-650 text-slate-200 py-3 rounded-lg font-bold text-xs cursor-pointer transition" :disabled="cart.length === 0" :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                            {{ t('hold_sale') }}
                        </button>
                        <button @click="openCheckout" class="bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-lg font-bold text-xs cursor-pointer transition shadow-[0_4px_12px_rgba(16,185,129,0.15)]" :disabled="cart.length === 0" :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                            {{ t('proceed_payment') }}
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Right panel: Catalog -->
            <div class="flex-1 flex flex-col h-full bg-slate-900">
                <!-- Search bar & Categories -->
                <div class="bg-slate-800 border-b border-slate-700 p-4 flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input type="text" v-model="searchQuery" :placeholder="t('search_placeholder')" class="w-full bg-slate-950 border border-slate-700 text-slate-100 py-2.5 pl-10 pr-4 rounded-lg text-xs outline-none focus:border-indigo-500 transition" />
                        </div>

                        <!-- Camera Toggle Button -->
                        <button @click="toggleCameraScanner" class="px-3.5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-xs flex items-center gap-1.5 transition">
                            <span>📷 {{ t('camera_scanner') }}</span>
                        </button>
                    </div>

                    <!-- Collapsible Camera Viewfinder -->
                    <div v-show="showCameraScanner" class="bg-slate-950 border border-slate-700 rounded-lg p-4 flex flex-col items-center gap-2">
                        <div id="cameraScannerReader" class="w-full max-w-[320px] overflow-hidden rounded"></div>
                        <button @click="toggleCameraScanner" class="text-xs text-rose-400 hover:underline">Close Camera</button>
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
                                <!-- Beautiful mock visual block instead of empty images -->
                                <div class="w-full h-24 bg-gradient-to-br from-indigo-950 to-slate-900 border border-indigo-900/40 rounded flex items-center justify-center text-indigo-400 font-extrabold text-sm mb-3">
                                    {{ prod.name.split(' ').map(w => w[0]).join('').slice(0, 3).toUpperCase() }}
                                </div>
                                <div class="text-xs font-bold text-slate-100 line-clamp-2 h-9 leading-snug">{{ prod.name }}</div>
                                <div class="text-[9px] font-mono text-slate-500 mt-1">{{ prod.sku }}</div>
                            </div>
                            
                            <div class="flex justify-between items-end mt-4">
                                <div class="text-xs font-extrabold text-slate-200">${{ prod.price.toFixed(2) }}</div>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded" :class="prod.stock_quantity <= 0 ? 'bg-rose-500/10 text-rose-400' : (prod.stock_quantity < 20 ? 'bg-amber-500/10 text-amber-400' : 'bg-emerald-500/10 text-emerald-400')">
                                    {{ prod.stock_quantity <= 0 ? 'Out of stock' : prod.stock_quantity.toFixed(0) + ' left' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Split Payment Checkout Dialog -->
        <div v-if="isCheckingOut" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50 animate-[fadeIn_0.12s_ease-out]">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[480px] overflow-hidden shadow-2xl animate-[scaleUp_0.15s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">{{ t('payment_settlement') }}</h3>
                    <button @click="closeCheckout" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                
                <div class="p-5 flex flex-col gap-4">
                    <div class="flex justify-between items-center bg-slate-900/50 p-3 rounded-lg text-base font-bold text-slate-100">
                        <span>{{ t('total_due') }}:</span>
                        <span class="text-indigo-400">${{ total.toFixed(2) }}</span>
                    </div>
                    
                    <div class="flex flex-col gap-3">
                        <span class="text-xs font-semibold text-slate-400">{{ t('select_payment') }}</span>
                        
                        <div class="space-y-3">
                            <!-- Cash Payment -->
                            <div class="flex items-center justify-between gap-4 bg-slate-900/40 p-2 border border-slate-700 rounded-lg">
                                <label class="text-xs font-bold text-slate-300 w-32">💵 Cash</label>
                                <input type="number" v-model="splitCashAmount" placeholder="0.00" class="bg-slate-900 border border-slate-700 text-slate-100 px-3 py-1.5 rounded text-sm text-right font-mono outline-none focus:border-indigo-500 flex-1" step="0.01" min="0">
                            </div>

                            <!-- Card Payment -->
                            <div class="flex flex-col gap-2 bg-slate-900/40 p-2 border border-slate-700 rounded-lg">
                                <div class="flex items-center justify-between gap-4">
                                    <label class="text-xs font-bold text-slate-300 w-32">💳 Credit Card</label>
                                    <input type="number" v-model="splitCardAmount" placeholder="0.00" class="bg-slate-900 border border-slate-700 text-slate-100 px-3 py-1.5 rounded text-sm text-right font-mono outline-none focus:border-indigo-500 flex-1" step="0.01" min="0">
                                </div>
                                <div v-if="parseFloat(splitCardAmount) > 0" class="border-t border-slate-800/85 pt-2 flex flex-col gap-1.5">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-slate-400 font-semibold">Stripe Reader Status:</span>
                                        <span v-if="cardVerified" class="text-emerald-400 font-bold">Verified ✅ ({{ stripeTrxId }})</span>
                                        <span v-else-if="processingStripe" class="text-indigo-400 font-semibold animate-pulse">{{ stripeProgress }}</span>
                                        <span v-else class="text-amber-400 font-semibold">Card Swipe Needed</span>
                                    </div>
                                    <button type="button" @click="processStripeTerminal" v-if="!cardVerified" :disabled="processingStripe" class="w-full bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 text-xs py-1.5 rounded border border-indigo-500/20 font-bold transition cursor-pointer">
                                        {{ processingStripe ? 'Reader Processing...' : '⚡ Trigger Stripe Reader Mock' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Mobile Payment -->
                            <div class="flex flex-col gap-2 bg-slate-900/40 p-2 border border-slate-700 rounded-lg">
                                <div class="flex items-center justify-between gap-4">
                                    <label class="text-xs font-bold text-slate-300 w-32">📱 Mobile Pay</label>
                                    <input type="number" v-model="splitMobileAmount" placeholder="0.00" class="bg-slate-900 border border-slate-700 text-slate-100 px-3 py-1.5 rounded text-sm text-right font-mono outline-none focus:border-indigo-500 flex-1" step="0.01" min="0">
                                </div>
                                <div v-if="parseFloat(splitMobileAmount) > 0" class="border-t border-slate-800/85 pt-2 flex flex-col gap-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-slate-400 font-semibold">bKash Verification:</span>
                                        <span v-if="mobileVerified" class="text-emerald-400 font-bold">Verified ✅ (Wallet: {{ bkashWallet }})</span>
                                        <span v-else class="text-amber-500 font-semibold">Enter TrxID</span>
                                    </div>
                                    <div v-if="!mobileVerified" class="flex gap-2">
                                        <input type="text" v-model="bkashTrxId" placeholder="Enter bKash TrxID" class="bg-slate-950 border border-slate-750 text-slate-100 text-xs px-2.5 py-1.5 rounded flex-1 outline-none">
                                        <button type="button" @click="processBkashVerification" :disabled="processingBkash || !bkashTrxId.trim()" class="bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded text-xs font-bold transition cursor-pointer">
                                            Verify
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Loyalty Points Payment -->
                            <div class="flex items-center justify-between gap-4 bg-slate-900/40 p-2 border border-slate-700 rounded-lg">
                                <label class="text-xs font-bold text-slate-300 w-32">🌟 Loyalty Pts</label>
                                <input type="number" v-model="splitLoyaltyAmount" placeholder="0.00" class="bg-slate-900 border border-slate-700 text-slate-100 px-3 py-1.5 rounded text-sm text-right font-mono outline-none focus:border-indigo-500 flex-1" step="0.01" min="0">
                            </div>
                        </div>
                    </div>

                    <!-- Remaining to Pay indicator -->
                    <div v-if="amountRemaining > 0.01" class="p-3 rounded-lg bg-amber-500/10 border border-dashed border-amber-500 text-amber-400 font-bold flex justify-between items-center text-sm">
                        <span>{{ t('amount_remaining') }}:</span>
                        <span>${{ amountRemaining.toFixed(2) }}</span>
                    </div>
                    
                    <div v-if="changeDue > 0.01" class="p-3 rounded-lg bg-emerald-500/10 border border-dashed border-emerald-500 text-emerald-400 font-bold flex justify-between items-center text-sm">
                        <span>{{ t('change_due') }}:</span>
                        <span>${{ changeDue.toFixed(2) }}</span>
                    </div>
                    
                    <button @click="submitCheckout" class="w-full bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3.5 rounded-lg text-sm transition mt-2 cursor-pointer shadow-md" :disabled="checkoutDisabled">
                        {{ t('complete_sale') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Line Item Adjustments Dialog -->
        <div v-if="showLineAdjustModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[380px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">{{ t('line_adjust_title') }}</h3>
                    <button @click="showLineAdjustModal = false" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                
                <div class="p-5 flex flex-col gap-4">
                    <div class="text-sm font-semibold text-slate-100 bg-slate-900/50 p-2.5 rounded">
                        {{ adjustingCartItem.name }}
                    </div>

                    <!-- Quantity Input -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-slate-400 font-semibold">{{ t('quantity') }}</label>
                        <input type="number" v-model="adjustingCartItem.quantity" class="bg-slate-900 border border-slate-700 text-slate-100 p-2.5 rounded-lg text-sm outline-none focus:border-indigo-500" step="0.01" min="0.01">
                    </div>

                    <!-- Unit Price Override -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-slate-400 font-semibold">{{ t('unit_price') }}</label>
                        <input type="number" v-model="adjustingCartItem.price" class="bg-slate-900 border border-slate-700 text-slate-100 p-2.5 rounded-lg text-sm outline-none focus:border-indigo-500" step="0.01" min="0">
                    </div>

                    <!-- Line Discount -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs text-slate-400 font-semibold">{{ t('line_discount') }}</label>
                        <input type="number" v-model="adjustingCartItem.discount" class="bg-slate-900 border border-slate-700 text-slate-100 p-2.5 rounded-lg text-sm outline-none focus:border-indigo-500" step="0.01" min="0">
                    </div>

                    <button @click="saveLineAdjustment" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-lg text-sm transition mt-2">
                        Apply Adjustments
                    </button>
                </div>
            </div>
        </div>

        <!-- Parked Sales Dialog -->
        <div v-if="showRecallModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[420px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">{{ t('recall_sale') }}</h3>
                    <button @click="showRecallModal = false" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                
                <div class="p-5 max-h-[50vh] overflow-y-auto space-y-3">
                    <div v-if="parkedOrders.length === 0" class="text-center text-xs text-slate-400 py-6">
                        No parked sales found.
                    </div>
                    <div v-for="order in parkedOrders" :key="order.offline_id" class="bg-slate-900 border border-slate-700 rounded-lg p-3.5 flex justify-between items-center">
                        <div>
                            <div class="text-sm font-bold text-slate-100">{{ order.name }}</div>
                            <div class="text-[10px] text-slate-500 mt-0.5">{{ new Date(order.created_at).toLocaleString() }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-slate-200">${{ order.total_amount.toFixed(2) }}</span>
                            <button @click="recallSale(order)" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs font-bold transition">Load</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Returns & Exchanges Dialog -->
        <div v-if="showReturnsModal" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[540px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">{{ t('returns') }}</h3>
                    <button @click="showReturnsModal = false" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                
                <div class="p-5 flex flex-col gap-4">
                    <div class="flex gap-3">
                        <input type="text" v-model="returnInvoiceQuery" :placeholder="t('return_invoice_placeholder')" class="flex-1 bg-slate-900 border border-slate-700 text-slate-100 px-3 py-2.5 rounded-lg text-xs outline-none focus:border-indigo-500 font-mono">
                        <button @click="searchReturnOrder" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition">
                            {{ t('search_invoice') }}
                        </button>
                    </div>

                    <!-- Return Invoice Summary -->
                    <div v-if="returnOrder" class="border-t border-slate-700 pt-4 space-y-4">
                        <div class="flex justify-between text-xs text-slate-400">
                            <span>{{ t('date') }}: {{ new Date(returnOrder.created_at).toLocaleDateString() }}</span>
                            <span>{{ t('original_total') }}: ${{ returnOrder.total_amount.toFixed(2) }}</span>
                        </div>

                        <!-- Item details -->
                        <div class="space-y-2.5">
                            <div v-for="item in returnOrder.items" :key="item.product_id" class="bg-slate-900/60 border border-slate-850 p-3 rounded-lg flex justify-between items-center gap-4">
                                <div class="flex-1">
                                    <div class="text-xs font-bold text-slate-200">{{ item.name }}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Purchased: {{ item.quantity }} @ ${{ item.price }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-slate-400 font-semibold">Return:</span>
                                    <input type="number" v-model="returnedQuantities[item.product_id]" class="w-16 bg-slate-900 border border-slate-750 text-slate-100 p-1.5 rounded text-center text-xs font-bold outline-none" min="0" :max="item.quantity" step="1">
                                </div>
                            </div>
                        </div>

                        <!-- Refund Total display -->
                        <div class="p-3 bg-rose-500/10 border border-dashed border-rose-500 rounded-lg flex justify-between items-center text-sm font-bold text-rose-400">
                            <span>{{ t('refund_calculated') }}:</span>
                            <span>${{ returnRefundTotal.toFixed(2) }}</span>
                        </div>

                        <button @click="submitReturn" class="w-full bg-rose-600 hover:bg-rose-500 text-white py-3.5 rounded-lg text-sm font-bold shadow-md cursor-pointer transition" :disabled="returnRefundTotal <= 0" :class="returnRefundTotal <= 0 ? 'opacity-50 cursor-not-allowed' : ''">
                            {{ t('process_refund') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Receipt Print Dialog Overlay -->
        <div v-if="showReceipt" class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center z-50 animate-[fadeIn_0.12s_ease-out]">
            <div class="bg-slate-800 border border-slate-700 rounded-xl w-[95%] max-w-[420px] overflow-hidden shadow-2xl">
                <div class="px-5 py-4 border-b border-slate-700 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-100">{{ t('transaction_complete') }}</h3>
                    <button @click="showReceipt = false" class="text-slate-400 hover:text-slate-200 font-bold text-xl cursor-pointer">&times;</button>
                </div>
                
                <!-- Email & SMS messaging inputs inside completion dialog -->
                <div class="bg-slate-900 p-4 border-b border-slate-750 flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <label class="text-xs font-semibold text-slate-400 w-24">{{ t('email_receipt') }}</label>
                        <input type="email" v-model="emailAddress" :placeholder="t('email_placeholder')" class="flex-1 bg-slate-850 border border-slate-700 text-slate-100 px-3 py-1.5 rounded text-xs outline-none focus:border-indigo-500">
                        <button @click="sendReceiptEmail" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 rounded text-xs font-semibold transition">{{ t('send') }}</button>
                        <span class="text-[10px] text-indigo-400 w-16" v-if="emailSentStatus">{{ emailSentStatus }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="text-xs font-semibold text-slate-400 w-24">{{ t('sms_receipt') }}</label>
                        <input type="text" v-model="smsPhone" :placeholder="t('sms_placeholder')" class="flex-1 bg-slate-850 border border-slate-700 text-slate-100 px-3 py-1.5 rounded text-xs outline-none focus:border-indigo-500">
                        <button @click="sendReceiptSMS" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 rounded text-xs font-semibold transition">{{ t('send') }}</button>
                        <span class="text-[10px] text-indigo-400 w-16" v-if="smsSentStatus">{{ smsSentStatus }}</span>
                    </div>
                </div>

                <div class="bg-white text-black p-5 max-h-[40vh] overflow-y-auto" id="receiptPrintSection">
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
                            <div class="text-[10px] text-gray-700">{{ item.quantity }} x ${{ item.price.toFixed(2) }} <span v-if="item.discount > 0">(-${{ item.discount }})</span></div>
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
                        
                        <!-- Split payment receipts details -->
                        <div class="font-bold mb-1">Payments:</div>
                        <div class="pl-2 space-y-0.5">
                            <div class="flex justify-between text-[10px]" v-if="receiptOrder.payment_details.cash > 0">
                                <span>Cash:</span>
                                <span>${{ receiptOrder.payment_details.cash.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-[10px]" v-if="receiptOrder.payment_details.card > 0">
                                <span>Card:</span>
                                <span>${{ receiptOrder.payment_details.card.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-[10px]" v-if="receiptOrder.payment_details.mobile > 0">
                                <span>Mobile Pay:</span>
                                <span>${{ receiptOrder.payment_details.mobile.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-[10px]" v-if="receiptOrder.payment_details.loyalty > 0">
                                <span>Loyalty Pts:</span>
                                <span>${{ receiptOrder.payment_details.loyalty.toFixed(2) }}</span>
                            </div>
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
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes scaleUp {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

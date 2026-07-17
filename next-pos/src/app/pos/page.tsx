'use client';

import React, { useState, useEffect, useMemo, useRef } from 'react';
import { useSession, signOut } from 'next-auth/react';
import { useRouter } from 'next/navigation';
import Dexie from 'dexie';
import {
  Search,
  ShoppingCart,
  User,
  LogOut,
  RefreshCw,
  Layers,
  Sparkles,
  Camera,
  Coins,
  CreditCard,
  Phone,
  Bookmark,
  History,
  RotateCcw,
  Plus,
  Minus,
  Trash2,
  Settings,
  HelpCircle,
  Volume2,
} from 'lucide-react';

// 1. Initialize Dexie DB client-side only to prevent SSR errors
const db = typeof window !== 'undefined' ? new Dexie('apf_pos_db') : null;
if (db) {
  db.version(2).stores({
    products: 'id, name, sku, barcode, price, cost, category_id, status, stock_quantity',
    categories: 'id, name, slug',
    customers: 'id, name, phone, email, loyalty_points',
    pending_orders: 'offline_id, branch_id, total_amount, synced_at',
    parked_orders: 'offline_id, name, customer_id, total_amount, created_at',
    returns: 'offline_id, original_order_id, refund_amount, created_at',
  });
}

// 2. Localization Dictionaries
const locales: any = {
  en: {
    search_placeholder: 'Search product name, barcode scan, or SKU...',
    walkin_customer: 'Walk-in Customer',
    cart_empty: 'Cart is Empty',
    cart_instructions: 'Select products from catalog or scan barcodes',
    subtotal: 'Subtotal',
    discount: 'Apply Cart Discount',
    tax: 'Tax (15%)',
    total_due: 'Total Due',
    proceed_payment: 'Proceed to Payment',
    payment_settlement: 'Payment Settlement',
    select_payment: 'Enter Payment Breakdown (Supports Split Pay)',
    cash_received: 'Cash Paid ($)',
    card_received: 'Credit Card Paid ($)',
    mobile_received: 'Mobile banking Paid ($)',
    loyalty_received: 'Loyalty points Value Paid ($)',
    change_due: 'Change Due (Cash)',
    amount_remaining: 'Remaining Amount Due',
    complete_sale: 'Complete Transaction & Print Receipt',
    transaction_complete: 'Transaction Complete',
    receipt_id: 'Receipt ID',
    date: 'Date',
    cashier: 'Cashier',
    payment_method: 'Paid Via',
    thank_you: 'Thank You For Shopping!',
    print: 'Print',
    done: 'Done',
    no_discount: 'No Discount (0%)',
    staff_discount: 'Staff Discount (5%)',
    special_promo: 'Special Promo (10%)',
    loyalty_member: 'Loyalty Member (15%)',
    clearance_sale: 'Clearance Sale (20%)',
    sync_queue: 'Sync Offline Queue',
    syncing: 'Syncing...',
    unsynced: 'unsynced',
    online: 'Online',
    offline: 'Offline Mode',
    back_office: 'Back-office ➔',
    hold_sale: 'Hold Sale',
    recall_sale: 'Recall Sale',
    returns: 'Returns',
    line_adjust_title: 'Line Item Adjustments',
    unit_price: 'Unit Price ($)',
    quantity: 'Quantity',
    line_discount: 'Line Discount ($)',
    camera_scanner: 'Camera Scan',
    customer_display_btn: 'Open Customer Display 🔗',
    email_receipt: 'Email Receipt',
    sms_receipt: 'SMS Receipt',
    email_placeholder: 'customer@email.com',
    sms_placeholder: '017XXXXXXXX',
    send: 'Send',
    return_invoice_placeholder: 'Enter Invoice UUID / Receipt ID...',
    search_invoice: 'Search Invoice',
    original_total: 'Original Total',
    refund_calculated: 'Refund Calculated',
    process_refund: 'Confirm Return & Restock Items',
  },
  bn: {
    search_placeholder: 'পণ্য, বারকোড বা এসকেইউ (SKU) দিয়ে খুঁজুন...',
    walkin_customer: 'সাধারণ ক্রেতা',
    cart_empty: 'কার্ট খালি আছে',
    cart_instructions: 'পণ্য নির্বাচন করুন বা বারকোড স্ক্যান করুন',
    subtotal: 'উপ-মোট',
    discount: 'কার্ট ডিসকাউন্ট প্রয়োগ করুন',
    tax: 'ভ্যাট (১৫%)',
    total_due: 'মোট প্রদেয়',
    proceed_payment: 'পেমেন্টে এগিয়ে যান',
    payment_settlement: 'পেমেন্ট নিষ্পত্তি',
    select_payment: 'পেমেন্ট বিবরণ প্রদান করুন (স্প্লিট পে সমর্থিত)',
    cash_received: 'নগদ পেমেন্ট ($)',
    card_received: 'কার্ড পেমেন্ট ($)',
    mobile_received: 'মোবাইল ব্যাংকিং ($)',
    loyalty_received: 'লয়্যালটি পয়েন্ট পেমেন্ট ($)',
    change_due: 'ফেরতযোগ্য টাকা (নগদ)',
    amount_remaining: 'বাকি প্রদেয় টাকা',
    complete_sale: 'লেনদেন সম্পন্ন করুন এবং রসিদ প্রিন্ট করুন',
    transaction_complete: 'লেনদেন সফল হয়েছে',
    receipt_id: 'রসিদ নম্বর',
    date: 'তারিখ',
    cashier: 'ক্যাশিয়ার',
    payment_method: 'পেমেন্ট মাধ্যম',
    thank_you: 'কেনাকাটার জন্য ধন্যবাদ!',
    print: 'প্রিন্ট',
    done: 'সম্পন্ন',
    no_discount: 'কোন ছাড় নেই (0%)',
    staff_discount: 'কর্মী ছাড় (5%)',
    special_promo: 'বিশেষ প্রোমো (10%)',
    loyalty_member: 'লয়্যালটি সদস্য (15%)',
    clearance_sale: 'ক্লিয়ারেন্স সেল (20%)',
    sync_queue: 'অফলাইন কিউ সিঙ্ক করুন',
    syncing: 'সিঙ্ক হচ্ছে...',
    unsynced: 'সিঙ্কহীন',
    online: 'অনলাইন',
    offline: 'অফলাইন মোড',
    back_office: 'ব্যাক-অফিস ➔',
    hold_sale: 'হোল্ড করুন',
    recall_sale: 'পুনরুদ্ধার করুন',
    returns: 'ফেরত / রিটার্নস',
    line_adjust_title: 'আইটেম সমন্বয়',
    unit_price: 'একক মূল্য ($)',
    quantity: 'পরিমাণ',
    line_discount: 'আইটেম ডিসকাউন্ট ($)',
    camera_scanner: 'ক্যামেরা স্ক্যান',
    customer_display_btn: 'গ্রাহক ডিসপ্লে খুলুন 🔗',
    email_receipt: 'ইমেইল রসিদ',
    sms_receipt: 'এসএমএস রসিদ',
    email_placeholder: 'customer@email.com',
    sms_placeholder: '017XXXXXXXX',
    send: 'পাঠান',
    return_invoice_placeholder: 'ইনভয়েস UUID / রসিদ আইডি দিন...',
    search_invoice: 'ইনভয়েস খুঁজুন',
    original_total: 'মূল মোট মূল্য',
    refund_calculated: 'ফেরতযোগ্য মোট মূল্য',
    process_refund: 'রিটার্ন নিশ্চিত করুন এবং আইটেম স্টকে যুক্ত করুন',
  },
};

export default function PosPage() {
  const { data: session, status } = useSession();
  const router = useRouter();

  // State Hooks
  const [currentLocale, setCurrentLocale] = useState('en');
  const [onlineStatus, setOnlineStatus] = useState(true);
  const [categoriesList, setCategoriesList] = useState<any[]>([]);
  const [customersList, setCustomersList] = useState<any[]>([]);
  const [productsList, setProductsList] = useState<any[]>([]);
  const [cart, setCart] = useState<any[]>([]);
  
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('all');
  const [selectedCustomerId, setSelectedCustomerId] = useState('');

  const [discountRate, setDiscountRate] = useState(0.0);
  const [taxRate] = useState(0.15); // 15% VAT

  const [syncQueueCount, setSyncQueueCount] = useState(0);
  const [parkedSalesCount, setParkedSalesCount] = useState(0);
  const [isSyncing, setIsSyncing] = useState(false);

  // Modals state
  const [isCheckingOut, setIsCheckingOut] = useState(false);
  const [showReceipt, setShowReceipt] = useState(false);
  const [showRecallModal, setShowRecallModal] = useState(false);
  const [showReturnsModal, setShowReturnsModal] = useState(false);

  // Split payment state
  const [splitCashAmount, setSplitCashAmount] = useState('');
  const [splitCardAmount, setSplitCardAmount] = useState('');
  const [splitMobileAmount, setSplitMobileAmount] = useState('');
  const [splitLoyaltyAmount, setSplitLoyaltyAmount] = useState('');

  const [cardVerified, setCardVerified] = useState(false);
  const [mobileVerified, setMobileVerified] = useState(false);
  const [stripeTrxId, setStripeTrxId] = useState('');
  const [bkashWallet, setBkashWallet] = useState('');
  const [bkashTrxId, setBkashTrxId] = useState('');
  
  const [parkedOrders, setParkedOrders] = useState<any[]>([]);
  const [receiptOrder, setReceiptOrder] = useState<any>(null);

  // Setup broadcast channel client-side
  const customerDisplayChannelRef = useRef<BroadcastChannel | null>(null);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      customerDisplayChannelRef.current = new BroadcastChannel('apf_pos_customer_display');
      setOnlineStatus(navigator.onLine);
      setCurrentLocale(localStorage.getItem('pos_language') || 'en');

      const handleOnline = () => setOnlineStatus(true);
      const handleOffline = () => setOnlineStatus(false);

      window.addEventListener('online', handleOnline);
      window.addEventListener('offline', handleOffline);

      return () => {
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
        customerDisplayChannelRef.current?.close();
      };
    }
  }, []);

  // Fetch local cache
  const loadLocalCache = async () => {
    if (!db) return;
    try {
      const cats = await db.table('categories').toArray();
      const custs = await db.table('customers').toArray();
      const prods = await db.table('products').toArray();

      setCategoriesList(cats);
      setCustomersList(custs);
      setProductsList(prods);

      const queue = (await db.table('pending_orders').count()) + (await db.table('returns').count());
      const parked = await db.table('parked_orders').count();
      setSyncQueueCount(queue);
      setParkedSalesCount(parked);
    } catch (e) {
      console.error(e);
    }
  };

  useEffect(() => {
    loadLocalCache();
  }, []);

  // helper translation
  const t = (key: string) => locales[currentLocale]?.[key] || key;

  // Toggle Language
  const toggleLanguage = () => {
    const nextLang = currentLocale === 'en' ? 'bn' : 'en';
    setCurrentLocale(nextLang);
    localStorage.setItem('pos_language', nextLang);
  };

  // Filter products
  const filteredProducts = useMemo(() => {
    let result = productsList;
    if (selectedCategory !== 'all') {
      result = result.filter((p) => p.category_id === parseInt(selectedCategory));
    }
    if (searchQuery.trim() !== '') {
      const query = searchQuery.toLowerCase().trim();
      result = result.filter(
        (p) =>
          p.name.toLowerCase().includes(query) ||
          p.sku.toLowerCase().includes(query) ||
          (p.barcode && p.barcode.includes(query))
      );
    }
    return result;
  }, [productsList, selectedCategory, searchQuery]);

  // Cart values calculations
  const subtotal = useMemo(() => {
    return cart.reduce((sum, item) => sum + (item.price - item.discount) * item.quantity, 0);
  }, [cart]);

  const discountAmount = useMemo(() => {
    return subtotal * discountRate;
  }, [subtotal, discountRate]);

  const taxAmount = useMemo(() => {
    return (subtotal - discountAmount) * taxRate;
  }, [subtotal, discountAmount, taxRate]);

  const total = useMemo(() => {
    return Math.max(0, subtotal - discountAmount + taxAmount);
  }, [subtotal, discountAmount, taxAmount]);

  // Split payment totals
  const totalPaid = useMemo(() => {
    const cash = parseFloat(splitCashAmount) || 0;
    const card = parseFloat(splitCardAmount) || 0;
    const mobile = parseFloat(splitMobileAmount) || 0;
    const loyalty = parseFloat(splitLoyaltyAmount) || 0;
    return cash + card + mobile + loyalty;
  }, [splitCashAmount, splitCardAmount, splitMobileAmount, splitLoyaltyAmount]);

  const amountRemaining = useMemo(() => {
    return Math.max(0, total - totalPaid);
  }, [total, totalPaid]);

  const changeDue = useMemo(() => {
    if (totalPaid <= total) return 0;
    return totalPaid - total;
  }, [totalPaid, total]);

  // Sync state with customer display channel
  useEffect(() => {
    customerDisplayChannelRef.current?.postMessage({
      type: 'cart-update',
      cart: cart.map((item) => ({
        name: item.name,
        price: item.price - item.discount,
        quantity: item.quantity,
      })),
      subtotal,
      discount: discountAmount,
      tax: taxAmount,
      total,
    });
  }, [cart, subtotal, discountAmount, taxAmount, total]);

  // Cart operations
  const addToCart = (product: any) => {
    const existing = cart.find((item) => item.product_id === product.id);
    if (existing) {
      setCart(
        cart.map((item) =>
          item.product_id === product.id ? { ...item, quantity: item.quantity + 1 } : item
        )
      );
    } else {
      setCart([
        ...cart,
        {
          product_id: product.id,
          name: product.name,
          sku: product.sku,
          price: product.price,
          cost: product.cost,
          quantity: 1,
          discount: 0,
        },
      ]);
    }
  };

  const updateQty = (productId: number, delta: number) => {
    setCart(
      cart
        .map((item) =>
          item.product_id === productId ? { ...item, quantity: Math.max(0.01, item.quantity + delta) } : item
        )
        .filter((item) => item.quantity > 0)
    );
  };

  const removeItem = (productId: number) => {
    setCart(cart.filter((item) => item.product_id !== productId));
  };

  const clearCart = () => {
    setCart([]);
    setSelectedCustomerId('');
    setDiscountRate(0.0);
    customerDisplayChannelRef.current?.postMessage({ type: 'clear' });
  };

  // Hold / Park sale
  const parkSale = async () => {
    if (cart.length === 0 || !db) return;
    const name = prompt('Enter reference for this held sale:') || `Held Sale #${Date.now().toString().slice(-4)}`;

    await db.table('parked_orders').add({
      offline_id: 'park-' + self.crypto.randomUUID(),
      name,
      customer_id: selectedCustomerId ? parseInt(selectedCustomerId) : null,
      cart: JSON.parse(JSON.stringify(cart)),
      discount_rate: discountRate,
      tax_rate: taxRate,
      total_amount: total,
      created_at: new Date().toISOString(),
    });

    clearCart();
    await loadLocalCache();
  };

  const openRecallModal = async () => {
    if (!db) return;
    const list = await db.table('parked_orders').toArray();
    setParkedOrders(list);
    setShowRecallModal(true);
  };

  const recallSale = async (order: any) => {
    if (!db) return;
    setCart(order.cart);
    setSelectedCustomerId(order.customer_id ? order.customer_id.toString() : '');
    setDiscountRate(order.discount_rate);
    await db.table('parked_orders').delete(order.offline_id);
    setShowRecallModal(false);
    await loadLocalCache();
  };

  // Checkout process
  const openCheckout = () => {
    if (cart.length === 0) return;
    setSplitCashAmount(total.toFixed(2));
    setSplitCardAmount('');
    setSplitMobileAmount('');
    setSplitLoyaltyAmount('');
    setCardVerified(false);
    setMobileVerified(false);
    setStripeTrxId('');
    setBkashWallet('');
    setBkashTrxId('');
    setIsCheckingOut(true);
  };

  const submitCheckout = async () => {
    if (amountRemaining > 0.01 || !db) {
      alert('Payment remaining. Please input details.');
      return;
    }

    const orderId = 'pos-sale-' + self.crypto.randomUUID();
    const orderTimestamp = new Date().toISOString();

    const order = {
      offline_id: orderId,
      branch_id: session?.user?.branchId || 1,
      user_id: session?.user?.id ? parseInt(session.user.id) : 1,
      customer_id: selectedCustomerId ? parseInt(selectedCustomerId) : null,
      subtotal: parseFloat(subtotal.toFixed(2)),
      tax_amount: parseFloat(taxAmount.toFixed(2)),
      discount_amount: parseFloat(discountAmount.toFixed(2)),
      total_amount: parseFloat(total.toFixed(2)),
      payment_method: 'split',
      payment_details: {
        cash: parseFloat(splitCashAmount) || 0,
        card: parseFloat(splitCardAmount) || 0,
        card_trx_id: stripeTrxId,
        mobile: parseFloat(splitMobileAmount) || 0,
        mobile_wallet: bkashWallet,
        mobile_trx_id: bkashTrxId,
        loyalty: parseFloat(splitLoyaltyAmount) || 0,
      },
      payment_status: 'paid',
      status: 'completed',
      notes: '',
      created_at: orderTimestamp,
      items: cart.map((item) => ({
        product_id: item.product_id,
        quantity: parseFloat(item.quantity.toFixed(2)),
        price: parseFloat(item.price.toFixed(2)),
        cost: parseFloat(item.cost.toFixed(2)),
        discount: parseFloat(item.discount.toFixed(2)),
        subtotal: parseFloat(((item.price - item.discount) * item.quantity).toFixed(2)),
      })),
    };

    // Save locally to IndexedDB queue
    await db.table('pending_orders').add(order);

    // Deduct stock in IndexedDB cache
    for (const item of order.items) {
      const localProd = await db.table('products').get(item.product_id);
      if (localProd) {
        localProd.stock_quantity = Math.max(0, localProd.stock_quantity - item.quantity);
        await db.table('products').put(localProd);
      }
    }

    // Deduct loyalty points if used
    if (order.payment_details.loyalty > 0 && order.customer_id) {
      const customer = await db.table('customers').get(order.customer_id);
      if (customer) {
        const pointsDeducted = Math.round(order.payment_details.loyalty * 10);
        customer.loyalty_points = Math.max(0, customer.loyalty_points - pointsDeducted);
        await db.table('customers').put(customer);
      }
    }

    clearCart();
    setIsCheckingOut(false);
    await loadLocalCache();

    setReceiptOrder(order);
    setShowReceipt(true);

    customerDisplayChannelRef.current?.postMessage({
      type: 'checkout-complete',
      receiptId: orderId,
      total: order.total_amount,
    });

    if (onlineStatus) {
      syncData();
    }
  };

  // Sync API pull / push
  const syncData = async () => {
    if (isSyncing || !db) return;
    setIsSyncing(true);
    try {
      const pending = await db.table('pending_orders').toArray();
      const pendingReturns = await db.table('returns').toArray();

      // PUSH pending sync data to API route backend
      if (pending.length > 0 || pendingReturns.length > 0) {
        const response = await fetch('/api/sync', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ orders: pending, returns: pendingReturns }),
        });
        if (response.ok) {
          const result = await response.json();
          if (result.success) {
            if (result.synced_offline_ids) {
              for (const id of result.synced_offline_ids) {
                await db.table('pending_orders').delete(id);
              }
            }
            if (result.synced_offline_return_ids) {
              for (const id of result.synced_offline_return_ids) {
                await db.table('returns').delete(id);
              }
            }
          }
        }
      }

      // PULL fresh databases from backend
      const branchIdParam = session?.user?.branchId || 1;
      const pullResponse = await fetch(`/api/sync?branch_id=${branchIdParam}`);
      if (pullResponse.ok) {
        const data = await pullResponse.json();
        for (const cat of data.categories) await db.table('categories').put(cat);
        for (const cust of data.customers) await db.table('customers').put(cust);
        for (const prod of data.products) await db.table('products').put(prod);
      }

      await loadLocalCache();
    } catch (err) {
      console.error(err);
    } finally {
      setIsSyncing(false);
    }
  };

  // Trigger Stripe Payment terminal mock logic
  const handleStripeMock = () => {
    const amt = parseFloat(splitCardAmount) || 0;
    if (amt <= 0) return;
    setStripeTrxId('STRIPE-CHG-' + Math.random().toString(36).substring(2, 9).toUpperCase());
    setCardVerified(true);
  };

  const handleBkashMock = () => {
    const amt = parseFloat(splitMobileAmount) || 0;
    if (amt <= 0) return;
    setBkashWallet('+8801700' + Math.floor(Math.random() * 900000 + 100000));
    setBkashTrxId('BKASH-TX-' + Math.random().toString(36).substring(2, 9).toUpperCase());
    setMobileVerified(true);
  };

  // Auth routing verification
  if (status === 'loading') {
    return (
      <div className="flex min-h-screen bg-slate-950 items-center justify-center">
        <div className="h-8 w-8 border-2 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin" />
      </div>
    );
  }

  if (!session) {
    router.push('/auth/login');
    return null;
  }

  return (
    <div className="flex h-screen w-screen overflow-hidden bg-slate-950 text-slate-100 font-sans relative">
      {/* 1. Products Catalog Panel */}
      <div className="flex-1 flex flex-col min-w-0 border-r border-slate-900">
        {/* Top POS Header */}
        <header className="h-16 px-6 bg-slate-900/60 backdrop-blur-md border-b border-slate-900 flex items-center justify-between z-10 shrink-0">
          <div className="flex items-center gap-3">
            <span className="text-sm font-extrabold tracking-wider bg-indigo-600/10 text-indigo-400 px-3 py-1.5 rounded-xl border border-indigo-500/20">
              TERMINAL #{session?.user?.branchId || 1}
            </span>
            <div className="flex items-center gap-2 text-xs font-semibold text-slate-400">
              <span className={`h-2.5 w-2.5 rounded-full ${onlineStatus ? 'bg-emerald-500' : 'bg-rose-500'}`} />
              {onlineStatus ? t('online') : t('offline')}
            </div>
          </div>

          <div className="flex items-center gap-4">
            <button
              onClick={syncData}
              disabled={isSyncing}
              className="flex items-center gap-2 px-3 py-1.5 bg-slate-800/80 hover:bg-slate-800 text-slate-200 border border-slate-700/60 rounded-xl text-xs font-bold transition-all disabled:opacity-50"
            >
              <RefreshCw className={`h-3.5 w-3.5 ${isSyncing ? 'animate-spin' : ''}`} />
              {t('sync_queue')} ({syncQueueCount})
            </button>
            <button
              onClick={toggleLanguage}
              className="px-2.5 py-1.5 bg-slate-800/80 text-xs font-bold rounded-xl border border-slate-700/60 hover:bg-slate-800 text-indigo-400"
            >
              {currentLocale === 'en' ? 'বাংলা ➔' : 'EN ➔'}
            </button>
            <div className="h-6 w-[1px] bg-slate-800" />
            <div className="text-right">
              <p className="text-xs font-bold text-slate-200">{session?.user?.name}</p>
              <p className="text-[10px] font-semibold text-slate-500">Cashier Terminal</p>
            </div>
            <button
              onClick={() => signOut({ callbackUrl: '/auth/login' })}
              className="text-slate-500 hover:text-rose-400 p-2 hover:bg-slate-900 rounded-xl transition-all"
            >
              <LogOut className="h-4.5 w-4.5" />
            </button>
          </div>
        </header>

        {/* Categories Bar */}
        <div className="p-4 bg-slate-950 shrink-0 flex gap-2 overflow-x-auto border-b border-slate-900/50">
          <button
            onClick={() => setSelectedCategory('all')}
            className={`px-4 py-2 text-xs font-bold rounded-xl border transition-all ${
              selectedCategory === 'all'
                ? 'bg-indigo-600 text-white border-indigo-500 shadow-lg shadow-indigo-600/10'
                : 'bg-slate-900/60 text-slate-400 border-slate-800/80 hover:text-slate-200 hover:bg-slate-900'
            }`}
          >
            All Products
          </button>
          {categoriesList.map((cat) => (
            <button
              key={cat.id}
              onClick={() => setSelectedCategory(cat.id.toString())}
              className={`px-4 py-2 text-xs font-bold rounded-xl border transition-all ${
                selectedCategory === cat.id.toString()
                  ? 'bg-indigo-600 text-white border-indigo-500 shadow-lg shadow-indigo-600/10'
                  : 'bg-slate-900/60 text-slate-400 border-slate-800/80 hover:text-slate-200 hover:bg-slate-900'
              }`}
            >
              {cat.name}
            </button>
          ))}
        </div>

        {/* Search Bar */}
        <div className="p-4 shrink-0 bg-slate-950">
          <div className="relative">
            <Search className="absolute left-4 top-3.5 text-slate-500 h-5 w-5" />
            <input
              type="text"
              placeholder={t('search_placeholder')}
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-12 pr-4 py-3 bg-slate-900/50 border border-slate-900 rounded-2xl text-sm font-semibold placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/80 transition-all"
            />
          </div>
        </div>

        {/* Products Grid */}
        <div className="flex-1 overflow-y-auto p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 align-content-start bg-slate-950/20">
          {filteredProducts.length > 0 ? (
            filteredProducts.map((prod) => (
              <div
                key={prod.id}
                onClick={() => addToCart(prod)}
                className="bg-slate-900/40 border border-slate-900/60 hover:border-slate-800/80 hover:bg-slate-900/60 p-4 rounded-2xl cursor-pointer transition-all flex flex-col justify-between group active:scale-[0.98]"
              >
                <div>
                  <div className="h-10 w-10 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-xl flex items-center justify-center font-black group-hover:scale-105 transition-all text-xs font-mono">
                    {prod.sku.slice(0, 3)}
                  </div>
                  <h3 className="mt-3 text-sm font-bold text-slate-200 truncate">{prod.name}</h3>
                  <p className="text-[10px] text-slate-500 font-mono tracking-wider mt-0.5">{prod.sku}</p>
                </div>
                <div className="mt-4 flex justify-between items-center">
                  <span className="text-sm font-black text-slate-100 font-mono">${prod.price.toFixed(2)}</span>
                  <span className="text-[10px] font-bold text-slate-500">Qty: {prod.stock_quantity}</span>
                </div>
              </div>
            ))
          ) : (
            <div className="col-span-full py-16 text-center text-slate-500 text-sm font-semibold">
              No products found matching filters.
            </div>
          )}
        </div>
      </div>

      {/* 2. Cart Operations Panel */}
      <div className="w-96 shrink-0 bg-slate-900/30 backdrop-blur-xl border-l border-slate-900 flex flex-col justify-between z-10">
        {/* Cart Title & Hold Actions */}
        <div className="p-4 border-b border-slate-900 flex items-center justify-between shrink-0 bg-slate-900/10">
          <h2 className="text-sm font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
            <ShoppingCart className="h-4.5 w-4.5 text-indigo-400" />
            Shopping Cart ({cart.length})
          </h2>
          <div className="flex gap-2">
            <button
              onClick={parkSale}
              className="p-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl border border-slate-700/60"
              title={t('hold_sale')}
            >
              <Bookmark className="h-4 w-4" />
            </button>
            <button
              onClick={openRecallModal}
              className="p-2 bg-slate-800 hover:bg-slate-700 text-slate-350 rounded-xl border border-slate-700/60 relative"
              title={t('recall_sale')}
            >
              <History className="h-4 w-4" />
              {parkedSalesCount > 0 && (
                <span className="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-rose-500 text-[9px] font-black flex items-center justify-center text-white">
                  {parkedSalesCount}
                </span>
              )}
            </button>
          </div>
        </div>

        {/* Customer & Discount Selectors */}
        <div className="p-4 shrink-0 space-y-3 bg-slate-900/10 border-b border-slate-900/60">
          {/* Customer */}
          <div>
            <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Select Customer</label>
            <select
              value={selectedCustomerId}
              onChange={(e) => setSelectedCustomerId(e.target.value)}
              className="w-full mt-1.5 px-3 py-2 bg-slate-950 border border-slate-900 rounded-xl text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="">{t('walkin_customer')}</option>
              {customersList.map((cust) => (
                <option key={cust.id} value={cust.id.toString()}>
                  {cust.name} ({cust.phone || 'no phone'})
                </option>
              ))}
            </select>
          </div>

          {/* Cart Discount */}
          <div>
            <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cart Discount</label>
            <select
              value={discountRate.toString()}
              onChange={(e) => setDiscountRate(parseFloat(e.target.value))}
              className="w-full mt-1.5 px-3 py-2 bg-slate-950 border border-slate-900 rounded-xl text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="0.00">{t('no_discount')}</option>
              <option value="0.05">{t('staff_discount')}</option>
              <option value="0.10">{t('special_promo')}</option>
              <option value="0.15">{t('loyalty_member')}</option>
              <option value="0.20">{t('clearance_sale')}</option>
            </select>
          </div>
        </div>

        {/* Cart Item Feed */}
        <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-950/20">
          {cart.length > 0 ? (
            cart.map((item) => (
              <div
                key={item.product_id}
                className="p-3 bg-slate-900/30 border border-slate-900/60 rounded-xl flex items-center justify-between"
              >
                <div className="min-w-0 flex-1">
                  <p className="text-xs font-bold text-slate-200 truncate">{item.name}</p>
                  <p className="text-[10px] text-slate-500 mt-0.5 font-mono">
                    ${(item.price - item.discount).toFixed(2)} / unit
                  </p>
                </div>
                <div className="flex items-center gap-3 shrink-0 ml-4">
                  {/* Qty edit */}
                  <div className="flex items-center bg-slate-950 border border-slate-900 rounded-lg">
                    <button onClick={() => updateQty(item.product_id, -1)} className="p-1.5 text-slate-400 hover:text-slate-200">
                      <Minus className="h-3 w-3" />
                    </button>
                    <span className="w-8 text-center text-xs font-bold text-slate-200 font-mono">{item.quantity}</span>
                    <button onClick={() => updateQty(item.product_id, 1)} className="p-1.5 text-slate-400 hover:text-slate-200">
                      <Plus className="h-3 w-3" />
                    </button>
                  </div>
                  {/* Delete */}
                  <button
                    onClick={() => removeItem(item.product_id)}
                    className="p-1.5 text-slate-500 hover:text-rose-400 bg-slate-950 border border-slate-900 rounded-lg"
                  >
                    <Trash2 className="h-3.5 w-3.5" />
                  </button>
                </div>
              </div>
            ))
          ) : (
            <div className="h-full flex flex-col justify-center items-center text-center text-slate-600 py-12">
              <ShoppingCart className="h-10 w-10 text-slate-800 mb-2" />
              <p className="text-xs font-bold">{t('cart_empty')}</p>
              <p className="text-[10px] text-slate-650 mt-1 max-w-[180px]">{t('cart_instructions')}</p>
            </div>
          )}
        </div>

        {/* Pricing Summary Footer */}
        <div className="p-6 border-t border-slate-900 bg-slate-900/40 shrink-0 space-y-4">
          <div className="space-y-2 text-xs font-semibold text-slate-400">
            <div className="flex justify-between">
              <span>{t('subtotal')}</span>
              <span className="font-mono text-slate-200">${subtotal.toFixed(2)}</span>
            </div>
            {discountAmount > 0 && (
              <div className="flex justify-between text-emerald-400">
                <span>Discount</span>
                <span className="font-mono">-${discountAmount.toFixed(2)}</span>
              </div>
            )}
            <div className="flex justify-between">
              <span>{t('tax')}</span>
              <span className="font-mono text-slate-200">${taxAmount.toFixed(2)}</span>
            </div>
            <div className="border-t border-slate-900/60 my-2" />
            <div className="flex justify-between text-sm font-bold text-slate-100">
              <span>{t('total_due')}</span>
              <span className="font-mono text-indigo-400 text-lg">${total.toFixed(2)}</span>
            </div>
          </div>

          <button
            onClick={openCheckout}
            disabled={cart.length === 0}
            className="w-full py-3 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/10 transition-all text-xs tracking-wide uppercase disabled:opacity-50"
          >
            {t('proceed_payment')}
          </button>
        </div>
      </div>

      {/* 3. Checkout Modal Overlay */}
      {isCheckingOut && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl relative">
            <div className="p-6 border-b border-slate-800 flex justify-between items-center">
              <h3 className="text-base font-bold text-slate-100">{t('payment_settlement')}</h3>
              <button
                onClick={() => setIsCheckingOut(false)}
                className="text-slate-500 hover:text-slate-350 text-xs font-bold"
              >
                Close
              </button>
            </div>

            <div className="p-6 space-y-6">
              {/* Summary */}
              <div className="bg-slate-950 p-4 rounded-2xl flex justify-between items-center border border-slate-800">
                <span className="text-xs font-bold text-slate-400">Total Amount Due</span>
                <span className="text-xl font-mono font-black text-indigo-400">${total.toFixed(2)}</span>
              </div>

              {/* Input Breakdowns */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                    <Coins className="h-3.5 w-3.5 text-amber-500" /> Cash Payment
                  </label>
                  <input
                    type="number"
                    value={splitCashAmount}
                    onChange={(e) => setSplitCashAmount(e.target.value)}
                    className="w-full mt-2 px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm font-mono text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    placeholder="0.00"
                  />
                </div>
                <div>
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                    <CreditCard className="h-3.5 w-3.5 text-indigo-500" /> Card Payment
                  </label>
                  <div className="flex gap-2 mt-2">
                    <input
                      type="number"
                      value={splitCardAmount}
                      onChange={(e) => setSplitCardAmount(e.target.value)}
                      className="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm font-mono text-slate-200 focus:outline-none"
                      placeholder="0.00"
                    />
                    <button
                      onClick={handleStripeMock}
                      className="px-3 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-indigo-400 border border-slate-700 rounded-xl whitespace-nowrap"
                    >
                      {cardVerified ? 'OK' : 'Verify'}
                    </button>
                  </div>
                </div>
                <div>
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                    <Phone className="h-3.5 w-3.5 text-pink-500" /> Mobile Banking
                  </label>
                  <div className="flex gap-2 mt-2">
                    <input
                      type="number"
                      value={splitMobileAmount}
                      onChange={(e) => setSplitMobileAmount(e.target.value)}
                      className="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm font-mono text-slate-200 focus:outline-none"
                      placeholder="0.00"
                    />
                    <button
                      onClick={handleBkashMock}
                      className="px-3 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-pink-400 border border-slate-700 rounded-xl whitespace-nowrap"
                    >
                      {mobileVerified ? 'OK' : 'Verify'}
                    </button>
                  </div>
                </div>
                <div>
                  <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                    <Sparkles className="h-3.5 w-3.5 text-emerald-500" /> Loyalty Points
                  </label>
                  <input
                    type="number"
                    value={splitLoyaltyAmount}
                    onChange={(e) => setSplitLoyaltyAmount(e.target.value)}
                    className="w-full mt-2 px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm font-mono text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    placeholder="0.00"
                  />
                </div>
              </div>

              {/* Status footer inside checkout */}
              <div className="pt-4 border-t border-slate-800/60 text-xs space-y-2">
                <div className="flex justify-between text-slate-400 font-bold">
                  <span>Total Paid:</span>
                  <span className="font-mono text-slate-200">${totalPaid.toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-slate-400 font-bold">
                  <span>Remaining:</span>
                  <span className="font-mono text-rose-400">${amountRemaining.toFixed(2)}</span>
                </div>
                {changeDue > 0 && (
                  <div className="flex justify-between text-slate-400 font-bold">
                    <span>{t('change_due')}:</span>
                    <span className="font-mono text-emerald-400">${changeDue.toFixed(2)}</span>
                  </div>
                )}
              </div>
            </div>

            <div className="p-6 bg-slate-900/60 border-t border-slate-800 flex justify-end gap-3">
              <button
                onClick={() => setIsCheckingOut(false)}
                className="px-4 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-700 text-xs font-bold rounded-xl"
              >
                Cancel
              </button>
              <button
                onClick={submitCheckout}
                disabled={amountRemaining > 0.01}
                className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg disabled:opacity-50"
              >
                Complete Sale
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 4. Receipt Screen Modal */}
      {showReceipt && receiptOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 backdrop-blur-md p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl relative p-6">
            <h3 className="text-center font-black text-emerald-400 text-lg">✓ TRANSACTION COMPLETE</h3>
            <p className="text-center text-xs text-slate-500 mt-1">Receipt generated successfully</p>

            {/* Receipt Content Section */}
            <div id="receiptPrintSection" className="bg-white text-black p-4 rounded-xl mt-6 font-mono text-xs space-y-3">
              <div className="text-center font-bold">APF POS TERMINAL</div>
              <div className="text-center">Branch ID: {receiptOrder.branch_id}</div>
              <div className="border-t border-dashed border-black/30 my-2" />
              <div>Receipt: {receiptOrder.offline_id.slice(0, 16)}</div>
              <div>Date: {receiptOrder.created_at.slice(0, 19).replace('T', ' ')}</div>
              <div className="border-t border-dashed border-black/30 my-2" />
              {receiptOrder.items.map((item: any, i: number) => (
                <div key={i} className="flex justify-between">
                  <span>
                    {item.quantity}x Prod#{item.product_id}
                  </span>
                  <span>${item.subtotal.toFixed(2)}</span>
                </div>
              ))}
              <div className="border-t border-dashed border-black/30 my-2" />
              <div className="flex justify-between font-bold">
                <span>TOTAL:</span>
                <span>${receiptOrder.total_amount.toFixed(2)}</span>
              </div>
              <div className="border-t border-dashed border-black/30 my-2" />
              <div className="text-center font-bold">THANK YOU FOR SHOPPING!</div>
            </div>

            <div className="mt-8 flex flex-col gap-2">
              <button
                onClick={() => {
                  window.print();
                }}
                className="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs uppercase"
              >
                Print Receipt
              </button>
              <button
                onClick={() => setShowReceipt(false)}
                className="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl text-xs uppercase"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 5. Recall / Parked Sales Modal */}
      {showRecallModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4">
          <div className="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl relative p-6">
            <h3 className="text-base font-bold text-slate-100 mb-6">Recall Parked / Held Sales</h3>
            <div className="space-y-3.5 max-h-96 overflow-y-auto pr-1">
              {parkedOrders.length > 0 ? (
                parkedOrders.map((order) => (
                  <div
                    key={order.offline_id}
                    onClick={() => recallSale(order)}
                    className="p-4 bg-slate-950/50 hover:bg-slate-950 border border-slate-800/80 hover:border-slate-700/80 rounded-2xl cursor-pointer transition-all flex justify-between items-center group"
                  >
                    <div>
                      <p className="text-sm font-bold text-slate-200 group-hover:text-indigo-400 transition-all">
                        {order.name}
                      </p>
                      <p className="text-[10px] text-slate-500 mt-1 font-mono">{order.created_at.slice(11, 16)}</p>
                    </div>
                    <span className="font-mono text-sm font-black text-slate-100">${order.total_amount.toFixed(2)}</span>
                  </div>
                ))
              ) : (
                <p className="text-xs text-slate-500 text-center py-8">No held sales found.</p>
              )}
            </div>
            <div className="mt-6 flex justify-end">
              <button
                onClick={() => setShowRecallModal(false)}
                className="px-4 py-2 bg-slate-850 hover:bg-slate-800 border border-slate-700 rounded-xl text-xs font-bold"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

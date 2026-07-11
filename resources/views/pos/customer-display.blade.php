<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Facing Display</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-950 text-slate-50 font-sans min-h-screen flex flex-col overflow-hidden">
    <!-- Header -->
    <header class="h-[70px] bg-slate-900 border-b border-slate-800 flex justify-between items-center px-8">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-400 to-emerald-400 bg-clip-text text-transparent">APF POS</h1>
            <span class="text-slate-400 text-sm font-semibold">Customer Display</span>
        </div>
        <div class="text-sm font-medium text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded border border-emerald-500/20 shadow-[0_0_8px_rgba(16,185,129,0.1)]">
            Active Terminal Session
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- Left: Branding & Promotions / Welcome -->
        <div class="flex-1 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 flex flex-col items-center justify-center p-12 text-center relative border-r border-slate-800">
            <!-- Background glow -->
            <div class="absolute w-[300px] h-[300px] bg-indigo-500/10 rounded-full blur-[120px] top-1/4 left-1/4"></div>
            <div class="absolute w-[200px] h-[200px] bg-emerald-500/5 rounded-full blur-[100px] bottom-1/4 right-1/4"></div>

            <div id="welcomeContainer" class="space-y-4">
                <div class="w-20 h-20 bg-indigo-600/10 border border-indigo-500/20 rounded-full flex items-center justify-center mx-auto shadow-inner">
                    <svg class="w-10 h-10 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-100">Welcome to APF Retail</h2>
                <p class="text-slate-400 max-w-sm text-sm">Please check your items on the right side screen. Thank you for shopping with us!</p>
            </div>

            <!-- Transaction Completed state -->
            <div id="completedContainer" class="hidden space-y-4">
                <div class="w-20 h-20 bg-emerald-600/10 border border-emerald-500/20 rounded-full flex items-center justify-center mx-auto shadow-inner">
                    <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-extrabold text-emerald-400">Transaction Complete!</h2>
                <p class="text-slate-200 text-sm">Thank you for your purchase.</p>
                <div class="bg-slate-900 border border-slate-800 rounded-lg p-4 inline-block text-left">
                    <div class="flex justify-between gap-8 text-xs text-slate-400">
                        <span>Receipt:</span>
                        <span id="completedReceiptId" class="font-bold text-slate-200 font-mono">-</span>
                    </div>
                    <div class="flex justify-between gap-8 text-xs text-slate-400 mt-1">
                        <span>Paid Amount:</span>
                        <span id="completedPaidAmount" class="font-bold text-slate-200">-</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Current Cart Panel -->
        <div class="w-[500px] bg-slate-950 flex flex-col h-full">
            <div class="p-6 border-b border-slate-800">
                <h3 class="text-lg font-bold text-slate-100 flex items-center gap-2">
                    🛒 Your Basket
                </h3>
            </div>

            <!-- Item List -->
            <div id="cartItemsList" class="flex-1 overflow-y-auto p-6 space-y-4">
                <!-- Fallback empty state -->
                <div id="emptyCartMessage" class="flex flex-col items-center justify-center h-full text-slate-500 text-center gap-3">
                    <svg class="w-12 h-12 text-slate-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-xs">No items scanned yet.</p>
                </div>
            </div>

            <!-- Totals Panel -->
            <div class="bg-slate-900 border-t border-slate-800 p-6 flex flex-col gap-3">
                <div class="flex justify-between text-sm text-slate-400">
                    <span>Subtotal</span>
                    <span id="subtotalVal">$0.00</span>
                </div>
                <div id="discountRow" class="flex justify-between text-sm text-rose-400 hidden">
                    <span>Discount</span>
                    <span id="discountVal">-$0.00</span>
                </div>
                <div class="flex justify-between text-sm text-slate-400">
                    <span>Tax (15%)</span>
                    <span id="taxVal">$0.00</span>
                </div>
                <div class="flex justify-between text-xl font-extrabold border-t border-slate-800 pt-4 text-slate-100">
                    <span>Total Due</span>
                    <span id="totalVal" class="text-emerald-400">$0.00</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Script to handle BroadcastChannel messages -->
    <script>
        const bc = new BroadcastChannel('apf_pos_customer_display');
        
        const welcomeContainer = document.getElementById('welcomeContainer');
        const completedContainer = document.getElementById('completedContainer');
        const completedReceiptId = document.getElementById('completedReceiptId');
        const completedPaidAmount = document.getElementById('completedPaidAmount');
        const cartItemsList = document.getElementById('cartItemsList');
        const emptyCartMessage = document.getElementById('emptyCartMessage');
        
        const subtotalVal = document.getElementById('subtotalVal');
        const discountRow = document.getElementById('discountRow');
        const discountVal = document.getElementById('discountVal');
        const taxVal = document.getElementById('taxVal');
        const totalVal = document.getElementById('totalVal');

        let resetTimer = null;

        bc.onmessage = (event) => {
            const data = event.data;
            if (!data) return;

            if (resetTimer) {
                clearTimeout(resetTimer);
                resetTimer = null;
            }

            if (data.type === 'cart-update') {
                welcomeContainer.classList.remove('hidden');
                completedContainer.classList.add('hidden');
                updateCart(data.cart, data.subtotal, data.discount, data.tax, data.total);
            } else if (data.type === 'checkout-complete') {
                welcomeContainer.classList.add('hidden');
                completedContainer.classList.remove('hidden');
                completedReceiptId.textContent = data.receiptId.slice(-12).toUpperCase();
                completedPaidAmount.textContent = '$' + data.total.toFixed(2);
                clearCartView();
                
                // Automatically return to welcome state after 10 seconds of idle
                resetTimer = setTimeout(() => {
                    welcomeContainer.classList.remove('hidden');
                    completedContainer.classList.add('hidden');
                }, 10000);
            } else if (data.type === 'clear') {
                welcomeContainer.classList.remove('hidden');
                completedContainer.classList.add('hidden');
                clearCartView();
            }
        };

        function updateCart(cart, subtotal, discount, tax, total) {
            // Clear current list
            cartItemsList.innerHTML = '';

            if (cart.length === 0) {
                cartItemsList.appendChild(emptyCartMessage);
                subtotalVal.textContent = '$0.00';
                discountRow.classList.add('hidden');
                taxVal.textContent = '$0.00';
                totalVal.textContent = '$0.00';
                return;
            }

            cart.forEach(item => {
                const row = document.createElement('div');
                row.className = 'flex justify-between items-center bg-slate-900/60 border border-slate-800 rounded-lg p-4';
                row.innerHTML = `
                    <div class="flex-1">
                        <div class="text-sm font-bold text-slate-200">${item.name}</div>
                        <div class="text-xs text-slate-500 mt-1">${item.quantity} x $${item.price.toFixed(2)}</div>
                    </div>
                    <div class="text-sm font-extrabold text-slate-100">$${(item.price * item.quantity).toFixed(2)}</div>
                `;
                cartItemsList.appendChild(row);
            });

            subtotalVal.textContent = '$' + subtotal.toFixed(2);
            if (discount > 0) {
                discountVal.textContent = '-$' + discount.toFixed(2);
                discountRow.classList.remove('hidden');
            } else {
                discountRow.classList.add('hidden');
            }
            taxVal.textContent = '$' + tax.toFixed(2);
            totalVal.textContent = '$' + total.toFixed(2);
        }

        function clearCartView() {
            cartItemsList.innerHTML = '';
            cartItemsList.appendChild(emptyCartMessage);
            subtotalVal.textContent = '$0.00';
            discountRow.classList.add('hidden');
            taxVal.textContent = '$0.00';
            totalVal.textContent = '$0.00';
        }
    </script>
</body>
</html>

<?php
#   TITLE   : Payment Pages
#   DESC    : Polished checkout and payment UI for embedded store orders.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS
#   RELEASE : 2026/08/01
?>

<div id="dashboard-container" class="container">
    <?php @include_once "header.php"; ?>

    <style>
        .payment-shell {
            display: grid;
            gap: 24px;
            padding: 24px 0 40px;
        }
        .payment-banner {
            border: 1px solid rgba(255,255,255,0.12);
            background: linear-gradient(135deg, rgba(124,58,237,0.22), rgba(255,138,61,0.16));
            border-radius: 24px;
            padding: 20px 24px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
        }
        .payment-banner strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 4px;
        }
        .payment-grid {
            display: grid;
            grid-template-columns: 1.35fr 0.85fr;
            gap: 24px;
        }
        .payment-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 20px 55px rgba(0,0,0,0.22);
        }
        .payment-card h2,
        .payment-card h3 {
            margin: 0 0 8px;
        }
        .payment-stepper {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 16px 0 20px;
        }
        .payment-step {
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            color: #cbd5e1;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .payment-step.active {
            background: rgba(124,58,237,0.22);
            color: #fff;
            border-color: rgba(124,58,237,0.4);
        }
        .field-group {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
        }
        .field-group label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #94a3b8;
        }
        .field-group input,
        .field-group select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.12);
            background: rgba(15,23,42,0.7);
            color: #f8fafc;
        }
        .payment-methods {
            display: grid;
            gap: 10px;
            margin-top: 8px;
        }
        .payment-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            cursor: pointer;
        }
        .payment-option.active {
            border-color: rgba(255,138,61,0.6);
            background: rgba(255,138,61,0.14);
        }
        .payment-option strong {
            display: block;
            margin-bottom: 2px;
        }
        .payment-option span {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .summary-list {
            display: grid;
            gap: 10px;
            margin: 12px 0 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            color: #cbd5e1;
            font-size: 0.95rem;
        }
        .summary-row.total {
            padding-top: 10px;
            margin-top: 6px;
            border-top: 1px solid rgba(255,255,255,0.12);
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .payment-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .payment-btn {
            border: 0;
            border-radius: 999px;
            padding: 12px 16px;
            font-weight: 700;
            cursor: pointer;
            color: #111827;
            background: linear-gradient(135deg, #ff8a3d, #ffb36b);
        }
        .payment-btn.secondary {
            color: #f8fafc;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
        }
        .payment-status {
            margin-top: 14px;
            min-height: 24px;
            color: #cbd5e1;
            font-size: 0.95rem;
        }
        .muted {
            color: #94a3b8;
        }
        .pill {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            color: #cbd5e1;
            font-size: 0.76rem;
            margin-top: 6px;
        }
        @media (max-width: 960px) {
            .payment-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="payment-shell">
        <div class="payment-banner">
            <div>
                <strong>Secure checkout</strong>
                <span class="muted">Polished order review with a Shopify-inspired experience for your embedded storefront.</span>
            </div>
            <div class="pill">256-bit protection • Fast confirmation</div>
        </div>

        <div class="payment-grid">
            <section class="payment-card">
                <div class="payment-stepper">
                    <div class="payment-step">Cart</div>
                    <div class="payment-step active">Information</div>
                    <div class="payment-step">Payment</div>
                </div>

                <h2>Checkout details</h2>
                <p class="muted">Your order is ready to complete. Add the final details and confirm the payment method.</p>

                <div class="field-group">
                    <label for="payEmail">Email</label>
                    <input id="payEmail" type="email" placeholder="name@example.com">
                </div>

                <div class="field-group">
                    <label for="payPhone">Phone</label>
                    <input id="payPhone" type="tel" placeholder="+27 000 000 0000">
                </div>

                <div class="field-group">
                    <label for="payAddress">Delivery address</label>
                    <input id="payAddress" type="text" placeholder="1 Market Street, Johannesburg">
                </div>

                <div class="field-group">
                    <label for="payMethod">Shipping</label>
                    <select id="payMethod">
                        <option value="Express">Express delivery · 1–2 business days</option>
                        <option value="Standard">Standard delivery · 3–5 business days</option>
                        <option value="Pickup">Pickup from store · Same day</option>
                    </select>
                </div>

                <h3 style="margin-top: 20px;">Payment method</h3>
                <div class="payment-methods">
                    <div class="payment-option active" data-method="Credit card">
                        <div>
                            <strong>Credit / debit card</strong>
                            <span>Instant and secure</span>
                        </div>
                        <span>•••• 4242</span>
                    </div>
                    <div class="payment-option" data-method="Instant EFT">
                        <div>
                            <strong>Instant EFT</strong>
                            <span>Bank transfer in seconds</span>
                        </div>
                        <span>Fast</span>
                    </div>
                    <div class="payment-option" data-method="Wallet">
                        <div>
                            <strong>Store wallet</strong>
                            <span>Use your store balance</span>
                        </div>
                        <span>Balance</span>
                    </div>
                </div>

                <div class="payment-actions">
                    <button class="payment-btn" id="payNowBtn">Pay now</button>
                    <button class="payment-btn secondary" id="backBtn" type="button">Back to cart</button>
                </div>
                <div id="paymentStatus" class="payment-status">Your payment will be processed securely after you confirm.</div>
            </section>

            <aside class="payment-card">
                <h3>Order summary</h3>
                <p class="muted">Everything is prepared from the simulation checkout draft.</p>
                <div id="summaryItems" class="summary-list"></div>
                <div class="summary-row"><span>Subtotal</span><strong id="subtotalValue">R0.00</strong></div>
                <div class="summary-row"><span>Shipping</span><strong id="shippingValue">R0.00</strong></div>
                <div class="summary-row"><span>Tax</span><strong id="taxValue">R0.00</strong></div>
                <div class="summary-row total"><span>Total</span><strong id="totalValue">R0.00</strong></div>
                <div class="pill" id="summaryMeta">No order loaded yet</div>
            </aside>
        </div>
    </div>

    <script>
        const money = (value) => 'R' + Number(value || 0).toFixed(2);
        const state = {
            draft: null,
            selectedMethod: 'Credit card'
        };

        function getDraft() {
            try {
                const stored = sessionStorage.getItem('striker_checkout_draft');
                if (stored) return JSON.parse(stored);
            } catch (e) {}
            return null;
        }

        function getQueryParam(name) {
            const params = new URLSearchParams(window.location.search);
            return params.get(name) || '';
        }

        function hydrate() {
            const draft = getDraft();
            state.draft = draft || {
                order: {
                    email: getQueryParam('customer_email') || 'customer@example.com',
                    customer_phone: '',
                    total: Number(getQueryParam('total') || 0),
                    items: []
                },
                cart: []
            };

            const email = state.draft.order?.email || state.draft.customer?.email || '';
            const phone = state.draft.order?.customer_phone || state.draft.customer?.phone || '';
            const total = Number(state.draft.order?.total || 0);
            document.getElementById('payEmail').value = email;
            document.getElementById('payPhone').value = phone;
            document.getElementById('payAddress').value = '1 Market Street, Johannesburg';

            const items = Array.isArray(state.draft.cart) && state.draft.cart.length
                ? state.draft.cart
                : (Array.isArray(state.draft.order?.items) ? state.draft.order.items : []);

            const summaryItems = document.getElementById('summaryItems');
            if (!items.length) {
                summaryItems.innerHTML = '<div class="muted">No items available. Return to the storefront and add something to the cart.</div>';
            } else {
                summaryItems.innerHTML = items.map((item) => `
                    <div class="summary-row">
                        <span>${item.name || 'Item'} × ${item.quantity || 1}</span>
                        <strong>${money((item.price || 0) * (item.quantity || 1))}</strong>
                    </div>
                `).join('');
            }

            const shipping = total > 0 ? 49 : 0;
            const tax = total * 0.15;
            document.getElementById('subtotalValue').textContent = money(total);
            document.getElementById('shippingValue').textContent = money(shipping);
            document.getElementById('taxValue').textContent = money(tax);
            document.getElementById('totalValue').textContent = money(total + shipping + tax);
            document.getElementById('summaryMeta').textContent = `${items.length} item${items.length === 1 ? '' : 's'} • ${state.selectedMethod}`;
        }

        document.querySelectorAll('.payment-option').forEach((option) => {
            option.addEventListener('click', () => {
                document.querySelectorAll('.payment-option').forEach((item) => item.classList.remove('active'));
                option.classList.add('active');
                state.selectedMethod = option.dataset.method;
                document.getElementById('summaryMeta').textContent = `${state.draft?.cart?.length || 0} item${(state.draft?.cart?.length || 0) === 1 ? '' : 's'} • ${state.selectedMethod}`;
            });
        });

        document.getElementById('payNowBtn').addEventListener('click', () => {
            const status = document.getElementById('paymentStatus');
            status.textContent = `Processing ${state.selectedMethod.toLowerCase()} payment...`;
            status.style.color = '#fbbf24';
            setTimeout(() => {
                status.textContent = 'Payment confirmed. Your order is now being prepared for dispatch.';
                status.style.color = '#86efac';
            }, 900);
        });

        document.getElementById('backBtn').addEventListener('click', () => {
            window.history.back();
        });

        hydrate();
    </script>
</div>
# Cart

What the cart page shows for removing the cart and for returning to the store.

## Removing the cart

With **Delete cleared carts** off, the default, the cart page shows **Start a new cart**, which leaves the old cart as an inactive cart in **Commerce -> Orders**. Turn it on at **Checkout -> Features** to show **Delete this cart**, which moves the cart to the trash.

A cart template of your own can post to the `foster-checkout/cart/delete` action, which moves the cart to the trash regardless of **Delete cleared carts**.

## Continue shopping

With **Continue shopping link** off, the default, only the empty cart shows **Continue shopping**. Turn the setting on at **Checkout -> Features** to show the link under **Checkout** on a cart with items as well. **Continue shopping text** sets that link's text; blank shows “Continue shopping”.

**Continue shopping link** and **Continue shopping text** appear only while **Use the built-in cart template** is on at **Checkout -> General**.

The link goes to **Cancel path** at **Checkout -> General**, which defaults to the homepage.

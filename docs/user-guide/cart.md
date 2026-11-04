# Cart

What the cart page offers for removing the cart.

With **Delete cleared carts** off, the default, the cart page shows **Start a new cart**, which leaves the old cart as an inactive cart in **Commerce -> Orders**. Turn it on at **Checkout -> Features** to show **Delete this cart**, which moves the cart to the trash.

A cart template of your own can post to the `foster-checkout/cart/delete` action, which moves the cart to the trash regardless of **Delete cleared carts**.

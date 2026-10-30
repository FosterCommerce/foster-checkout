# Cart

What **Clear the cart** on the cart page does with the cart.

With **Delete cleared carts** off, the default, **Clear the cart** leaves the cart as an inactive cart in **Commerce -> Orders**. Turn it on at **Checkout -> Features** to move the cart to the trash instead.

A cart template of your own can post to the `foster-checkout/cart/delete` action, which moves the cart to the trash regardless of **Delete cleared carts**.

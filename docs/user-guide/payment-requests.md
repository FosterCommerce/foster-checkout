# Payment requests

Email a customer a link to pay the balance of a completed order, such as after staff change the order and its total goes up.

## Sending a request

Open the order at **Commerce -> Orders** and click **Request payment**. The button shows while the order has a balance, to staff with Commerce's **Edit orders** permission. An order refunded in full counts as settled, so the button doesn't show and its pay link doesn't take a payment. After you confirm, Foster Checkout emails a pay link to the order's email address and shows the amount requested.

Changing an order does not email the customer.

## The email

The email is a Craft system message. Edit its subject and body at **Utilities -> System Messages**, under **When staff request payment of an order’s balance**. The subject and body receive these variables:

| Variable | Value |
| --- | --- |
| `order` | The order |
| `amount` | The balance, formatted in the order's currency |
| `link` | The pay page link |
| `expires` | The date the link stops working, 30 days after sending |

## The pay page

The link opens a page under the checkout path, such as `/checkout/pay`. The page shows the order number and the order's contact, shipping address, shipping method and billing address, without links to change them. The order summary shows **Paid** and **Balance due** under **Total**. The payment form lists each gateway the order can use, except Manual gateways and gateways used as a partial payment step. Single-page and stepped checkouts share this page.

The customer does not need to sign in. The link does not include the order's email address. Foster Checkout adds it to the payment on the server.

Foster Checkout doesn't apply **Required shipping address fields** or **Required billing address fields** to a payment from the pay page, because the page has no address form.

While **Enable saved payment methods** is on and the order's customer has an account, a visitor who isn't signed in sees **Sign in to use a saved payment method**. After signing in, the customer returns to the pay page. It lists their saved payment methods for every gateway it shows, without a save checkbox. See [saved payment methods](./saved-payment-methods.md).

A reason from the `Checkout::EVENT_DEFINE_PAYMENT_BLOCK` event replaces the payment form, as at checkout. See [blocking payment](../dev-guide/blocking-payment.md).

After the balance is paid, the page heading shows the order status. In place of the payment form, the page lists each payment.

An expired or altered link opens a page asking the customer to request a new link.

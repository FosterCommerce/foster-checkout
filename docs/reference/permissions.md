# Permissions

Assign these under **Settings -> Users -> User Groups**, or per user.

## Craft's plugin access

Every user needs Craft's own `accessPlugin-foster-checkout` before any permission below takes effect. Without it Craft returns a 403 for every page under `/admin/foster-checkout`, even for a user who holds `foster-checkout-viewContent`.

## Content

| Permission | Grants |
| --- | --- |
| `foster-checkout-viewContent` | Read-only access to **Checkout -> Content** |
| `foster-checkout-editContent` | Saving changes on that screen |

`editContent` is nested under `viewContent`, so granting it implies both.

### editContent grants code execution

Checkout copy is rendered as a Twig template so it can reference the cart or order. A user who can edit copy can therefore run arbitrary Twig, which means running code on the server. Treat this permission as equivalent to template access, and grant it only to people you would trust with that.

This is why the permission is assignable rather than fixed to admins: each build decides which groups or users get it. Make that decision deliberately.

## Settings

| Permission | Grants |
| --- | --- |
| `foster-checkout-manageAppearance` | **Checkout -> Appearance** |
| `foster-checkout-manageFeatures` | **Checkout -> Features** |
| `foster-checkout-manageSettings` | **Checkout -> Products**, **Gateways** and **General** |

Settings persist to project config, so they are editable only where `allowAdminChanges` is on. On production these screens are read-only regardless of permission.

## Navigation visibility

The **Checkout** item appears only when the user holds at least one of these permissions, and its subnav lists only the screens they can reach.

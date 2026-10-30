# Permissions

Assign these under **Settings -> Users -> User Groups**, or per user.

## Craft's plugin access

Every user needs Craft's own `accessPlugin-foster-checkout` to open any of these screens. Craft checks it only when a page loads; the permissions on this page gate saving. Without it, Craft returns a 403 for every page under `/admin/foster-checkout`, even for a user who has `foster-checkout-viewContent`.

## Content

| Permission | Grants |
| --- | --- |
| `foster-checkout-viewContent` | Read-only access to **Checkout -> Notes & Links** |
| `foster-checkout-editContent` | Saving changes on that screen |

In the permissions screen, `editContent` is nested under `viewContent`, so you cannot select `editContent` without `viewContent`. Craft does not grant the parent at runtime, so the plugin checks a permission granted outside the control panel by itself.

`editContent` grants code execution: the plugin renders checkout copy as Twig, so anyone who can edit it can run code on the server. Treat it as equivalent to template access.

## Settings

| Permission | Grants |
| --- | --- |
| `foster-checkout-manageAppearance` | **Checkout -> Appearance** |
| `foster-checkout-manageFeatures` | **Checkout -> Features** |
| `foster-checkout-manageSettings` | **Checkout -> Line Items**, **Gateways**, **Custom Fields**, **Addresses** and **General** |

The plugin stores settings in project config, so these screens are editable only where `allowAdminChanges` is on. Where `allowAdminChanges` is off, the screens are read-only for every user, whatever their permissions.

The **Checkout** nav item appears only for users with at least one of these permissions, and lists only the screens they can reach.

# Permissions

Assign these under **Settings -> Users -> User Groups**, or per user.

## Craft's plugin access

Every user needs Craft's own `accessPlugin-foster-checkout` to open any of these screens. Craft checks it on page loads only, so the permissions below are what gate a save. Without it Craft returns a 403 for every page under `/admin/foster-checkout`, even for a user who holds `foster-checkout-viewContent`.

## Content

| Permission | Grants |
| --- | --- |
| `foster-checkout-viewContent` | Read-only access to **Checkout -> Content** |
| `foster-checkout-editContent` | Saving changes on that screen |

`editContent` is nested under `viewContent` in the permissions screen, so it cannot be checked on its own there. Craft does not imply the parent at runtime, so a permission granted outside the control panel is checked on its own.

`editContent` grants code execution: checkout copy is rendered as Twig, so anyone who can edit it can run code on the server. Treat it as equivalent to template access. It is assignable rather than admin-only so each build can decide who gets it.

## Settings

| Permission | Grants |
| --- | --- |
| `foster-checkout-manageAppearance` | **Checkout -> Appearance** |
| `foster-checkout-manageFeatures` | **Checkout -> Features** |
| `foster-checkout-manageSettings` | **Checkout -> Products**, **Gateways** and **General** |

Settings persist to project config, so they are editable only where `allowAdminChanges` is on. On production these screens are read-only regardless of permission.

The **Checkout** nav item appears only for users holding at least one of these permissions, and lists only the screens they can reach.

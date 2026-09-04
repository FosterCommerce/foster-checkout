# Vendored Tailwind Play build

`tailwind-3.4.17.js` is the Tailwind Play CDN build, served from this plugin rather than
`cdn.tailwindcss.com` so the checkout loads no unpinned third-party script.

Fetched from:

```
https://cdn.tailwindcss.com/3.4.17?plugins=forms@0.5.10,typography@0.5.16
```

Tailwind CSS and both plugins are MIT licensed. Replacing the file means fetching that URL again
with the versions bumped and renaming to match.

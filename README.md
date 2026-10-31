## Better Account for Easy Digital Downloads

Adds tabs to your account pages (Profile, Orders, License Keys, Subscriptions, etc.), so customers can easily switch
between them. You manage the tabs as a regular WordPress menu.

### How to use

1. Go to **Appearance > Menus** and create a menu with your account pages, e.g. Account (your profile), Orders, Files,
   License Keys, Subscriptions, and a **Custom Link** to `/wp-login.php?action=logout` for logging out.
2. Assign the menu to the **Account Tabs** location.

That's it. Logged-in customers now see the tabs on:

- every page in the menu, including the views on that page (e.g. **Manage Sites** on License Keys, or **View
  Transactions** and **Update Payment Method** on Subscriptions),
- subpages of a page in the menu (they belong to the tab of their parent),
- EDD Recurring's **Update Payment Method** page (`/subscription/update/…`), with the tab of the page that holds the
  `[edd_subscriptions]` shortcode or Subscriptions block marked as current.

Only top level menu items are shown. Logged out visitors don't see the tabs.

On smaller screens, the current tab becomes a button that opens the other tabs.

### Tips

- Add the CSS class `is-danger` to a menu item (enable **CSS Classes** under **Screen Options** in Appearance > Menus)
  to show it in red, with a divider above it. E.g. for a page with the
  [Delete Account](https://github.com/Dan0sz/edd-delete-account) block.
- A logout link (`?action=logout`) gets a proper logout URL, so customers don't have to confirm they want to log out.
- The tabs take up a column next to the content, so give your account pages a normal (not narrow) content width.

### Styling

The tabs use your theme's fonts. Colors are custom properties you can override in your theme:

```css
.eba {
    --eba-accent: #2271b1; /* Defaults to Kadence's first palette color, if available. */
    --eba-accent-background: color-mix(in srgb, var(--eba-accent) 10%, #fff);
    --eba-danger: #b91c1c;
    --eba-divider: #e5e7eb;
    --eba-background: #fff;
    --eba-shadow: 0 10px 25px -5px rgb(0 0 0 / .08), 0 0 0 1px rgb(0 0 0 / .04);
    --eba-sticky-top: 2rem;
}
```

The `<body>` gets the class `has-account-tabs` on pages with tabs.

### Filters

- `edd_better_account_subscriptions_tab` — the tab to mark as current on EDD Recurring's Update Payment Method page.
